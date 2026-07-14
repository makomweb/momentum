<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Assert\Assert;
use App\Strava\API\MissingTokenException;
use App\Strava\API\Token;
use App\Strava\API\TokenCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class DebugTokenCache implements TokenCacheInterface
{
    public function __construct(
        private string $directory,
        private SerializerInterface $serializer,
    ) {
    }

    public function getToken(int $stravaUserId): Token
    {
        $path = $this->directory.DIRECTORY_SEPARATOR.sprintf('%d', $stravaUserId);

        var_dump(['context' => __METHOD__, 'path' => $path]);

        Assert::that(file_exists($path), new MissingTokenException($stravaUserId));

        $data = file_get_contents($path);

        var_dump(['content' => $data]);

        return $this->serializer->deserialize($data, Token::class, 'json');
    }

    public function saveToken(Token $token): void
    {
        $data = $this->serializer->serialize($token, 'json', []);

        $path = $this->directory.DIRECTORY_SEPARATOR.sprintf('%d', $token->athlete->id);

        var_dump(['context' => __METHOD__, 'path' => $path]);

        $result = file_put_contents($path, $data);

        Assert::that(false !== $result, sprintf('Unable to cache token for %d', $token->athlete->id));
    }
}
