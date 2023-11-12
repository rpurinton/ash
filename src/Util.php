<?php

namespace Rpurinton\Ash;

class Util
{
    private $encoder = null;

    public function __construct()
    {
        $this->encoder = new \TikToken\Encoder();
    }

    public function tokenCount(string $input): int
    {
        try {
            $count = count($this->encoder->encode($input));
        } catch (\Exception $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Error $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Throwable $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        }
        return $count;
    }

    public function markdownToEscapeCodes($text, $color_support = true)
    {
        $text = str_replace("\\e", "\e", $text);
        $text = str_replace("```", "", $text);
        if ($color_support) {
            $text = preg_replace("/(?<!\S)\*\*(.*?)\*\*(?!\S)/", "\e[1m$1\e[0m", $text);
            $text = preg_replace("/(?<!\S)\*(.*?)\*(?!\S)/", "\e[3m$1\e[0m", $text);
            $text = preg_replace("/(?<!\S)\_(.*?)\_(?!\S)/", "\e[3m$1\e[0m", $text);
            $text = preg_replace("/(?<!\S)\~(.*?)\~(?!\S)/", "\e[9m$1\e[0m", $text);
            $text = preg_replace("/(?<!\S)\`(.*?)\`(?!\S)/", "\e[7m$1\e[0m", $text);
            return $text;
        } else {
            $text = preg_replace("/(?<!\S)\*\*(.*?)\*\*(?!\S)/", "$1", $text);
            $text = preg_replace("/(?<!\S)\*(.*?)\*(?!\S)/", "$1", $text);
            $text = preg_replace("/(?<!\S)\_(.*?)\_(?!\S)/", "$1", $text);
            $text = preg_replace("/(?<!\S)\~(.*?)\~(?!\S)/", "$1", $text);
            $text = preg_replace("/(?<!\S)\`(.*?)\`(?!\S)/", "$1", $text);
            return $text;
        }
    }
}
