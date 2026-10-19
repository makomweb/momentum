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

final class NeuronAdapter implements NeuronAdapterInterface
{
    private ?int $stravaUserId = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function setStravaUserId(int $stravaUserId): void
    {
        $this->stravaUserId = $stravaUserId;
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
        assert(is_int($this->stravaUserId), sprintf('Call %s::setStravaId() first!', __CLASS__));

        return new FileVectorStore(
            directory: __DIR__,
            name: sprintf('strava_activities_%d', $this->stravaUserId)
        );
    }
}
