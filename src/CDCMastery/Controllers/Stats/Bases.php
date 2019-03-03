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
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Bases extends Stats
{
    /**
     * @var BaseCollection
     */
    private $baseCollection;

    /**
     * @var \CDCMastery\Models\Statistics\Bases
     */
    private $bases;

    /**
     * @var Tests
     */
    private $tests;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        BaseCollection $baseCollection,
        \CDCMastery\Models\Statistics\Bases $bases,
        Tests $tests
    ) {
        parent::__construct($logger, $twig);

        $this->baseCollection = $baseCollection;
        $this->bases = $bases;
        $this->tests = $tests;
    }

    /**
     * @return Response
     */
    public function renderBasesStatsHome(): Response
    {
        return AppHelpers::redirect('/');
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderBasesTests(): Response
    {
        $baseList = BaseHelpers::listNamesKeyed($this->baseCollection->fetchAll());
        $baseNames = BaseHelpers::listNamesKeyed($this->baseCollection->fetchAll());

        $data = [
            'title' => 'All Bases',
            'subTitle' => 'Tests Overall',
            'averages' => StatisticsHelpers::formatGraphDataBasesOverall(
                $this->bases->averagesOverall(),
                $baseNames
            ),
            'counts' => StatisticsHelpers::formatGraphDataBasesOverall(
                $this->bases->countsOverall(),
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
    public function renderBaseTests(string $baseUuid): Response
    {
        return AppHelpers::redirect("/stats/bases/{$baseUuid}/tests/last-seven");
    }

    /**
     * @param string $baseUuid
     * @param int $type
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderBaseTestsTimeSegment(string $baseUuid, int $type): Response
    {
        $baseList = BaseHelpers::listNamesKeyed($this->baseCollection->fetchAll());

        $base = $this->baseCollection->fetch($baseUuid);

        if ($base->getUuid() === '') {
            Messages::add(
                Messages::INFO,
                'The specified base does not exist'
            );

            return AppHelpers::redirect('/stats/bases');
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
                        $this->tests->baseAverageLastSevenDays($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseCountLastSevenDays($base)
                    )
                ]);

                if (empty($data['averages'])) {
                    Messages::add(
                        Messages::INFO,
                        "No tests have been taken at {$base->getName()} in the last seven days"
                    );

                    return AppHelpers::redirect('/stats/bases/tests');
                }
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseAverageByMonth($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseCountByMonth($base)
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseAverageByWeek($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseCountByWeek($base)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseAverageByYear($base)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->baseCountByYear($base)
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                return AppHelpers::redirect('/stats/bases');
        }

        if ($data['averages'] === '') {
            Messages::add(
                Messages::INFO,
                "No tests have been taken at {$base->getName()}"
            );

            return AppHelpers::redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/bases/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderBaseTestsLastSeven(string $baseUuid): Response
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderBaseTestsByMonth(string $baseUuid): Response
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderBaseTestsByWeek(string $baseUuid): Response
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $baseUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderBaseTestsByYear(string $baseUuid): Response
    {
        return $this->renderBaseTestsTimeSegment($baseUuid, self::TYPE_YEAR);
    }
}