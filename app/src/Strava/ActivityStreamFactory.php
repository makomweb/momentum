<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\ActivityStream;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ActivityStreamFactory
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function fromJsonFile(string $filePath): ActivityStream
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        return $this->fromJsonString($content);
    }

    public function fromJsonString(string $json): ActivityStream
    {
        return $this->serializer->deserialize($json, ActivityStream::class, 'json');
    }
}
