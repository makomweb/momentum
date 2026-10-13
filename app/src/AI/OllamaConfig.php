<?php

declare(strict_types=1);

namespace App\AI;

final readonly class OllamaConfig implements \Stringable
{
    public function __construct(private string $endpoint, public string $model)
    {
        assert(!empty($endpoint));
        assert(!empty($model));
    }

    public function getApiEndpoint(): string
    {
        return $this->getBaseEndpoint().'/api';
    }

    public function getBaseEndpoint(): string
    {
        return rtrim((string) preg_replace('#/api/?$#', '', rtrim($this->endpoint, '/')), '/');
    }

    public function __toString(): string
    {
        return "endpoint = {$this->getApiEndpoint()}, model = {$this->model}";
    }
}
