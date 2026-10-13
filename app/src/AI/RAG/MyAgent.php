<?php

declare(strict_types=1);

namespace App\AI\RAG;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

class MyAgent extends Agent
{
    private static ?AIProviderFactoryInterface $factory = null;

    public static function initialize(AIProviderFactoryInterface $factory): void
    {
        self::$factory = $factory;
    }

    protected function provider(): AIProviderInterface
    {
        assert(self::$factory instanceof AIProviderFactoryInterface);

        return self::$factory->create();
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['You are a friendly AI Agent created with Neuron AI framework.'],
        );
    }

    /**
     * @return ToolInterface[]|ToolkitInterface[]
     */
    protected function tools(): array
    {
        return [];
    }

    /**
     * Attach middleware to nodes.
     */
    protected function middleware(): array
    {
        return [
            // ToolNode::class => [],
        ];
    }
}
