<?php

namespace Rpurinton\Ash;

class OpenAI
{
    private $client = null;
    private $models = [];
    private $model = null;
    private $maxTokens = null;
    private $basePrompt = null;
    private $baseTokens = 0;
    public $runningProcess = null;
    private $util = null;
    public $history = null;

    public function __construct(private $ash)
    {
        $this->util = new Util();
        $this->history = new History($this->util);
        $this->client = \OpenAI::client($this->ash->config->config['openaiApiKey']);
        $models = $this->client->models()->list()->data;
        foreach ($models as $model) if (mb_substr($model->id, 0, 3) == 'gpt') $this->models[] = $model->id;
        $this->selectModel();
        $this->selectMaxTokens();
        passthru("clear");
        $this->basePrompt = file_get_contents(__DIR__ . "/base_prompt.txt");
        $this->baseTokens = $this->util->tokenCount($this->basePrompt);
        $this->welcomeMessage();
    }

    public function __destruct()
    {
        if ($this->runningProcess) proc_terminate($this->runningProcess);
    }

    public function selectModel($force = false)
    {
        // Check if openai_model is set in the config
        if (!$force && isset($this->ash->config->config['openaiModel'])) {
            $model_id = $this->ash->config->config['openaiModel'];
            // Check if the model is in the list of models
            if (in_array($model_id, $this->models)) {
                $this->model = $model_id;
                return;
            }
        }

        // Prompt the user to select a model
        while (true) {
            $model_count = count($this->models);
            $prompt = "(ash) Please select an OpenAI GPT model to use:\n";
            for ($i = 0; $i < $model_count; $i++) {
                $prompt .= "(ash) [$i] {$this->models[$i]}\n";
            }
            $prompt .= "(ash) Enter the number of the model to use (default: 0 ({$this->models[0]})): ";
            $model_index = readline($prompt);
            if ($model_index == "") $model_index = 0;

            // Check if the selected model is valid
            if (isset($this->models[$model_index])) {
                $this->model = $this->models[$model_index];
                $this->ash->config->setOpenAIModel($this->model);
                return;
            }

            echo "(ash) Invalid model selected. Please try again.\n";
        }
    }

    public function selectMaxTokens($force = false)
    {

        if (!$force && isset($this->ash->config->config['openaiTokens'])) {
            $this->maxTokens = $this->ash->config->config['openaiTokens'];
            return;
        }

        while (true) {
            $prompt = "(ash) Please select the maximum tokens you want use for any single request (default: 4096, range [2048-131072]): ";
            $max_tokens = readline($prompt);
            if ($max_tokens == "") $max_tokens = 4096;

            if (is_numeric($max_tokens) && $max_tokens >= 2048 && $max_tokens <= 131072) {
                $this->maxTokens = $max_tokens;
                $this->ash->config->setOpenAITokens($this->maxTokens);
                return;
            }

            echo "(ash) Invalid max tokens value. Please try again.\n";
        }
    }

    public function welcomeMessage()
    {
        $this->history->saveMessage(["role" => "system", "content" => "User started a new ash session from : " . $this->ash->sysInfo->sysInfo["who-u"]]);
        $messages = $this->buildPrompt();
        $messages[] = ["role" => "system", "content" => "Run any initial functions you want then write your system login/welcome/motd/banner message here."];
        $this->handlePromptAndResponse($messages);
    }

    public function userMessage($input)
    {
        $this->history->saveMessage(["role" => "user", "content" => $input]);
        $this->handlePromptAndResponse($this->buildPrompt());
    }

    private function buildPrompt()
    {
        $messages[] = ["role" => "system", "content" => $this->basePrompt];
        $dynamic_prompt = "Your full name is " . $this->ash->sysInfo->sysInfo['hostFQDN'] . ", but people can call you " . $this->ash->sysInfo->sysInfo['hostName'] . " for short.\n";
        $dynamic_prompt .= "Here is the current situation: " . print_r($this->ash->sysInfo->sysInfo, true);
        if ($this->ash->config->config['emojiSupport']) $dynamic_prompt .= "Emoji support enabled!  Use it to express yourself!  🤣🤣🤣\n";
        else $dynamic_prompt .= "Emoji support disabled. Do not send emoji!\n";
        if ($this->ash->config->config['colorSupport']) $dynamic_prompt .= "Terminal  \e[31mcolor \e[32msupport\e[0m enabled! use it to highlight keywords and such.  for example use purple for directory or folder names, green for commands, and red for errors, blue for symlinks, gray for data files etc. blue for URLs, etc. You can also use alternating colors when displaying tables of information to make them easier to read.  \e[31mred \e[32mgreen \e[33myellow \e[34mblue \e[35mpurple \e[36mcyan \e[37mgray \e[0m.  Don't send the escape codes, send the actual unescaped color control symbols.\n";
        else $dynamic_prompt .= "Terminal color support disabled. Do not send color codes!\n";
        $dynamic_prompt .=  "Markdown support is disabled, don't include ``` or any other markdown formatting.\n";
        $messages[] = ["role" => "system", "content" => $dynamic_prompt];
        $dynamic_tokens = $this->util->tokenCount($dynamic_prompt);
        $response_space = round($this->maxTokens * 0.1, 0);
        $history_space = $this->maxTokens - $this->baseTokens - $dynamic_tokens - $response_space;
        $messages = array_merge($messages, $this->history->getHistory($history_space));
        return $messages;
    }

    private function handlePromptAndResponse($messages)
    {
        $prompt = [
            "model" => $this->model,
            "messages" => $messages,
            "max_tokens" => $this->maxTokens,
            "temperature" => 0.1,
            "top_p" => 0.1,
            "frequency_penalty" => 0.0,
            "presence_penalty" => 0.0,
            "functions" => $this->getFunctions(),
        ];
        $full_response = "";
        $function_call = null;
        if ($this->ash->debug) echo ("(ash) Sending prompt to OpenAI: " . print_r($prompt, true) . "\n");
        echo ("(ash) ");
        try {
            $stream = $this->client->chat()->createStreamed($prompt);
        } catch (\Exception $e) {
            if ($this->ash->debug) echo ("(ash) Error: " . print_r($e, true) . "\n");
            else echo ("(ash) Error: " . $e->getMessage() . "\n");
            return;
        } catch (\Error $e) {
            if ($this->ash->debug) echo ("(ash) Error: " . print_r($e, true) . "\n");
            else echo ("(ash) Error: " . $e->getMessage() . "\n");
            return;
        } catch (\Throwable $e) {
            if ($this->ash->debug) echo ("(ash) Error: " . print_r($e, true) . "\n");
            else echo ("(ash) Error: " . $e->getMessage() . "\n");
            return;
        }
        $line_char_count = 0;
        $line = "";
        foreach ($stream as $response) {
            $reply = $response->choices[0]->toArray();
            $finish_reason = $reply["finish_reason"];
            if (isset($reply["delta"]["function_call"]["name"])) {
                $function_call = $reply["delta"]["function_call"]["name"];
                if ($this->ash->debug) echo ("(ash) ✅ Running $function_call...\n");
            }
            if ($function_call) {
                if (isset($reply["delta"]["function_call"]["arguments"])) $full_response .= $reply["delta"]["function_call"]["arguments"];
            } else if (isset($reply["delta"]["content"])) {
                $delta_content = $reply["delta"]["content"];
                $full_response .= $delta_content;
                $line .= $delta_content;
                $line_char_count = mb_strlen($line);
                $debug_info = [
                    "full_response" => $full_response,
                    "line" => $line,
                    "line_char_count" => $line_char_count,
                    "delta_content" => $delta_content,
                ];
                //echo ("debug 1: " . print_r($debug_info, true) . "\n");
                if (strpos($line, "\n") !== false) {
                    $line = mb_substr($line, mb_strrpos($line, "\n") + 1);
                    $line_char_count = mb_strlen($line);
                }
                $debug_info = [
                    "full_response" => $full_response,
                    "line" => $line,
                    "line_char_count" => $line_char_count,
                    "delta_content" => $delta_content,
                ];
                //echo ("debug 2: " . print_r($debug_info, true) . "\n");
                if ($line_char_count > 64) {
                    $space_pos = mb_strrpos($line, " ");
                    if ($space_pos !== false) {
                        echo ("\n(ash)\t");
                        $line = mb_substr($line, $space_pos + 1);
                        $line_char_count = mb_strlen($line);
                    }
                }
                $debug_info = [
                    "full_response" => $full_response,
                    "line" => $line,
                    "line_char_count" => $line_char_count,
                    "delta_content" => $delta_content,
                ];
                //echo ("debug 3: " . print_r($debug_info, true) . "\n");
                echo ($delta_content);
                //echo ("================================\n");
            }
        }
        if ($function_call) {
            $arguments = json_decode($full_response, true);
        } else {
            $assistant_message = ["role" => "assistant", "content" => $full_response];
            $this->history->saveMessage($assistant_message);
        }
        echo ("\n");
        if ($this->ash->debug) {
            if ($function_call) echo ("(ash) ✅ Response complete.  Function call: " . print_r($arguments, true) . "\n");
            else echo ("(ash) Response complete.\n");
        }
    }

    private function getFunctions()
    {
        exec('ls ' . __DIR__ . '/functions.d/*.json', $functions);
        $result = [];
        foreach ($functions as $function) $result[] = json_decode(file_get_contents($function), true);
        return $result;
    }

    public function procExec(array $input): array
    {
        if ($this->ash->debug) echo ("(ash) proc_exec(" . print_r($input, true) . ")\n");
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];
        $pipes = [];
        try {
            $this->runningProcess = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'] ?? $this->ash->sysInfo->sysInfo['working_dir'], $input['env'] ?? []);
        } catch (\Exception $e) {
            return [
                "stdout" => "",
                "stderr" => "Error (ash): proc_open() failed: " . $e->getMessage(),
                "exit_code" => -1,
            ];
        }
        if (is_resource($this->runningProcess)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($this->runningProcess);
            $this->runningProcess = null;
            $result = [
                "stdout" => $stdout,
                "stderr" => $stderr,
                "exitCode" => $exitCode,
            ];
            if ($this->ash->debug) echo ("(ash) proc_exec() result: " . print_r($result, true) . "\n");
            return $result;
        }
        return [
            "stdout" => "",
            "stderr" => "Error (ash): proc_open() failed.",
            "exitCode" => -1,
        ];
    }
}
