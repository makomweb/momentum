<?php

declare(strict_types=1);

namespace App\AI;

final readonly class Config implements \Stringable
{
    public function __construct(
        private string $llmProviderEndpoint,
        public string $llm,
        public string $embeddingsModel,
    ) {
        assert(!empty($llmProviderEndpoint));
        assert(!empty($llm));
        assert(!empty($embeddingsModel));
    }

    public function getApiEndpoint(): string
    {
        return $this->getBaseEndpoint().'/api';
    }

    public function getBaseEndpoint(): string
    {
        return rtrim((string) preg_replace('#/api/?$#', '', rtrim($this->llmProviderEndpoint, '/')), '/');
    }

    public function __toString(): string
    {
        return "endpoint = {$this->getApiEndpoint()}, llm = {$this->llm}, embeddings = {$this->embeddingsModel}";
    }
}
