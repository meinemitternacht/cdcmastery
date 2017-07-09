<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;

class Tests extends Stats
{
    const TYPE_LAST_SEVEN = 0;
    const TYPE_MONTH = 1;
    const TYPE_WEEK = 2;
    const TYPE_YEAR = 3;

    /**
     * @return string
     */
    public function renderTestsStatsHome(): string
    {
        return AppHelpers::redirect('/stats/tests/last-seven');
    }

    /**
     * @param int $type
     * @return string
     */
    private function renderTestsByTimeSegment(int $type): string
    {
        $statsTests = $this->container->get(\CDCMastery\Models\Statistics\Tests::class);

        $data = [
            'title' => 'All Tests'
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->averageLastSevenDays()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->countLastSevenDays()
                    )
                ]);
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->averageByMonth()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->countByMonth()
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->averageByWeek()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->countByWeek()
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->averageByYear()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->countByYear()
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                AppHelpers::redirect('/stats/tests');
                break;
        }

        if (empty($data['averages'])) {
            Messages::add(
                Messages::INFO,
                "No tests have been taken in the last seven days"
            );

            AppHelpers::redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/tests.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    public function renderTestsLastSeven(): string
    {
        return $this->renderTestsByTimeSegment(self::TYPE_LAST_SEVEN);
    }

    /**
     * @return string
     */
    public function renderTestsByMonth(): string
    {
        return $this->renderTestsByTimeSegment(self::TYPE_MONTH);
    }

    /**
     * @return string
     */
    public function renderTestsByWeek(): string
    {
        return $this->renderTestsByTimeSegment(self::TYPE_WEEK);
    }

    /**
     * @return string
     */
    public function renderTestsByYear(): string
    {
        return $this->renderTestsByTimeSegment(self::TYPE_YEAR);
    }
}