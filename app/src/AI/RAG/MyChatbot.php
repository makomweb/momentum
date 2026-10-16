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

    /**
     * @param iterable<ActivityDocument> $documents
     */
    public static function ingest(iterable $documents): void
    {
        $documents = is_array($documents) ? $documents : iterator_to_array($documents);
        self::make()->addDocuments(
            array_map(
                static fn (ActivityDocument $doc) => $doc->getNeuronDocument(),
                $documents
            )
        );
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
