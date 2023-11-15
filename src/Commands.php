<?php

namespace Rpurinton\Ash;

class Commands
{
    public function __construct(private $ash)
    {
    }

    public function internalCommands($input)
    {
        switch ($input) {
            case "":
                return "";
            case "exit":
            case "quit":
                exit(0);
            case "bash":
                passthru(trim(shell_exec("which bash")));
                return "";
            case "clear":
                $this->ash->openai->history->clearHistory();
                passthru("clear");
                usleep(10000);
                return "\r";
            case "help":
                return file_get_contents(__DIR__ . "/../README.md") . "\n";
            case "sysinfo":
                return print_r($this->ash->sysInfo, true) . "\n";
            case "debug":
                $this->ash->debug = !$this->ash->debug;
                $this->ash->config->setDebug($this->ash->debug);
                if ($this->ash->debug) return "debug: Debug mode enabled.\n";
                else return "debug: Debug mode disabled.\n";
            case "config":
                $this->ash->config->initialConfig();
                return "";
            case "color":
                $this->ash->config->setColorSupport(!$this->ash->config->config["colorSupport"]);
                if ($this->ash->config->config["colorSupport"]) return "Color support enabled.\n";
                else return "Color support disabled.\n";
            case "emoji":
                $this->ash->config->setEmojiSupport(!$this->ash->config->config["emojiSupport"]);
                if ($this->ash->config->config["emojiSupport"]) return "Emoji support enabled 🙂\n";
                else return "Emoji support disabled.\n";
            case "openai-key":
                $openaiApiKey = "";
                while (true) {
                    $openaiApiKey = readline("Enter your OpenAI API key: ");
                    if (strlen($openaiApiKey) == 51 && substr($openaiApiKey, 0, 3) == "sk-") break;
                    echo "Error: Invalid API key.\n";
                }
                $this->ash->config->setOpenAIKey($openaiApiKey);
                return "OpenAI API key updated.\n";
            case "openai-model":
                $this->ash->openai->modelPicker->selectModel(true);
                return "OpenAI model updated.\n";
            case "openai-tokens":
                $this->ash->openai->modelPicker->selectMaxTokens(true);
                return "OpenAI max tokens updated.\n";
        }
        if (substr($input, 0, 3) == "cd ") {
            $targetDir = substr($input, 3);
            if ($targetDir == "~") $targetDir = $this->ash->sysInfo->sysInfo['homeDir'];
            if ($targetDir == "-") $targetDir = $this->ash->sysInfo->sysInfo['lastDir'];
            if (is_dir($targetDir)) {
                $this->ash->sysInfo->setLastDir($this->ash->sysInfo->sysInfo['workingDir']);
                chdir($targetDir);
                $this->ash->openai->history->saveMessage([
                    "role" => "user",
                    "content" => "User changed current working directory to $targetDir"
                ]);
            } else echo "Error: Directory not found: $targetDir\n";
            return "";
        }
        return false;
    }
}
