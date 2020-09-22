<?php

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Bases\BaseHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\Bases\BasesGrouped;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Bases extends Stats
{
    private BaseCollection $baseCollection;
    private BasesGrouped $base_stats;
    private TestStats $test_stats;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        BaseCollection $baseCollection,
        BasesGrouped $bases,
        TestStats $tests
    ) {
        parent::__construct($logger, $twig, $session);

        $this->baseCollection = $baseCollection;
        $this->base_stats = $bases;
        $this->test_stats = $tests;
    }

    /**
     * @return Response
     */
    public function show_bases_home(): Response
    {
        return $this->redirect('/');
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_bases_tests(): Response
    {
        $baseList = BaseHelpers::listNames($this->baseCollection->fetchAll());
        $baseNames = BaseHelpers::listNames($this->baseCollection->fetchAll());

        $data = [
            'title' => 'All Bases',
            'subTitle' => 'Tests Overall',
            'averages' => StatisticsHelpers::formatGraphDataUsersBases(
                $this->base_stats->averagesOverall(),
                $baseNames
            ),
            'counts' => StatisticsHelpers::formatGraphDataUsersBases(
                $this->base_stats->countsOverall(),
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
     * @return Response
     */
    public function show_bases_tests_timespan_home(string $baseUuid): Response
    {
        return $this->redirect("/stats/bases/{$baseUuid}/tests/last-seven");
    }

    /**
     * @param string $baseUuid
     * @param int $type
     * @return Response
     * @throws JsonException
     */
    private function show_base_tests_timespan(string $baseUuid, int $type): Response
    {
        $baseList = BaseHelpers::listNames($this->baseCollection->fetchAll());

        $base = $this->baseCollection->fetch($baseUuid);

        if ($base === null || ($base->getUuid() ?? '') === '') {
            $this->flash()->add(MessageTypes::INFO,
                                'The specified base does not exist');

            return $this->redirect('/stats/bases');
        }

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
                        $this->test_stats->baseAverageLastSevenDays($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseCountLastSevenDays($base)
                    )
                ]);

                if (empty($data['averages'])) {
                    $this->flash()->add(
                        MessageTypes::INFO,
                        "No tests have been taken at {$base->getName()} in the last seven days"
                    );

                    return $this->redirect('/stats/bases/tests');
                }
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseAverageByMonth($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseCountByMonth($base)
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseAverageByWeek($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseCountByWeek($base)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseAverageByYear($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->test_stats->baseCountByYear($base)
                    ),
                ]);
                break;
            default:
                $this->flash()->add(
                    MessageTypes::WARNING,
                    'Invalid time period'
                );

                return $this->redirect('/stats/bases');
        }

        if ($data['averages'] === '') {
            $this->flash()->add(
                MessageTypes::INFO,
                "No tests have been taken at {$base->getName()}"
            );

            return $this->redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/bases/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws JsonException
     */
    public function show_base_tests_last_seven(string $baseUuid): Response
    {
        return $this->show_base_tests_timespan($baseUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws JsonException
     */
    public function show_base_tests_month(string $baseUuid): Response
    {
        return $this->show_base_tests_timespan($baseUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws JsonException
     */
    public function show_base_tests_week(string $baseUuid): Response
    {
        return $this->show_base_tests_timespan($baseUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws JsonException
     */
    public function show_base_tests_year(string $baseUuid): Response
    {
        return $this->show_base_tests_timespan($baseUuid, self::TYPE_YEAR);
    }
}