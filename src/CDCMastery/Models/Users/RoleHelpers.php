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
    public static function listNames(array $roles): array
    {
        $names = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            $names[$role->getUuid()] = $role->getName();
        }

        return $names;
    }
}