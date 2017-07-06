<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/4/2017
 * Time: 7:39 PM
 */

namespace CDCMastery\Models\Statistics;


class StatisticsHelpers
{
    public static function formatGraphDataTests(array $data): string
    {
        $newData = [];
        $i = 0;
        foreach ($data as $date => $val) {
            $newData[] = [
                'x' => $i,
                'y' => $val,
                'label' => $date
            ];

            $i++;
        }

        return json_encode($newData);
    }
}