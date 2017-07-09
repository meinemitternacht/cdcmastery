<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:56 PM
 */

namespace CDCMastery\Models\Bases;


class BaseHelpers
{
    /**
     * @param Base[] $bases
     * @return string[]
     */
    public static function listNamesKeyed(array $bases): array
    {
        $bases = array_values($bases);
        $c = count($bases);

        $nameList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($bases[$i])) {
                continue;
            }

            if (!$bases[$i] instanceof Base) {
                continue;
            }

            $nameList[$bases[$i]->getUuid()] = $bases[$i]->getName();
        }

        return $nameList;
    }
}