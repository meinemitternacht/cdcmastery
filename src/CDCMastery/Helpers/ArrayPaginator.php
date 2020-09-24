<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 2:30 PM
 */

namespace CDCMastery\Helpers;


class ArrayPaginator
{
    private const LINK_TEXT_PAGE = 0;
    private const LINK_TEXT_FIRST = 1;
    private const LINK_TEXT_PREV = 2;
    private const LINK_TEXT_NEXT = 3;
    private const LINK_TEXT_LAST = 4;

    public const VAR_START = 'start';
    public const VAR_ROWS = 'rows';
    public const VAR_SORT = 'sort';
    public const VAR_DIRECTION = 'dir';

    public const DEFAULT_START = 0;
    public const DEFAULT_ROWS = 20;

    /**
     * @param array $data
     * @param int $start
     * @param int $count
     * @return array
     */
    public static function paginate(array $data, int $start, int $count): array
    {
        $dataCount = count($data);

        if ($start < 0) {
            $start = 0;
        }

        if ($start > $dataCount) {
            $start = 0;
        }

        if (($start + $count) > $dataCount) {
            $count = ($dataCount - $start);
        }

        $rowOffset = $start * $count;

        return array_slice($data, $rowOffset, $count, true);
    }

    /**
     * @param array $data
     * @param int $count
     * @return int
     */
    public static function calcNumPagesData(array $data, int $count): int
    {
        return ceil(
            count($data) / $count
        ) - 1;
    }

    /**
     * @param int $numRows
     * @param int $count
     * @return int
     */
    public static function calcNumPagesNoData(int $numRows, int $count): int
    {
        return ceil(
            $numRows / $count
        ) - 1;
    }

    public static function buildLinks(
        string $path,
        int $curPage,
        int $numPages,
        int $rows,
        int $totalRows,
        ?string $sort = null,
        ?string $dir = null
    ): string {
        if ($numPages <= 1) {
            return '';
        }

        if ($curPage > $numPages) {
            return '';
        }

        $showFirst = true;
        $showPrevious = true;
        $showNext = true;
        $showLast = true;

        $firstPage = (($curPage - 5) < 0)
            ? 0
            : $curPage - 5;
        $lastPage = ($curPage + 5) > $numPages
            ? $numPages
            : $curPage + 5;

        if ($curPage === 0) {
            $showFirst = false;
            $showPrevious = false;
            $firstPage = 0;
            $lastPage = ($numPages > ($firstPage + 9))
                ? $firstPage + 9
                : $numPages;
            goto out_return;
        }

        if ($curPage === $numPages) {
            $showNext = false;
            $showLast = false;
            $firstPage = (($numPages - 10) < 0)
                ? 0
                : $numPages - 10;
            $lastPage = $numPages;
            goto out_return;
        }

        out_return:
        $htmlParts = [];

        $htmlParts[] = '<ul class="pagination pagination-sm cdc-pagination">';

        if ($showFirst) {
            $htmlParts[] = self::createHtmlLinkPart(
                $path,
                $curPage,
                0,
                $rows,
                self::LINK_TEXT_FIRST,
                $sort,
                $dir
            );
        }

        if ($showPrevious) {
            $htmlParts[] = self::createHtmlLinkPart(
                $path,
                $curPage,
                ($curPage - 1),
                $rows,
                self::LINK_TEXT_PREV,
                $sort,
                $dir
            );
        }

        $i = $firstPage;
        while ($i <= $lastPage) {
            $htmlParts[] = self::createHtmlLinkPart(
                $path,
                $curPage,
                $i,
                $rows,
                self::LINK_TEXT_PAGE,
                $sort,
                $dir
            );

            $i++;
        }

        if ($showNext) {
            $htmlParts[] = self::createHtmlLinkPart(
                $path,
                $curPage,
                ($curPage + 1),
                $rows,
                self::LINK_TEXT_NEXT,
                $sort,
                $dir
            );
        }

        if ($showLast) {
            $htmlParts[] = self::createHtmlLinkPart(
                $path,
                $curPage,
                $numPages,
                $rows,
                self::LINK_TEXT_LAST,
                $sort,
                $dir
            );
        }

        $htmlParts[] = '<li class="disabled"><a href="#">' . number_format($totalRows) . ' records</a></li>';

        $htmlParts[] = '</ul>';

        return implode(
            PHP_EOL,
            $htmlParts
        );
    }

    private static function createHtmlLinkPart(
        string $path,
        int $curPage,
        int $pageNum,
        int $rows,
        int $textType = self::LINK_TEXT_PAGE,
        ?string $sort = null,
        ?string $dir = null
    ): string {
        $class = '';

        switch ($textType) {
            case self::LINK_TEXT_FIRST:
                $text = '&laquo;';
                break;
            case self::LINK_TEXT_PREV:
                $text = '&lt;';
                break;
            case self::LINK_TEXT_NEXT:
                $text = '&gt;';
                break;
            case self::LINK_TEXT_LAST:
                $text = '&raquo;';
                break;
            case self::LINK_TEXT_PAGE:
            default:
                $text = $pageNum + 1;

                $class = ($pageNum) === $curPage
                    ? ' class="active"'
                    : '';
                break;
        }

        $sortDir = '';
        if (!empty($sort) && !empty($dir)) {
            $sortDir = '&sort=' . $sort . '&dir=' . $dir;
        }

        return '<li' .
                $class .
                '><a href="' .
                $path .
                '?' .
                self::VAR_START .
                '=' .
                $pageNum .
                '&' .
                self::VAR_ROWS .
                '=' .
                $rows .
                $sortDir .
                '">' .
                $text .
                '</a></li>';
    }
}