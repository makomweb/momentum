<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;

interface ReportProgressInterface
{
    public function reportStart(?Activity $resumeAfter): void;

    public function skip(Activity $current): void;

    public function report(Activity $current): void;

    public function reportFinish(): void;
}
