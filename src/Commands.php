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
                passthru("clear && clear");
                return "";
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
                $this->ash->config->setColorSupport(!$this->ash->config->config["color_support"]);
                if ($this->ash->config->config["color_support"]) return "(ash) Color support enabled.\n";
                else return "(ash) Color support disabled.\n";
            case "emoji":
                $this->ash->config->setEmojiSupport(!$this->ash->config->config["emoji_support"]);
                if ($this->ash->config->config["emoji_support"]) return "(ash) Emoji support enabled 🙂\n";
                else return "(ash) Emoji support disabled.\n";
            case "openai-key":
                $openaiApiKey = "";
                while (true) {
                    $openaiApiKey = readline("(ash) Enter your OpenAI API key: ");
                    if (strlen($openaiApiKey) == 51 && substr($openaiApiKey, 0, 3) == "sk-") break;
                    echo "(ash) Error: Invalid API key.\n";
                }
                $this->ash->config->setOpenAIKey($openaiApiKey);
                return "(ash) OpenAI API key updated.\n";
            case "openai-model":
                $this->ash->openai->selectModel(true);
                return "(ash) OpenAI model updated.\n";
            case "openai-tokens":
                $this->ash->openai->selectMaxTokens(true);
                return "(ash) OpenAI max tokens updated.\n";
        }
        if (substr($input, 0, 3) == "cd ") {
            $targetDir = substr($input, 3);
            if ($targetDir == "~") $target_dir = $this->ash->sysInfo['home_dir'];
            if ($target_dir == "-") $target_dir = $this->ash->sysInfo['last_dir'];
            if (is_dir($target_dir)) {
                $this->ash->sysInfo->setLastDir($this->ash->sysInfo['working_dir']);
                chdir($target_dir);
            } else echo "(ash) Error: Directory not found: $target_dir\n";
            return "";
        }
        return false;
    }
}
