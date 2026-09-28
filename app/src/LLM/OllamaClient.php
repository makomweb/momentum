<?php

namespace App\LLM;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaClient
{
    private const TIMEOUT = 300; // seconds (5 minutes)

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $endpoint,
        private string $model,
    ) {
    }

    /**
     * Send a prompt to Ollama and get a response.
     *
     * @param string     $prompt      The prompt/question to send
     * @param float      $temperature Temperature for creativity (0-1, higher = more creative)
     * @param int|null   $topK        Optional top K parameter
     * @param float|null $topP        Optional nucleus sampling parameter
     *
     * @return string The model's response
     */
    public function ask(
        string $prompt,
        float $temperature = 0.7,
        ?int $topK = null,
        ?float $topP = null,
    ): string {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'temperature' => $temperature,
        ];

        if (null !== $topK) {
            $payload['top_k'] = $topK;
        }

        if (null !== $topP) {
            $payload['top_p'] = $topP;
        }

        $response = $this->httpClient->request(
            'POST',
            $this->endpoint.'/api/generate', [
                'json' => $payload,
                'timeout' => self::TIMEOUT,
            ]
        );

        $data = $response->toArray();

        return $data['response'] ?? '';
    }

    /**
     * Stream a prompt response for real-time output (callback-based).
     *
     * @param string   $prompt      The prompt/question to send
     * @param callable $onChunk     Callback function receiving each chunk of text
     * @param float    $temperature Temperature for creativity (0-1, higher = more creative)
     */
    public function askStream(
        string $prompt,
        callable $onChunk,
        float $temperature = 0.7,
    ): void {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => true,
            'temperature' => $temperature,
        ];

        $response = $this->httpClient->request(
            'POST',
            $this->endpoint.'/api/generate',
            [
                'json' => $payload,
                'timeout' => self::TIMEOUT,
            ]
        );

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isLast()) {
                break;
            }

            $data = json_decode($chunk->getContent(), true);
            if (isset($data['response'])) {
                $onChunk($data['response']);
            }
        }
    }

    /**
     * Get list of available models.
     *
     * @return array List of available model names
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->endpoint.'/api/tags',
                ['timeout' => 10]
            );

            $data = $response->toArray();
            $models = [];

            foreach ($data['models'] ?? [] as $model) {
                $models[] = $model['name'];
            }

            return $models;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Check if Ollama is available and running.
     */
    public function isAvailable(): bool
    {
        try {
            $this->httpClient->request(
                'HEAD',
                $this->endpoint,
                ['timeout' => 5]
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Set the model to use.
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the current model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
