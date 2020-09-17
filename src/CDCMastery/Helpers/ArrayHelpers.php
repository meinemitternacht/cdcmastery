<?php


namespace CDCMastery\Helpers;


use Exception;

class ArrayHelpers
{
    /**
     * @param array $arr
     * @throws Exception
     */
    public static function shuffle(array &$arr): void
    {
        uasort($arr, static function ($a, $b): int {
            return random_int(-1, 1);
        });
    }
}