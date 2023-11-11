<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;
    private $models = [];
    private $model = null;
    private $max_tokens = null;
    private $history = [];
    private $base_prompt = null;
    private $base_tokens = 0;
    private $running_process = null;
    private $encoder = null;


    public function __construct(private $ash)
    {
        $this->encoder = new \TikToken\Encoder();
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
        $models = $this->client->models()->list()->data;
        foreach ($models as $model) if (substr($model->id, 0, 3) == 'gpt') $this->models[] = $model->id;
        $this->select_model();
        $this->select_max_tokens();
        $this->base_prompt = file_get_contents(__DIR__ . "/base_prompt.txt");
        $this->base_tokens = $this->token_count($this->base_prompt);
        $this->load_history();
        $this->welcome_message();
    }

    public function token_count($input)
    {
        try {
            $count = count($this->encoder->encode($input));
        } catch (\Exception $e) {
            if ($this->ash->debug) echo ("(ash) Error: " . $e->getMessage() . "\n");
            $count = 0;
        }
        return $count;
    }

    public function __destruct()
    {
        if ($this->running_process) {
            proc_terminate($this->running_process);
            $this->running_process = null;
        }
    }

    public function load_history()
    {
        $history_file = __DIR__ . "/conf.d/history.json";
        if (file_exists($history_file)) {
            $this->history = json_decode(file_get_contents($history_file), true);
        } else {
            $this->history = [];
        }
    }

    public function save_message($message)
    {
        $message["tokens"] = $this->token_count($message["content"]);
        $this->history[] = $message;
        $message_json = json_encode($message);
        file_put_contents(__DIR__ . "/conf.d/history.json", $message_json . "\n", FILE_APPEND);
    }

    public function get_history($num_tokens)
    {
        $rev_history = array_reverse($this->history);
        $token_count = 0;
        $result = [];
        foreach ($rev_history as $message) {
            $token_count += $message["tokens"];
            if ($token_count <= $num_tokens) {
                unset($message["tokens"]);
                $result[] = $message;
            } else {
                $token_count -= $message["tokens"];
                break;
            }
        }
        return array_reverse($result);
    }

    public function select_model($force = false)
    {
        // Check if openai_model is set in the config
        if (!$force && isset($this->ash->config['openai_model'])) {
            $model_id = $this->ash->config['openai_model'];
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
                $this->ash->config['openai_model'] = $this->model;
                $this->ash->save_config();
                return;
            }

            echo "(ash) Invalid model selected. Please try again.\n";
        }
    }

    public function select_max_tokens($force = false)
    {
        // Check if openai_max_tokens is set in the config
        if (!$force && isset($this->ash->config['openai_max_tokens'])) {
            $this->max_tokens = $this->ash->config['openai_max_tokens'];
            return;
        }

        // Prompt the user to select a max_tokens value
        while (true) {
            $prompt = "(ash) Please select the maximum tokens you want use for any single request (default: 4096, range [2048-131072]): ";
            $max_tokens = readline($prompt);
            if ($max_tokens == "") $max_tokens = 4096;

            // Check if the selected max_tokens value is valid
            if (is_numeric($max_tokens) && $max_tokens >= 2048 && $max_tokens <= 131072) {
                $this->max_tokens = $max_tokens;
                $this->ash->config['openai_max_tokens'] = $this->max_tokens;
                $this->ash->save_config();
                return;
            }

            echo "(ash) Invalid max_tokens value. Please try again.\n";
        }
    }

    public function welcome_message()
    {
        $messages[] = ["role" => "system", "content" => $this->base_prompt];
        $response_space = round($this->max_tokens * 0.1, 0);
        $history_space = $this->max_tokens - $this->base_tokens - $response_space;
        $messages = array_merge($messages, $this->get_history($history_space));
        $messages[] = ["role" => "system", "content" => "Your full name is " . $this->ash->sys_info['host_fqdn'] . ", but people can call you " . $this->ash->sys_info['host_name'] . " for short."];
        $messages[] = ["role" => "system", "content" => "Here is the current situation: " . print_r($this->ash->sys_info, true)];
        if ($this->ash->config['color_support']) $messages[] = ["role" => "system", "content" => "Terminal  \e[31mcolor \e[32msupport\e[0m enabled! use it to highlight keywords and such.  for example use purple for directory or folder names, green for commands, and red for errors, blue for symlinks, gray for data files etc. blue for URLs, etc. You can also use alternating colors when displaying tables of information to make them easier to read.  \e[31mred \e[32mgreen \e[33myellow \e[34mblue \e[35mpurple \e[36mcyan \e[37mgray \e[0m"];
        if ($this->ash->config['emoji_support']) $messages[] = ["role" => "system", "content" => "Emoji support enabled!  Use it to express yourself!  🤣🤣🤣"];
        $messages[] = ["role" => "system", "content" => "The user " . $this->ash->sys_info['user_id'] . " just logged on.  Please write a welcome message from you (" . $this->ash->sys_info['host_name'] . ") to " . $this->ash->sys_info['user_id'] . "."];
        $messages[] = ["role" => "system", "content" => "Be sure to word-wrap your response to 80 characters or less by including line breaks in all messages."];
        $messages[] = ["role" => "system", "content" => "Markdown support disabled, don't include and ``` or markdown formatting. This is just a text-CLI."];
        $prompt = [
            "model" => $this->model,
            "messages" => $messages,
            "max_tokens" => $this->max_tokens,
            "temperature" => 0.1,
            "top_p" => 0.1,
            "frequency_penalty" => 0.0,
            "presence_penalty" => 0.0,
        ];
        $full_response = "";
        $function_call = null;
        if ($this->ash->debug) echo ("(ash) Sending prompt to OpenAI: " . print_r($prompt, true) . "\n");
        echo ("(ash) ");
        $stream = $this->client->chat()->createStreamed($prompt);
        foreach ($stream as $response) {
            $reply = $response->choices[0]->toArray();
            $finish_reason = $reply["finish_reason"];
            if (isset($reply["delta"]["function_call"]["name"])) {
                $function_call = $reply["delta"]["function_call"]["name"];
                echo ("✅ Running $function_call...\n");
            }
            if ($function_call) {
                if (isset($reply["delta"]["function_call"]["arguments"])) $full_response .= $reply["delta"]["function_call"]["arguments"];
            } else if (isset($reply["delta"]["content"])) {
                $delta_content = $reply["delta"]["content"];
                $full_response .= $delta_content;
                echo ($delta_content);
            }
        }
        if ($this->ash->debug) echo ("(ash) Response complete.\n");
        echo ("\n\n");
    }

    public function user_message($input)
    {
        $user_message = ["role" => "user", "content" => $input];
        $this->save_message($user_message);
        $messages[] = ["role" => "system", "content" => $this->base_prompt];
        $response_space = round($this->max_tokens * 0.1, 0);
        $history_space = $this->max_tokens - $this->base_tokens - $response_space;
        $messages = array_merge($messages, $this->get_history($history_space));
        $messages[] = ["role" => "system", "content" => "Your full name is " . $this->ash->sys_info['host_fqdn'] . ", but people can call you " . $this->ash->sys_info['host_name'] . " for short."];
        $messages[] = ["role" => "system", "content" => "Here is the current situation: " . print_r($this->ash->sys_info, true)];
        if ($this->ash->config['color_support']) $messages[] = ["role" => "system", "content" => "Terminal  \e[31mcolor \e[32msupport\e[0m enabled! use it to highlight keywords and such.  for example use purple for directory or folder names, green for commands, and red for errors, blue for symlinks, gray for data files etc. blue for URLs, etc. You can also use alternating colors when displaying tables of information to make them easier to read.  \e[31mred \e[32mgreen \e[33myellow \e[34mblue \e[35mpurple \e[36mcyan \e[37mgray \e[0m.  Don't send the escape codes, send the actual color control symbols."];
        if ($this->ash->config['emoji_support']) $messages[] = ["role" => "system", "content" => "Emoji support enabled!  Use it to express yourself!  🤣🤣🤣"];
        $messages[] = ["role" => "system", "content" => "Be sure to word-wrap your response to 80 characters or less by including line breaks in all messages."];
        $messages[] = ["role" => "system", "content" => "Markdown support is disabled, don't include ``` or any other markdown formatting. This is just a text-CLI."];
        $prompt = [
            "model" => $this->model,
            "messages" => $messages,
            "max_tokens" => $this->max_tokens,
            "temperature" => 0.1,
            "top_p" => 0.1,
            "frequency_penalty" => 0.0,
            "presence_penalty" => 0.0,
            "functions" => $this->get_functions(),
        ];
        $full_response = "";
        $function_call = null;
        if ($this->ash->debug) echo ("(ash) Sending prompt to OpenAI: " . print_r($prompt, true) . "\n");
        echo ("(ash) ");
        $stream = $this->client->chat()->createStreamed($prompt);
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
                echo ($delta_content);
            }
        }
        if ($function_call) {
            $arguments = json_decode($full_response, true);
        } else {
            $assistant_message = ["role" => "assistant", "content" => $full_response];
            $this->save_message($assistant_message);
        }
        if ($this->ash->debug) {
            if ($function_call) echo ("(ash) ✅ Response complete.  Function call: " . print_r($arguments, true) . "\n");
            else echo ("(ash) Response complete.\n");
        }
        echo ("\n\n");
    }

    private function get_functions()
    {
        exec('ls ' . __DIR__ . '/functions.d/*.json', $functions);
        $result = [];
        foreach ($functions as $function) $result[] = json_decode(file_get_contents($function), true);
        return $result;
    }

    public function proc_exec(array $input): array
    {
        if ($this->ash->debug) echo ("(ash) proc_exec(" . print_r($input, true) . ")\n");
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];
        $pipes = [];
        try {
            $this->running_process = proc_open($input['command'], $descriptorspec, $pipes, $input['cwd'] ?? $this->ash->sys_info['working_dir'], $input['env'] ?? []);
        } catch (\Exception $e) {
            return [
                "stdout" => "",
                "stderr" => "Error (ash): proc_open() failed: " . $e->getMessage(),
                "exit_code" => -1,
            ];
        }
        if (is_resource($this->running_process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit_code = proc_close($this->running_process);
            $this->running_process = null;
            $result = [
                "stdout" => $stdout,
                "stderr" => $stderr,
                "exit_code" => $exit_code,
            ];
            if ($this->ash->debug) echo ("(ash) proc_exec() result: " . print_r($result, true) . "\n");
            return $result;
        }
        return [
            "stdout" => "",
            "stderr" => "Error (ash): proc_open() failed.",
            "exit_code" => -1,
        ];
    }
}
