<?php
declare(strict_types=1);


namespace CDCMastery\Models\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringFunctions extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('strtr', [$this, 'strtr']),
            new TwigFilter('strtr_right', [$this, 'strtr_right']),
        ];
    }

    public function strtr(?string $v, int $max_len): ?string
    {
        if ($v === null) {
            return null;
        }

        $len = strlen($v);

        if ($len < $max_len) {
            return $v;
        }

        if ($len + 3 > $max_len) {
            return substr($v, 0, $max_len - 3) . '...';
        }

        return substr($v, 0, $max_len) . '...';
    }

    public function strtr_right(?string $v, int $max_len): ?string
    {
        if ($v === null) {
            return null;
        }

        $len = strlen($v);

        if ($len < $max_len) {
            return $v;
        }

        if ($len + 3 > $max_len) {
            return '...' . substr($v, -($max_len - 3));
        }

        return '...' . substr($v, -$max_len);
    }
}