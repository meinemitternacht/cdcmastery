<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 11:32 PM
 */

namespace CDCMastery\Models\CdcData;


class QuestionHelpers
{
    /**
     * @param Question[] $questions
     * @return string[]
     */
    public static function listUuid(array $questions): array
    {
        $c = count($questions);

        $uuidList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            if (!$questions[$i] instanceof Question) {
                continue;
            }

            $uuidList[] = $questions[$i]->getUuid();
        }

        return $uuidList;
    }
}