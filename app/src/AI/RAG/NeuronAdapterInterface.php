<?php

declare(strict_types=1);

namespace App\AI\RAG;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

interface NeuronAdapterInterface
{
    public function createAIProvider(): AIProviderInterface;

    public function createEmbeddingsProvider(): EmbeddingsProviderInterface;

    public function createVectorStore(): VectorStoreInterface;
}
