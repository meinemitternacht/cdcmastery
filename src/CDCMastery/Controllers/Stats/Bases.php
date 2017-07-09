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
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Bases\BaseHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;

class Bases extends Stats
{
    const TYPE_LAST_SEVEN = 0;
    const TYPE_MONTH = 1;
    const TYPE_WEEK = 2;
    const TYPE_YEAR = 3;

    /**
     * @return string
     */
    public function renderBasesStatsHome(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function renderBasesTests(): string
    {
        $baseCollection = $this->container->get(BaseCollection::class);
        $baseList = BaseHelpers::listNamesKeyed($baseCollection->fetchAll());

        $statsBases = $this->container->get(\CDCMastery\Models\Statistics\Bases::class);

        $baseNames = BaseHelpers::listNamesKeyed($baseCollection->fetchAll());

        $data = [
            'title' => 'All Bases',
            'subTitle' => 'Tests Overall',
            'averages' => StatisticsHelpers::formatGraphDataBasesOverall(
                $statsBases->averagesOverall(),
                $baseNames
            ),
            'counts' => StatisticsHelpers::formatGraphDataBasesOverall(
                $statsBases->countsOverall(),
                $baseNames
            ),
            'baseList' => $baseList
        ];

        return $this->render(
            'public/stats/bases/overall.html.twig',
            $data
        );
    }

    /**
     * @param string $baseUuid
     * @return string
     */
    public function renderBaseTests(string $baseUuid): string
    {
        return AppHelpers::redirect('/stats/bases/' . $baseUuid . '/tests/last-seven');
    }

    /**
     * @param string $baseUuid
     * @param int $type
     * @return string
     */
    private function renderBaseTestsTimeSegment(string $baseUuid, int $type): string
    {
        $baseCollection = $this->container->get(BaseCollection::class);
        $baseList = BaseHelpers::listNamesKeyed($baseCollection->fetchAll());

        $base = $baseCollection->fetch($baseUuid);

        if (empty($base->getUuid())) {
            Messages::add(
                Messages::INFO,
                'The specified base does not exist'
            );

            AppHelpers::redirect('/stats/bases');
        }

        $statsTests = $this->container->get(Tests::class);

        $data = [
            'uuid' => $base->getUuid(),
            'title' => $base->getName(),
            'baseList' => $baseList
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseAverageLastSevenDays($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseCountLastSevenDays($base)
                    )
                ]);
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseAverageByMonth($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseCountByMonth($base)
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseAverageByWeek($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseCountByWeek($base)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseAverageByYear($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->baseCountByYear($base)
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                AppHelpers::redirect('/stats/bases');
                break;
        }

        if (empty($data['averages'])) {
            Messages::add(
                Messages::INFO,
                "No tests have been taken at {$base->getName()} in the last seven days"
            );

            AppHelpers::redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/bases/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $baseUuid
     * @return string
     */
    public function renderBaseTestsLastSeven(string $baseUuid): string
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $baseUuid
     * @return string
     */
    public function renderBaseTestsByMonth(string $baseUuid): string
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $baseUuid
     * @return string
     */
    public function renderBaseTestsByWeek(string $baseUuid): string
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $baseUuid
     * @return string
     */
    public function renderBaseTestsByYear(string $baseUuid): string
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_YEAR);
    }
}