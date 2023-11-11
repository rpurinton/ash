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
        foreach ($models as $model) {
            print_r($model);
            $model_id = $model['id'];
            $model_family = substr($model_id, 0, 3);
            if ($model_family == 'gpt') $this->models[] = $model_id;
        }
        print_r($this->models);
    }
}
