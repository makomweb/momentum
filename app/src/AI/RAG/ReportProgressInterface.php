<?php

declare(strict_types=1);

namespace App\AI\RAG;

interface ReportProgressInterface
{
    public function start(int $count): void;

    public function progress(int $amount = 1): void;

    public function finish(): void;
}
