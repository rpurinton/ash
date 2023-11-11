<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;
    private $models = [];
    private $model = null;
    private $max_tokens = null;
    private $history = [];

    public function __construct(private $ash)
    {
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
        $models = $this->client->models()->list()->data;
        foreach ($models as $model) if (substr($model->id, 0, 3) == 'gpt') $this->models[] = $model->id;
        $this->select_model();
        $this->select_max_tokens();
    }

    public function select_model($force = false)
    {
        // Check if openai_model is set in the config
        if (!$force && isset($this->ash->config['openai_model'])) {
            $model_id = $this->ash->config['openai_model'];
            // Check if the model is in the list of models
            if (in_array($model_id, $this->models)) {
                $this->model = $model_id;
                return;
            }
        }

        // Prompt the user to select a model
        while (true) {
            $model_count = count($this->models);
            $prompt = "(ash) Please select an OpenAI GPT model to use:\n";
            for ($i = 0; $i < $model_count; $i++) {
                $prompt .= "(ash) [$i] {$this->models[$i]}\n";
            }
            $prompt .= "(ash) Enter the number of the model to use (default: 0 ({$this->models[0]})): ";
            $model_index = readline($prompt);
            if ($model_index == "") $model_index = 0;

            // Check if the selected model is valid
            if (isset($this->models[$model_index])) {
                $this->model = $this->models[$model_index];
                $this->ash->config['openai_model'] = $this->model;
                $this->ash->save_config();
                return;
            }

            echo "(ash) Invalid model selected. Please try again.\n";
        }
    }

    public function select_max_tokens($force = false)
    {
        // Check if openai_max_tokens is set in the config
        if (!$force && isset($this->ash->config['openai_max_tokens'])) {
            $this->max_tokens = $this->ash->config['openai_max_tokens'];
            return;
        }

        // Prompt the user to select a max_tokens value
        while (true) {
            $prompt = "(ash) Please select the maximum tokens you want use for any single request (default: 4096, range [2048-131072]): ";
            $max_tokens = readline($prompt);
            if ($max_tokens == "") $max_tokens = 4096;

            // Check if the selected max_tokens value is valid
            if (is_numeric($max_tokens) && $max_tokens >= 2048 && $max_tokens <= 131072) {
                $this->max_tokens = $max_tokens;
                $this->ash->config['openai_max_tokens'] = $this->max_tokens;
                $this->ash->save_config();
                return;
            }

            echo "(ash) Invalid max_tokens value. Please try again.\n";
        }
    }
}
