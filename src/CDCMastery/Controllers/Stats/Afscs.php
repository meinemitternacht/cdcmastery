<?php

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Afscs extends Stats
{
    private AfscCollection $afscs;
    private TestStats $test_stats;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AfscCollection $afscs,
        TestStats $test_stats
    ) {
        parent::__construct($logger, $twig, $session);

        $this->afscs = $afscs;
        $this->test_stats = $test_stats;
    }

    /**
     * @return Response
     */
    public function show_stats_afsc_home(): Response
    {
        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_FOUO | AfscCollection::SHOW_OBSOLETE);

        return $this->render(
            'public/stats/afscs/home.html.twig',
            [
                'afscs' => $afscs,
            ]
        );
    }

    /**
     * @param string $afscUuid
     * @return Response
     */
    public function show_stats_afsc_tests(string $afscUuid): Response
    {
        return $this->redirect('/stats/afscs/' . $afscUuid . '/tests/month');
    }

    /**
     * @param string $afscUuid
     * @param int $type
     * @return Response
     * @throws JsonException
     */
    private function show_stats_afsc_tests_timespan(string $afscUuid, int $type): Response
    {
        $afsc = $this->afscs->fetch($afscUuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::INFO,
                                'The specified AFSC does not exist');

            return $this->redirect('/stats/afscs');
        }

        $data = [
            'uuid' => $afsc->getUuid(),
            'title' => $afsc->getName(),
            'afscs' => $this->afscs->fetchAll(AfscCollection::SHOW_ALL),
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscAverageLastSevenDays($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscCountLastSevenDays($afsc)
                    ),
                ]);

                if ($data['averages'] === '') {
                    $this->flash()->add(
                        MessageTypes::INFO,
                        "No tests have been taken with {$afsc->getName()} in the last seven days"
                    );

                    return $this->redirect('/stats/afscs');
                }
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscAverageByMonth($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscCountByMonth($afsc)
                    ),
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscAverageByWeek($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscCountByWeek($afsc)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscAverageByYear($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->afscCountByYear($afsc)
                    ),
                ]);
                break;
            default:
                $this->flash()->add(
                    MessageTypes::WARNING,
                    'Invalid time period'
                );

                return $this->redirect('/stats/afscs');
        }

        if ($data['averages'] === '') {
            $this->flash()->add(
                MessageTypes::INFO,
                "No tests have been taken with {$afsc->getName()}"
            );

            return $this->redirect('/stats/afscs');
        }

        return $this->render(
            'public/stats/afscs/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws JsonException
     */
    public function show_afsc_stats_tests_last_seven(string $afscUuid): Response
    {
        return $this->show_stats_afsc_tests_timespan($afscUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws JsonException
     */
    public function show_afsc_stats_tests_month(string $afscUuid): Response
    {
        return $this->show_stats_afsc_tests_timespan($afscUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws JsonException
     */
    public function show_afsc_stats_tests_week(string $afscUuid): Response
    {
        return $this->show_stats_afsc_tests_timespan($afscUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws JsonException
     */
    public function show_afsc_stats_tests_year(string $afscUuid): Response
    {
        return $this->show_stats_afsc_tests_timespan($afscUuid, self::TYPE_YEAR);
    }
}