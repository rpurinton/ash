<?php

namespace Rpurinton\Ash;

class ModelPicker
{

    public function __construct(private $openai)
    {
    }

    public function selectModel($force = false)
    {
        // Check if openai_model is set in the config
        if (!$force && isset($this->openai->ash->config->config['openaiModel'])) {
            $model_id = $this->openai->ash->config->config['openaiModel'];
            // Check if the model is in the list of models
            if (in_array($model_id, $this->openai->models)) {
                $this->openai->model = $model_id;
                return;
            }
        }

        // Prompt the user to select a model
        while (true) {
            $model_count = count($this->openai->models);
            $prompt = "Please select an OpenAI GPT model to use:\n";
            for ($i = 0; $i < $model_count; $i++) {
                $prompt .= "[$i] {$this->openai->models[$i]}\n";
            }
            $prompt .= "Enter the number of the model to use (default: 0 ({$this->openai->models[0]})): ";
            $model_index = readline($prompt);
            if ($model_index == "") $model_index = 0;

            // Check if the selected model is valid
            if (isset($this->openai->models[$model_index])) {
                $this->openai->model = $this->openai->models[$model_index];
                $this->openai->ash->config->setOpenAIModel($this->openai->model);
                return;
            }

            echo "Invalid model selected. Please try again.\n";
        }
    }

    public function selectMaxTokens($force = false)
    {

        if (!$force && isset($this->openai->ash->config->config['openaiTokens'])) {
            $this->openai->maxTokens = $this->openai->ash->config->config['openaiTokens'];
            return;
        }

        while (true) {
            $prompt = "Please select the maximum tokens you want use for any single request (default: 4096, range [2048-131072]): ";
            $max_tokens = readline($prompt);
            if ($max_tokens == "") $max_tokens = 4096;

            if (is_numeric($max_tokens) && $max_tokens >= 2048 && $max_tokens <= 131072) {
                $this->openai->maxTokens = $max_tokens;
                $this->openai->ash->config->setOpenAITokens($this->openai->maxTokens);
                return;
            }

            echo "Invalid max tokens value. Please try again.\n";
        }
    }
}
