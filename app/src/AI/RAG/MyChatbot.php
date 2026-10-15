<?php

declare(strict_types=1);

namespace App\AI\RAG;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

class MyChatbot extends RAG
{
    private static ?NeuronAdapterInterface $adapter = null;

    public static function initialize(NeuronAdapterInterface $adapter): void
    {
        self::$adapter = $adapter;
    }

    protected function provider(): AIProviderInterface
    {
        assert(self::$adapter instanceof NeuronAdapterInterface, sprintf('Call %s::initialize() first!', __CLASS__));

        return self::$adapter->createAIProvider();
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        assert(self::$adapter instanceof NeuronAdapterInterface, sprintf('Call %s::initialize() first!', __CLASS__));

        return self::$adapter->createEmbeddingsProvider();
    }

    protected function vectorStore(): VectorStoreInterface
    {
        assert(self::$adapter instanceof NeuronAdapterInterface, sprintf('Call %s::initialize() first!', __CLASS__));

        return self::$adapter->createVectorStore();
    }
}
