<?php

namespace Rpurinton\Ash;

class OpenAI
{
    public $client = null;
    public $models = [];
    public $model = null;
    public $maxTokens = null;
    public $basePrompt = null;
    public $baseTokens = 0;
    public $runningProcess = null;
    public $util = null;
    public $history = null;
    // Renamed “functionHandlers” to “toolHandlers” for clarity with new usage
    public $toolHandlers = [];
    public $modelPicker = null;
    public $cancel = false;

    public function __construct(public $ash)
    {
        $this->util = new Util();
        $this->history = new History($this->util, $this->ash);
        $this->client = \OpenAI::client($this->ash->config->config['openaiApiKey']);
        $models = $this->client->models()->list()->data;
        foreach ($models as $model) {
            if (mb_substr($model->id, 0, 3) == 'gpt') {
                $this->models[] = $model->id;
            }
        }
        $this->modelPicker = new ModelPicker($this);
        $this->modelPicker->selectModel();
        $this->modelPicker->selectMaxTokens();
        $this->basePrompt = file_get_contents(__DIR__ . "/../prompt.d/base_prompt.txt") . "\n" .
            file_get_contents(__DIR__ . "/../prompt.d/custom_prompt.txt");
        $this->baseTokens = $this->util->tokenCount($this->basePrompt);
    }

    public function __destruct()
    {
        if ($this->runningProcess) {
            proc_terminate($this->runningProcess);
        }
    }

    public function welcomeMessage()
    {
        if (!$this->ash->debug) {
            passthru("clear");
        }
        $this->history->saveMessage([
            "role" => "system",
            "content" => "User started a new ash session from : " .
                $this->ash->sysInfo->sysInfo["who-u"] .
                "\n Please greet them!"
        ]);
        $messages = $this->buildPrompt();
        $messages[] = [
            "role" => "system",
            "content" => "Run any initial functions you need to, then review everything for any potential areas that may need attention, and finally write a short welcome message to the user with a brief assessment of server status/health.\n"
        ];
        $this->handlePromptAndResponse($messages);
    }

    public function userMessage($input)
    {
        $this->history->saveMessage(["role" => "user", "content" => $input]);
        $this->handlePromptAndResponse($this->buildPrompt());
    }

    /**
     * Build the system + user messages array that will be sent to the API.
     */
    private function buildPrompt()
    {
        if (!$this->ash->shell) {
            $this->ash->config->config['colorSupport'] = false;
        }

        $dynamic_prompt  = "Your full name is " . $this->ash->sysInfo->sysInfo['hostFQDN'];
        $dynamic_prompt .= ", but people can call you " . $this->ash->sysInfo->sysInfo['hostName'] . " for short.\n";
        $dynamic_prompt .= "SYSTEM: " . ($this->ash->config->config['emojiSupport']
            ? "Emoji support enabled!  Use it to express yourself!  🤣🤣🤣\n"
            : "Emoji support disabled. Do not send emoji!\n"
        );
        $dynamic_prompt .= "SYSTEM: " . ($this->ash->config->config['colorSupport']
            ? "Instead of Markdown, Use ANSI escape codes to add more style and emphasis to all your outputs including combinations of \e[95mcolors\e[0m, \e[1mbold\e[0m, \e[3mitalic\e[0m, \e[4munderline\e[0m, \e[9mstrikethrough\e[0m, \e[7minverted\e[0m, and \e[4;9;7mcombinations\e[0m!\nAlways prefer the 'light' variant like 'light blue' over 'blue' to ensure maximum compatibility with all terminal color schemes.\n"
            : "Terminal color support disabled. Do not send ANSI color/style codes!\n"
        );
        $dynamic_prompt .= "SYSTEM: Please do as much as you can to make sure the user has a good experience.  Take the lead in running any functions that may be needed, without prompting the user too much.  Assume they may be a novice.\n";
        $dynamic_prompt .= "SYSTEM: If problems arise, please iterate on the issue. Example, update config, restart service, check logs, update the config again, restart the service again, check the logs again, etc. Until it gets resolved or until you have exhausted all options.\n";
        $dynamic_prompt .= "SYSTEM: Please use the last USER message text before this as your primary point of context.";
        $dynamic_tokens = $this->util->tokenCount($dynamic_prompt);

        $messages   = [];
        $messages[] = ["role" => "system", "content" => $this->basePrompt . $dynamic_prompt];

        $response_space = round($this->maxTokens * 0.1, 0);
        $history_space  = $this->maxTokens - $this->baseTokens - $dynamic_tokens - $response_space;
        $messages       = array_merge($messages, $this->history->getHistory($history_space));

        return $messages;
    }

    /**
     * Sends our prompt to OpenAI (as a streamed request) and handles the response.
     */
    private function handlePromptAndResponse(array $messages)
    {
        $prompt = [
            "model"             => $this->model,
            "messages"          => $messages,
            "temperature"       => 0.25,
            "top_p"             => 0.25,
            "frequency_penalty" => 0.0,
            "presence_penalty"  => 0.0,
            // This is essential for the new function-calling approach:
            "tools"             => $this->getTools(),
        ];

        // If you want to block repeated calls when there's a loop of function calls, you could:
        // $prompt["function_call"] = "none" if you detect too many calls.

        if ($this->ash->shell) {
            if (!$this->ash->config->config["emojiSupport"]) {
                echo ("Thinking...");
            } else {
                echo ("🧠 Thinking...");
            }
        }

        try {
            $stream = $this->client->chat()->createStreamed($prompt);
        } catch (\Throwable $e) {
            if ($this->ash->debug) {
                echo ("debug: Error: " . print_r($e, true) . "\n");
            } else {
                echo ("Error: " . $e->getMessage() . "\n");
            }
            return;
        }

        $this->handleStream($stream);
    }

    /**
     * Handle the streamed response from the OpenAI API, capturing both normal text and
     * any number of parallel tool calls. Then execute them all afterward.
     */
    private function handleStream($stream)
    {
        $assistant_reply = "";
        $content_buffer  = ""; // buffer for printing partial lines
        $role           = "assistant";
        $finish_reason  = null;

        // We'll collect all tool calls from the entire message first
        $tool_calls = [];
        // Keep track of each known tool-call ID so we can append arguments
        $known_call_ids = [];

        $last_call_id = null;

        // The typical spinning “thinking” indicators, if desired.
        $status_chars = ["|", "/", "-", "\\"];
        $status_ptr   = 0;

        // Clear out any partial "thinking..." line
        if ($this->ash->shell) {
            echo "\r                                               \r";
        }

        try {
            foreach ($stream as $response) {
                if ($this->cancel) {
                    $this->cancel = false;
                    $stream->cancel();
                    return;
                }

                $reply = $response->choices[0]->toArray();
                // We usually get "delta": ...
                $delta = $reply["delta"] ?? [];

                // The finish_reason can be "stop", "tool_calls", etc.
                $finish_reason = $reply["finish_reason"] ?? $finish_reason;

                // If the assistant posted new text:
                if (!empty($delta["role"])) {
                    $role = $delta["role"];
                }
                if (!empty($delta["content"])) {
                    $assistant_reply   .= $delta["content"];
                    $content_buffer    .= $delta["content"];
                    // Let's flush lines whenever we see a newline, or handle wrapping
                    $line_break_pos     = mb_strrpos($content_buffer, "\n");
                    if ($line_break_pos !== false) {
                        $output        = mb_substr($content_buffer, 0, $line_break_pos);
                        $content_buffer = mb_substr($content_buffer, $line_break_pos + 1);
                        // Convert user-supplied markdown to ANSI if colorSupport is enabled
                        $output         = $this->util->markdownToEscapeCodes(
                            $output,
                            $this->ash->config->config['colorSupport']
                        );
                        echo $output . "\n";
                    }
                }

                // If the assistant posted new tool_calls array:
                //   "delta" => [ "tool_calls" => [ [ "id" => "abc123", "function" => [...]] ] ]
                if (isset($delta["tool_calls"]) && is_array($delta["tool_calls"])) {
                    foreach ($delta["tool_calls"] as $call) {
                        $call_id = $call["id"] ?? null;
                        $last_call_id = $call['id'] ?? $last_call_id;

                        // Skip if no "id" field
                        if (!$call_id) {
                            $call_id = $last_call_id;
                        }

                        if (!$last_call_id) {
                            continue;
                        }
                        // If we've never seen this call ID, initialize it
                        if (!array_key_exists($call_id, $tool_calls)) {
                            $tool_calls[$call_id] = [
                                "name"     => "",
                                "arguments" => ""
                            ];
                            $known_call_ids[] = $call_id;
                        }
                        // If the assistant gave us a function name
                        if (isset($call["function"]["name"])) {
                            $tool_calls[$call_id]["name"] = $call["function"]["name"];
                            if ($this->ash->shell) {
                                $status_ptr++;
                                if ($status_ptr > 3) $status_ptr = 0;
                                echo "\r";
                                echo "Tool call: " . $tool_calls[$call_id]["name"] . " " . $status_chars[$status_ptr];
                            }
                        }
                        // If we have function arguments
                        if (isset($call["function"]["arguments"])) {
                            $tool_calls[$call_id]["arguments"] .= $call["function"]["arguments"];
                            if ($this->ash->shell) {
                                $status_ptr++;
                                if ($status_ptr > 3) $status_ptr = 0;
                                echo "\r";
                                echo "Tool call: " . $tool_calls[$call_id]["name"] . " " . $status_chars[$status_ptr];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            if ($this->ash->debug) {
                echo ("debug: Error: " . print_r($e, true) . "\n");
            } else {
                echo ("Error: " . $e->getMessage() . "\n");
            }
            return;
        }

        // Print any leftover text that did not have a trailing newline
        if (!empty($content_buffer)) {
            $output = $this->util->markdownToEscapeCodes(
                $content_buffer,
                $this->ash->config->config['colorSupport']
            );
            echo $output . "\n";
        }

        // If the assistant had any textual portion, store it
        if (!empty($assistant_reply)) {
            $assistant_message = ["role" => "assistant", "content" => $assistant_reply];
            $this->history->saveMessage($assistant_message);
        }

        // If we found any tool-calls, add them as a single message with "tool_calls"
        if (count($tool_calls)) {
            $tool_call_array = [];
            foreach ($tool_calls as $id => $call) {
                $tool_call_array[] = [
                    "id"       => $id,
                    "type"     => "function",
                    "function" => [
                        "name"     => $call["name"],
                        "arguments" => $call["arguments"]
                    ]
                ];
            }
            // The assistant is effectively the "role" that triggered the calls
            $tool_call_message = ["role" => $role, "tool_calls" => $tool_call_array];
            $this->history->saveMessage($tool_call_message);
        }

        // Now handle all calls in one pass
        foreach ($tool_calls as $id => $call) {
            if (empty($call["name"])) {
                // no function name -> no-op
                continue;
            }

            // Attempt to parse the JSON arguments
            $args = json_decode($call["arguments"], true);
            if (!is_array($args)) {
                $args = [];
            }

            // Run the associated tool handler
            $result = ["stdout" => "", "stderr" => "No handler available", "exitCode" => -1];
            if (isset($this->toolHandlers[$call["name"]]) && is_callable($this->toolHandlers[$call["name"]])) {
                try {
                    $result = $this->toolHandlers[$call["name"]]($args);
                } catch (\Throwable $e) {
                    $result = [
                        "stdout"   => "",
                        "stderr"   => "Handler error: " . $e->getMessage(),
                        "exitCode" => -1
                    ];
                }
            }

            // Save the tool result back
            $this->history->saveMessage([
                "role"         => "tool",
                "tool_call_id" => $id,
                "content"      => is_string($result) ? $result : json_encode($result)
            ]);
        }

        // If the assistant indicated that it has more to say or more tools to call,
        // the finish_reason will be "tool_calls". In that case, we re-run the prompt
        // one more time with the updated history (rather than recursing for each call).
        if ($finish_reason === "tool_calls") {
            $this->handlePromptAndResponse($this->buildPrompt());
        }

        // If finish_reason === 'stop' or anything else, we are done.
    }

    /**
     * Retrieve the set of available tools, as a “tools” array with shape:
     *  [
     *    [
     *      'type' => 'function',
     *      'function' => [
     *         'name' => 'some_function_name',
     *         'description' => 'some string',
     *         'parameters' => [...JSON schema...]
     *      ]
     *    ],
     *    ...
     *  ]
     */
    private function getTools()
    {
        // This is the updated version of “getFunctions”,
        // but returning type=function and function=... per the new approach.
        exec('ls ' . __DIR__ . '/../tools.d/*.json', $functions);
        $result = [];
        foreach ($functions as $function) {
            $jsonArray = json_decode(file_get_contents($function), true);
            if (!$this->validateChatGPTJson($jsonArray)) {
                if ($this->ash->debug) {
                    echo ("debug: Error: Invalid JSON in $function\n");
                }
                continue;
            }
            $result[] = [
                'type'     => 'function',
                'function' => $jsonArray
            ];

            // If there’s a matching PHP handler file, include it so we can bind it:
            $handlerFile = str_replace(".json", ".php", $function);
            if (file_exists($handlerFile)) {
                include_once($handlerFile);
            }
        }
        return $result;
    }

    /**
     * Validate the function-JSON is correct for the new “tools” format per OpenAI specs.
     */
    private function validateChatGPTJson($jsonArray)
    {
        $requiredKeys = ['name', 'description', 'parameters'];
        if (!is_array($jsonArray)) {
            return false;
        }
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $jsonArray)) {
                return false;
            }
        }
        if (!is_string($jsonArray['name']) || !is_string($jsonArray['description'])) {
            return false;
        }
        // Check “parameters” object
        if (
            !is_array($jsonArray['parameters']) ||
            !array_key_exists('type', $jsonArray['parameters']) ||
            !array_key_exists('properties', $jsonArray['parameters']) ||
            $jsonArray['parameters']['type'] !== 'object' ||
            !is_array($jsonArray['parameters']['properties'])
        ) {
            return false;
        }
        // Must have "additionalProperties" => false for strictness
        if (
            !array_key_exists('additionalProperties', $jsonArray['parameters']) ||
            $jsonArray['parameters']['additionalProperties'] !== false
        ) {
            return false;
        }
        // If "required" is present, it must be an array
        if (
            isset($jsonArray['parameters']['required']) &&
            !is_array($jsonArray['parameters']['required'])
        ) {
            return false;
        }
        return true;
    }
}
