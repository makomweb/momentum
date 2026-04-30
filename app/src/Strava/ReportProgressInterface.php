<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;

interface ReportProgressInterface
{
    public function onStart(?Activity $resumeAfter): void;

    public function onSkip(Activity $current): void;

    public function onSuccess(Activity $current): void;

    public function onFinish(): void;
}
