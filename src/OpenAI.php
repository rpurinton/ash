<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $config = [];
    private $openai = null;

    public function __construct()
    {
        if (!file_exists(__DIR__ . '/conf.d/openai.json')) $this->initial_config();
        $this->config = json_decode(file_get_contents(__DIR__ . '/conf.d/openai.json'), true);
    }

    public function initial_config()
    {
        echo ("(ash) Initial configuration wizard...\n");
        $open_ai_api_key = readline("(ash) Enter an OpenAI API key: ");
        // make the word 'color' blue and support 'green'
        $color_support = readline("(ash) Enable \e[31mcolor \e[32msupport?\e[0m [Y/n]: ");
        $color_support = strtolower($color_support);
        if ($color_support == "y" || $color_support == "") $color_support = true;
        else $color_support = false;
        $emoji_support = readline("(ash) Enable emoji support? ✅ [Y/n]: ");
        $emoji_support = strtolower($emoji_support);
        if ($emoji_support == "y" || $emoji_support == "") $emoji_support = true;
        else $emoji_support = false;
        $this->config = [
            "open_ai_api_key" => $open_ai_api_key,
            "color_support" => $color_support,
            "emoji_support" => $emoji_support
        ];
        if (!is_dir(__DIR__ . '/conf.d')) mkdir(__DIR__ . '/conf.d', 0755, true);
        file_put_contents(__DIR__ . '/conf.d/openai.json', json_encode($this->config));
    }
}
