<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MathExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('max', $this->max(...)),
            new TwigFilter('min', $this->min(...)),
        ];
    }

    public function max(int|float $value, int|float $bound): int|float
    {
        return max($value, $bound);
    }

    public function min(int|float $value, int|float $bound): int|float
    {
        return min($value, $bound);
    }
}