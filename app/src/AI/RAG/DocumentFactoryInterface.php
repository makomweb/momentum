<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Momentum\AthleteInterface;
use App\Strava\API\Activity;
use NeuronAI\RAG\Document;

interface DocumentFactoryInterface
{
    /**
     * @param Activity[] $activities
     *
     * @return \Generator<Document>
     */
    public function yieldDocuments(AthleteInterface $athlete, array $activities, ReportProgressInterface $report): \Generator;
}
