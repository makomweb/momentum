<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\AI\Config;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

final readonly class NeuronAdapter implements NeuronAdapterInterface
{
    public function __construct(private Config $config)
    {
    }

    public function createAIProvider(): AIProviderInterface
    {
        return new Ollama(
            url: $this->config->getApiEndpoint(),
            model: $this->config->llm,
        );
    }

    public function createEmbeddingsProvider(): EmbeddingsProviderInterface
    {
        return new OllamaEmbeddingsProvider(
            url: $this->config->getApiEndpoint(),
            model: $this->config->embeddingsModel,
        );
    }

    public function createVectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(
            directory: __DIR__,
            name: 'demo'
        );
    }
}
