<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;

    public function __construct(private $ash)
    {
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
    }
}
