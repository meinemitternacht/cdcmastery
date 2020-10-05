<?php
declare(strict_types=1);

namespace CDCMastery\Models\Statistics;


use JsonException;
use function count;

class StatisticsHelpers
{
    /**
     * @param array $data
     * @return string
     * @throws JsonException
     */
    public static function formatGraphDataTests(array $data): string
    {
        $newData = [];
        $i = 0;
        foreach ($data as $date => $val) {
            $newData[] = [
                'x' => $i,
                'y' => $val,
                'label' => $date,
            ];

            $i++;
        }

        return count($newData) === 0
            ? ''
            : json_encode($newData, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array $data
     * @param array $names
     * @return string
     * @throws JsonException
     */
    public static function formatGraphDataUsersBases(array $data, array $names): string
    {
        $newData = [];
        $i = 0;
        foreach ($data as $uuid => $datum) {
            if (is_array($datum)) {
                $newData[] = [
                    'label' => $names[ $uuid ] ?? '',
                    'x' => $i,
                    'y' => $datum[ 'tAvg' ] ?? $datum[ 'tCount' ] ?? 0,
                ];

                $i++;
                continue;
            }

            $newData[] = [
                'label' => $names[ $uuid] ?? '',
                'x' => $i,
                'y' => $datum ?? 0
            ];

            $i++;
        }

        return count($newData) === 0
            ? ''
            : json_encode($newData, JSON_THROW_ON_ERROR);
    }
}