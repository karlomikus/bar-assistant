<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Calculator;

enum CalculatorBlockType: string
{
    case Input = 'input';
    case Eval = 'eval';
}
