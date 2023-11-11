<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");
require_once(__DIR__ . "/Util.php");
require_once(__DIR__ . "/OpenAI.php");
require_once(__DIR__ . "/History.php");
require_once(__DIR__ . "/Commands.php");
require_once(__DIR__ . "/Composer.php");
require_once(__DIR__ . "/ParseArgs.php");
require_once(__DIR__ . "/SysInfo.php");

class Configuration
{
    private $configFilePath;
    public $config = [];

    public function __construct()
    {
        $this->configFilePath = __DIR__ . '/conf.d/config.json';
        $this->loadConfig();
    }

    public function __toArray()
    {
        return $this->config;
    }

    private function loadConfig()
    {
        if (!file_exists($this->configFilePath)) {
            $this->initialConfig();
        } else {
            $this->config = json_decode(file_get_contents($this->configFilePath), true);
        }
        if ($this->config['debug']) echo "(ash) config: " . print_r($this->config, true) . "\n";
    }

    public function saveConfig()
    {
        file_put_contents($this->configFilePath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function initialConfig()
    {
        echo ("(ash) Initial configuration wizard...\n");
        $openai_api_key = "";
        while (true) {
            $openai_api_key = readline("(ash) Enter your OpenAI API key: ");
            if (strlen($openai_api_key) == 51 && substr($openai_api_key, 0, 3) == "sk-") break;
            echo "(ash) Error: Invalid API key.\n";
        }
        $color_support = readline("(ash) Enable \e[31mcolor \e[32msupport?\e[0m [Y/n]: ");
        $color_support = strtolower(substr($color_support, 0, 1));
        if ($color_support == "y" || $color_support == "") $color_support = true;
        else $color_support = false;
        $emoji_support = readline("(ash) Enable emoji support? ✅ [Y/n]: ");
        $emoji_support = strtolower(substr($emoji_support, 0, 1));
        if ($emoji_support == "y" || $emoji_support == "") $emoji_support = true;
        else $emoji_support = false;

        $debug = readline("(ash) Enable debug mode? [y/N]: ");
        $debug = strtolower(substr($debug, 0, 1));
        if ($debug == "y") $debug = true;
        else $debug = false;
        $this->config = [
            "openai_api_key" => $openai_api_key,
            "color_support" => $color_support,
            "emoji_support" => $emoji_support,
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
        $this->config['color_support'] = $colorSupport;
        $this->saveConfig();
    }

    public function setEmojiSupport($emojiSupport)
    {
        $this->config['emoji_support'] = $emojiSupport;
        $this->saveConfig();
    }

    public function setOpenAIKey($openaiApiKey)
    {
        $this->config['openai_api_key'] = $openaiApiKey;
        $this->saveConfig();
    }

    public function setOpenAIModel($openaiModel)
    {
        $this->config['openai_model'] = $openaiModel;
        $this->saveConfig();
    }

    public function setOpenAITokens($openaiTokens)
    {
        $this->config['openai_tokens'] = $openaiTokens;
        $this->saveConfig();
    }
}
