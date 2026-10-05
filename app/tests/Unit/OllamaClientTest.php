<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\AI\LLM\OllamaClient;
use App\AI\OllamaConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OllamaClientTest extends TestCase
{
    #[Test]
    public function ask_uses_api_endpoint_when_configured_without_api_suffix(): void
    {
        $client = new OllamaClient(
            new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
                self::assertSame('POST', $method);
                self::assertSame('http://host.docker.internal:11434/api/generate', $url);
                assert(is_string($options['body'] ?? null));
                $body = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
                self::assertIsArray($body);
                self::assertSame('Was ist PHP?', $body['prompt'] ?? null);

                return new MockResponse('{"response":"PHP is a programming language."}');
            }),
            new OllamaConfig('http://host.docker.internal:11434', 'llama3:latest'),
        );

        self::assertSame('PHP is a programming language.', $client->ask('Was ist PHP?'));
    }

    #[Test]
    public function ask_keeps_single_api_suffix_when_configured_with_api_suffix(): void
    {
        $client = new OllamaClient(
            new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
                self::assertSame('POST', $method);
                self::assertSame('http://host.docker.internal:11434/api/generate', $url);
                assert(is_string($options['body'] ?? null));
                $body = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
                self::assertIsArray($body);
                self::assertSame('Was ist PHP?', $body['prompt'] ?? null);

                return new MockResponse('{"response":"PHP is a programming language."}');
            }),
            new OllamaConfig('http://host.docker.internal:11434/api', 'llama3:latest'),
        );

        self::assertSame('PHP is a programming language.', $client->ask('Was ist PHP?'));
    }

    #[Test]
    public function availability_check_uses_base_endpoint(): void
    {
        $client = new OllamaClient(
            new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
                self::assertSame('HEAD', $method);
                self::assertSame('http://host.docker.internal:11434/', $url);
                self::assertSame(5.0, $options['timeout']);

                return new MockResponse('', ['http_code' => 200]);
            }),
            new OllamaConfig('http://host.docker.internal:11434/api', 'llama3:latest'),
        );

        self::assertTrue($client->isAvailable());
    }
}
