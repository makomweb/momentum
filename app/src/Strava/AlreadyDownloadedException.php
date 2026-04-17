<?php

declare(strict_types=1);

namespace App\Strava;

final class AlreadyDownloadedException extends StravaException
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
