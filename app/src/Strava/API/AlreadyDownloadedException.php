<?php

declare(strict_types=1);

namespace App\Strava\API;

final class AlreadyDownloadedException extends ApiException
{
    public function __construct(string $filePath)
    {
        parent::__construct(
            sprintf('File "%s" already downloaded!', $filePath),
            0,
            null
        );
    }
}
