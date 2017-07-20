<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/28/2017
 * Time: 9:49 PM
 */

namespace CDCMastery\Models\CdcData;


class AfscHelpers
{
    /**
     * @param Afsc[] $afscs
     * @param bool $keyed
     * @return array
     */
    public static function listNames(array $afscs, bool $keyed = false): array
    {
        $afscs = array_values($afscs);
        $c = count($afscs);

        $nameList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($afscs[$i])) {
                continue;
            }

            if (!$afscs[$i] instanceof Afsc) {
                continue;
            }

            if (!$keyed) {
                $nameList[] = $afscs[$i]->getName();
                continue;
            }

            $nameList[$afscs[$i]->getUuid()] = $afscs[$i]->getName();
        }

        return $nameList;
    }

    /**
     * @param Afsc[] $afscs
     * @return string[]
     */
    public static function listUuid(array $afscs): array
    {
        $afscs = array_values($afscs);
        $c = count($afscs);

        $uuidList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($afscs[$i])) {
                continue;
            }

            if (!$afscs[$i] instanceof Afsc) {
                continue;
            }

            $uuidList[] = $afscs[$i]->getUuid();
        }

        return $uuidList;
    }
}