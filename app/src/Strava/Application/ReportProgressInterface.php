<?php

declare(strict_types=1);

namespace App\Strava\Application;

use App\Strava\API\Activity;

interface ReportProgressInterface
{
    public function report(Activity $activity): void;

    public function reportException(Activity $activity, \Throwable $ex): void;
}
