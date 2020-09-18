<?php


namespace CDCMastery\Models\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringFunctions extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('strtr', [$this, 'strtr']),
        ];
    }

    public function getFunctions(): array
    {
        return [];
    }

    public function strtr(string $v, int $max_len): string
    {
        $len = strlen($v);

        if ($len < $max_len) {
            return $v;
        }

        if ($len + 3 > $max_len) {
            return substr($v, 0, $max_len - 3) . '...';
        }

        return substr($v, 0, $max_len) . '...';
    }
}