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

        return empty($newData)
            ? ''
            : json_encode($newData);
    }

    public static function formatGraphDataBasesOverall(array $data, array $names): string
    {
        $newData = [];
        $i = 0;
        foreach ($data as $uuid => $datum) {
            if (is_array($datum)) {
                $newData[] = [
                    'label' => $names[$uuid] ?? '',
                    'x' => $i,
                    'y' => $datum['tAvg'] ?? $datum['tCount'] ?? 0
                ];

                $i++;
                continue;
            }

            $newData[] = [
                'label' => $names[$uuid] ?? '',
                'x' => $i,
                'y' => $datum ?? 0
            ];

            $i++;
        }

        return empty($newData)
            ? ''
            : json_encode($newData);
    }

    public static function formatGraphDataUsers(array $data, array $names): string
    {
        $newData = [];
        $i = 0;
        foreach ($data as $uuid => $datum) {
            if (is_array($datum)) {
                $newData[] = [
                    'label' => $names[$uuid] ?? '',
                    'x' => $i,
                    'y' => $datum['tAvg'] ?? $datum['tCount'] ?? 0
                ];

                $i++;
                continue;
            }

            $newData[] = [
                'label' => $names[$uuid] ?? '',
                'x' => $i,
                'y' => $datum ?? 0
            ];

            $i++;
        }

        return empty($newData)
            ? ''
            : json_encode($newData);
    }
}