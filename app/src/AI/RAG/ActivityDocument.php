<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Strava\API\Activity;
use NeuronAI\RAG\Document;

final readonly class ActivityDocument
{
    public function __construct(
        private int $stravaUserId,
        private Activity $activity,
    ) {
    }

    public function getNeuronDocument(): Document
    {
        return new Document('')
            ->setSourceType(get_class($this->activity))
            ->setSourceName($this->activity->getName())
            ->setMetadata([
                'strava_user_id' => $this->stravaUserId,
                ...array_diff_key($this->activity->data, array_flip(['id'])),
            ]);
    }
}
