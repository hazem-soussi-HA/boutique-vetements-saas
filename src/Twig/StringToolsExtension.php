<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringToolsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('text_limit', $this->limit(...)),
        ];
    }

    public function limit(?string $value, int $length = 120, string $ellipsis = '…'): string
    {
        $value = trim((string) $value);

        if ('' === $value) {
            return '';
        }

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        $cut = mb_substr($value, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        if (false !== $lastSpace && $lastSpace > $length * 0.6) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B.,;:").$ellipsis;
    }
}