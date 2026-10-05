<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\AI\OllamaConfig;
use NeuronAI\Providers\Ollama\Ollama;

final readonly class OllamaProviderFactory implements AIProviderFactoryInterface
{
    public function __construct(private OllamaConfig $config)
    {
    }

    public function create(): Ollama
    {
        return new Ollama(
            url: $this->config->getApiEndpoint(),
            model: $this->config->model,
        );
    }
}
