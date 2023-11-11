<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $openai = null;

    public function __construct()
    {
        $api_key = $this->get_api_key();
    }

    private function get_api_key()
    {
        return "";
    }
}
