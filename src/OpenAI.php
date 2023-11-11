<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;
    private $models = [];
    private $history = [];

    public function __construct(private $ash)
    {
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
        $models = $this->client->models()->list();
        foreach ($models as $model) if (substr($model['id'], 0, 3) == 'gpt') $this->models[] = $model['id'];
        print_r($this->models);
    }
}
