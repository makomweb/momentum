<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;

interface ReportProgressInterface
{
    public function onStart(?Activity $last): void;

    public function onActivity(Activity $activity): void;

    public function onException(Activity $activity, \Throwable $ex): void;

    public function onFinish(): void;
}
