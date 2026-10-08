<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms a comma-separated string into an array of trimmed strings.
 *
 * @implements DataTransformerInterface<string, array<int, string>>
 */
class CommaListTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        if (\is_string($value)) {
            return $value;
        }

        return implode(', ', array_filter((array) $value, static fn ($item) => '' !== trim((string) $item)));
    }

    public function reverseTransform(mixed $value): array
    {
        if (null === $value) {
            return [];
        }

        if (\is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        $parts = explode(',', (string) $value);
        $parts = array_map(static fn (string $part): string => trim($part), $parts);

        return array_values(array_filter($parts, static fn (string $part): bool => '' !== $part));
    }
}