<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAI
{
    private $client = null;
    private $models = [];
    private $model = null;
    private $history = [];

    public function __construct(private $ash)
    {
        $this->client = \OpenAI::client($this->ash->config['openai_api_key']);
        $models = $this->client->models()->list()->data;
        foreach ($models as $model) if (substr($model->id, 0, 3) == 'gpt') $this->models[] = $model->id;
        $this->select_model();
    }

    private function select_model()
    {
        // Check if openai_model is set in the config
        if (isset($this->ash->config['openai_model'])) {
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
            $prompt = "Please select an OpenAI GPT model to use:\n";
            for ($i = 0; $i < $model_count; $i++) {
                $prompt .= "[$i] {$this->models[$i]}\n";
            }
            $prompt .= "Enter the number of the model to use: ";
            $model_index = (int) readline($prompt);

            // Check if the selected model is valid
            if (isset($this->models[$model_index])) {
                $this->model = $this->models[$model_index];
                $this->ash->config['openai_model'] = $this->model;
                $this->ash->save_config();
                return;
            }

            echo "Invalid model selected. Please try again.\n";
        }
    }
}
