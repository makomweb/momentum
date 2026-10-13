<?php

declare(strict_types=1);

namespace App\AI\RAG;

use NeuronAI\Providers\AIProviderInterface;

interface AIProviderFactoryInterface
{
    public function create(): AIProviderInterface;
}
