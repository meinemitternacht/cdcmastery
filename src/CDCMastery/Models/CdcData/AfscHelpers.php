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
     * @return array
     */
    public static function listNames(array $afscs): array
    {
        $names = [];
        foreach ($afscs as $afsc) {
            if (!$afsc instanceof Afsc) {
                continue;
            }

            $names[$afsc->getUuid()] = $afsc->getName();
        }

        return $names;
    }

    /**
     * @param Afsc[] $afscs
     * @return string[]
     */
    public static function listUuid(array $afscs): array
    {
        $uuids = [];
        foreach ($afscs as $afsc) {
            if (!$afsc instanceof Afsc) {
                continue;
            }

            $uuids[] = $afsc->getUuid();
        }

        return $uuids;
    }
}