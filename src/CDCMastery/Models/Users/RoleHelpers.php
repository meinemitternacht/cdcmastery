<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:56 PM
 */

namespace CDCMastery\Models\Users;


class RoleHelpers
{
    /**
     * @param Role[] $roles
     * @return string[]
     */
    public static function listNamesKeyed(array $roles): array
    {
        $roles = array_values($roles);
        $c = count($roles);

        $nameList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($roles[$i])) {
                continue;
            }

            if (!$roles[$i] instanceof Role) {
                continue;
            }

            $nameList[$roles[$i]->getUuid()] = $roles[$i]->getName();
        }

        return $nameList;
    }
}