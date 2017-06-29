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
     * @return string[]
     */
    public static function listUuid(array $afscs): array
    {
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