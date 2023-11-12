<?php

namespace Rpurinton\Ash;

class Configuration
{
    private $configFilePath;
    public $config = [];

    public function __construct()
    {
        require_once(__DIR__ . "/vendor/autoload.php");
        require_once(__DIR__ . "/Util.php");
        require_once(__DIR__ . "/OpenAI.php");
        require_once(__DIR__ . "/History.php");
        require_once(__DIR__ . "/Commands.php");
        require_once(__DIR__ . "/ParseArgs.php");
        require_once(__DIR__ . "/SysInfo.php");
        require_once(__DIR__ . "/ModelPicker.php");
        $this->configFilePath = __DIR__ . '/conf.d/config.json';
        $this->loadConfig();
    }

    private function loadConfig()
    {
        if (!file_exists($this->configFilePath)) {
            $this->initialConfig();
        } else {
            $this->config = json_decode(file_get_contents($this->configFilePath), true);
        }
        if ($this->config['debug']) echo "config: " . print_r($this->config, true) . "\n";
    }

    public function saveConfig()
    {
        file_put_contents($this->configFilePath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function initialConfig()
    {
        echo ("Initial configuration wizard...\n");
        $emailAddress = "";
        while (true) {
            $emailAddress = readline("Enter an emergency contact email [default: none]: ");
            if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || $emailAddress == "") break;
            echo "Error: Invalid email address.\n";
        }
        $fromAddress = "";
        while (true) {
            $fromAddress = readline("Enter the from email address [default: none]: ");
            if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || $fromAddress == "") break;
            echo "Error: Invalid email address.\n";
        }
        $openaiApiKey = "";
        while (true) {
            $openaiApiKey = readline("Enter your OpenAI API key: ");
            if (strlen($openaiApiKey) == 51 && substr($openaiApiKey, 0, 3) == "sk-") break;
            echo "Error: Invalid API key.\n";
        }
        $colorSupport = readline("Enable \e[31mcolor \e[32msupport?\e[0m [Y/n]: ");
        $colorSupport = strtolower(substr($colorSupport, 0, 1));
        if ($colorSupport == "y" || $colorSupport == "") $colorSupport = true;
        else $colorSupport = false;
        $emojiSupport = readline("Enable emoji support? ✅ [Y/n]: ");
        $emojiSupport = strtolower(substr($emojiSupport, 0, 1));
        if ($emojiSupport == "y" || $emojiSupport == "") $emojiSupport = true;
        else $emojiSupport = false;

        $debug = readline("Enable debug mode? [y/N]: ");
        $debug = strtolower(substr($debug, 0, 1));
        if ($debug == "y") $debug = true;
        else $debug = false;
        $this->config = [
            "emailAddress" => $emailAddress,
            "fromAddress" => $fromAddress,
            "openaiApiKey" => $openaiApiKey,
            "colorSupport" => $colorSupport,
            "emojiSupport" => $emojiSupport,
            "debug" => $debug,
        ];
        if (!is_dir(__DIR__ . '/conf.d')) mkdir(__DIR__ . '/conf.d', 0755, true);
        $this->saveConfig();
    }

    public function setDebug($debug)
    {
        $this->config['debug'] = $debug;
        $this->saveConfig();
    }

    public function setColorSupport($colorSupport)
    {
        $this->config['colorSupport'] = $colorSupport;
        $this->saveConfig();
    }

    public function setEmojiSupport($emojiSupport)
    {
        $this->config['emojiSupport'] = $emojiSupport;
        $this->saveConfig();
    }

    public function setOpenAIKey($openaiApiKey)
    {
        $this->config['openaiApiKey'] = $openaiApiKey;
        $this->saveConfig();
    }

    public function setOpenAIModel($openaiModel)
    {
        $this->config['openaiModel'] = $openaiModel;
        $this->saveConfig();
    }

    public function setOpenAITokens($openaiTokens)
    {
        $this->config['openaiTokens'] = $openaiTokens;
        $this->saveConfig();
    }
}
