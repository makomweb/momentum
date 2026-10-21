<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Strava\API\Activity;

interface ReportProgressInterface
{
    public function progress(Activity $activity): void;
}
