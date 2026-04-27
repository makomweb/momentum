<?php

declare(strict_types=1);

namespace App\Momentum;

enum Zone: string
{
    case EASY = 'easy';
    case MODERATE = 'moderate';
    case TEMPO = 'tempo';
    case LONG_INTERVAL = 'long-interval';
    case SHORT_INTERVAL = 'short-interval';
}
