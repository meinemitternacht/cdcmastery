<?php

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Tests extends Stats
{
    /**
     * @var TestStats
     */
    private $test_stats;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        TestStats $test_stats
    ) {
        parent::__construct($logger, $twig, $session);

        $this->test_stats = $test_stats;
    }

    /**
     * @return Response
     */
    public function show_stats_tests_home(): Response
    {
        return $this->redirect('/stats/tests/last-seven');
    }

    /**
     * @param int $type
     * @return Response
     * @throws JsonException
     */
    private function show_tests_timespan(int $type): Response
    {
        $data = [
            'title' => 'All Tests'
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->averageLastSevenDays()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->countLastSevenDays()
                    )
                ]);
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->averageByMonth()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->countByMonth()
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->averageByWeek()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->countByWeek()
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->averageByYear()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->countByYear()
                    ),
                ]);
                break;
            default:
                $this->flash()->add(MessageTypes::WARNING,
                                    'Invalid time period');

                return $this->redirect('/stats/tests');
        }

        if ($data['averages'] === '') {
            $this->flash()->add(MessageTypes::INFO,
                                "No tests have been taken in the last seven days");

            return $this->redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/tests.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_tests_timespan_last_seven(): Response
    {
        return $this->show_tests_timespan(self::TYPE_LAST_SEVEN);
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_tests_timespan_month(): Response
    {
        return $this->show_tests_timespan(self::TYPE_MONTH);
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_tests_timespan_week(): Response
    {
        return $this->show_tests_timespan(self::TYPE_WEEK);
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_tests_timespan_year(): Response
    {
        return $this->show_tests_timespan(self::TYPE_YEAR);
    }
}