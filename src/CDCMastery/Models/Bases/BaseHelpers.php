<?php
declare(strict_types=1);

namespace CDCMastery\Models\Bases;


class BaseHelpers
{
    /**
     * @param Base[] $bases
     * @return string[]
     */
    public static function listNames(array $bases): array
    {
        $names = [];
        foreach ($bases as $base) {
            if (!$base instanceof Base) {
                continue;
            }

            $names[$base->getUuid()] = $base->getName();
        }

        return $names;
    }
}