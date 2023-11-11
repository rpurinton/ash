<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;
    private $history = [];

    public function __construct(private $ash)
    {
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
        $models = $this->client->models()->list()->toArray();
        print_r($models);
    }
}
