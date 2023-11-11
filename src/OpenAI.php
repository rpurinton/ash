<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $config = [];
    private $client = null;

    public function __construct()
    {
        //$this->config = json_decode(file_get_contents(__DIR__ . '/conf.d/openai.json'), true);
    }
}
