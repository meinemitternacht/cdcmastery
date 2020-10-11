<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 4:02 PM
 */

namespace CDCMastery\Models\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CreateSortLink extends AbstractExtension
{
    public const DIR_ASC = "ASC";
    public const DIR_DESC = "DESC";

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sortlink', [$this, 'createSortLink']),
        ];
    }

    /**
     * @param string $column
     * @param string $text
     * @param array $currentSort
     * @return string
     */
    public function createSortLink(string $column, string $text, array $currentSort): string
    {
        $curCol = $currentSort['col'] ?? '';
        $curDir = $currentSort['dir'] ?? 'DESC';

        $newDirection = (strtolower($column) === strtolower($curCol))
            ? ($curDir === self::DIR_ASC)
                ? self::DIR_DESC
                : self::DIR_ASC
            : self::DIR_ASC;

        return '<a href="?sort=' . $column . '&dir=' . $newDirection . '">' . $text . '</a>';
    }
}
