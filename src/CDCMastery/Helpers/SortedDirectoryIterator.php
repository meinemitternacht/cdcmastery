<?php
declare(strict_types=1);


namespace CDCMastery\Helpers;


use ArrayIterator;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SortedDirectoryIterator implements IteratorAggregate
{
    private ArrayIterator $iterator;

    public function __construct(string $path)
    {
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $_iterator = new RecursiveIteratorIterator($dir_iterator);

        $it_arr = iterator_to_array($_iterator, false);
        usort($it_arr, static function (SplFileInfo $a, SplFileInfo $b): int {
            return strnatcasecmp($a->getPathname(), $b->getPathname());
        });

        $this->iterator = new ArrayIterator($it_arr);
    }

    /** @inheritDoc */
    public function getIterator()
    {
        return $this->iterator;
    }
}