<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 3:12 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\CdcData\AfscHelpers;

class TestHelpers
{
    /**
     * @param Test[] $tests
     * @return array
     */
    public static function formatHtml(array $tests): array
    {
        if (empty($tests)) {
            return [];
        }

        $newTests = [];
        /** @var Test $test */
        foreach ($tests as $test) {
            if (empty($test->getUuid())) {
                continue;
            }

            $started = $test->getTimeStarted();
            $completed = $test->getTimeCompleted();

            if ($test->getTimeCompleted() !== null) {
                $newTests[] = [
                    'uuid' => $test->getUuid(),
                    'score' => $test->getScore(),
                    'afsc' => implode(
                        ', ',
                        AfscHelpers::listNames($test->getAfscs())
                    ),
                    'answered' => $test->getNumAnswered(),
                    'questions' => $test->getNumQuestions(),
                    'time' => [
                        'started' => ($started !== null)
                            ? $started->format(DateTimeHelpers::DT_FMT_SHORT)
                            : '',
                        'completed' => ($completed !== null)
                            ? $completed->format(DateTimeHelpers::DT_FMT_SHORT)
                            : ''
                    ]
                ];
                continue;
            }
        }

        return $newTests;
    }
}