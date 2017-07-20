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
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;

class Afscs extends Stats
{
    const TYPE_LAST_SEVEN = 0;
    const TYPE_MONTH = 1;
    const TYPE_WEEK = 2;
    const TYPE_YEAR = 3;

    /**
     * @return string
     */
    public function renderAfscsStatsHome(): string
    {
        $afscCollection = $this->container->get(AfscCollection::class);
        $afscList = AfscHelpers::listNames(
            $afscCollection->fetchAll(
                [],
                AfscCollection::SHOW_FOUO | AfscCollection::SHOW_OBSOLETE
            ),
            true
        );

        return $this->render(
            'public/stats/afscs/home.html.twig', [
                'afscList' => $afscList
            ]
        );
    }

    /**
     * @param string $afscUuid
     * @return string
     */
    public function renderAfscTests(string $afscUuid): string
    {
        return AppHelpers::redirect('/stats/afscs/' . $afscUuid . '/tests/month');
    }

    /**
     * @param string $afscUuid
     * @param int $type
     * @return string
     */
    private function renderAfscTestsTimeSegment(string $afscUuid, int $type): string
    {
        $afscCollection = $this->container->get(AfscCollection::class);
        $afscList = AfscHelpers::listNames(
            $afscCollection->fetchAll(
                [],
                PHP_INT_MAX
            ),
            true
        );

        $afsc = $afscCollection->fetch($afscUuid);

        if (empty($afsc->getUuid())) {
            Messages::add(
                Messages::INFO,
                'The specified AFSC does not exist'
            );

            AppHelpers::redirect('/stats/afscs');
        }

        $statsTests = $this->container->get(Tests::class);

        $data = [
            'uuid' => $afsc->getUuid(),
            'title' => $afsc->getName(),
            'afscList' => $afscList
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscAverageLastSevenDays($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscCountLastSevenDays($afsc)
                    )
                ]);

                if (empty($data['averages'])) {
                    Messages::add(
                        Messages::INFO,
                        "No tests have been taken with {$afsc->getName()} in the last seven days"
                    );

                    AppHelpers::redirect('/stats/afscs/tests');
                }
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscAverageByMonth($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscCountByMonth($afsc)
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscAverageByWeek($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscCountByWeek($afsc)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscAverageByYear($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $statsTests->afscCountByYear($afsc)
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                AppHelpers::redirect('/stats/afscs');
                break;
        }

        if (empty($data['averages'])) {
            Messages::add(
                Messages::INFO,
                "No tests have been taken with {$afsc->getName()}"
            );

            AppHelpers::redirect('/stats/afscs/tests');
        }

        return $this->render(
            'public/stats/afscs/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $afscUuid
     * @return string
     */
    public function renderAfscTestsLastSeven(string $afscUuid): string
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $afscUuid
     * @return string
     */
    public function renderAfscTestsByMonth(string $afscUuid): string
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $afscUuid
     * @return string
     */
    public function renderAfscTestsByWeek(string $afscUuid): string
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $afscUuid
     * @return string
     */
    public function renderAfscTestsByYear(string $afscUuid): string
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_YEAR);
    }
}