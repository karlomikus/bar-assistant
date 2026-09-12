<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Review;

enum Recommendation: string
{
    case Avoid = 'avoid';
    case Decent = 'decent';
    case Recommend = 'recommend';
}
