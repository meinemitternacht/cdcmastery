<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 4:02 PM
 */

namespace CDCMastery\Models\Twig;


class CreateSortLink extends \Twig_Extension
{
    const DIR_ASC = "ASC";
    const DIR_DESC = "DESC";

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_Function('sortlink', [$this, 'createSortLink'])
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
            : self::DIR_DESC;

        return '<a href="?sort=' . $column . '&dir=' . $newDirection . '">' . $text . '</a>';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'CreateSortLink';
    }
}