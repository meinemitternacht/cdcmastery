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
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Afscs extends Stats
{
    /**
     * @var AfscCollection
     */
    private $afscCollection;

    /**
     * @var Tests
     */
    private $tests;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        AfscCollection $afscCollection,
        Tests $tests
    ) {
        parent::__construct($logger, $twig);

        $this->afscCollection = $afscCollection;
        $this->tests = $tests;
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscsStatsHome(): Response
    {
        $afscList = AfscHelpers::listNames(
            $this->afscCollection->fetchAll(
                [],
                AfscCollection::SHOW_FOUO | AfscCollection::SHOW_OBSOLETE
            ),
            true
        );

        return $this->render(
            'public/stats/afscs/home.html.twig', [
                'afscList' => $afscList,
            ]
        );
    }

    /**
     * @param string $afscUuid
     * @return Response
     */
    public function renderAfscTests(string $afscUuid): Response
    {
        return AppHelpers::redirect('/stats/afscs/' . $afscUuid . '/tests/month');
    }

    /**
     * @param string $afscUuid
     * @param int $type
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderAfscTestsTimeSegment(string $afscUuid, int $type): Response
    {
        $afscList = AfscHelpers::listNames(
            $this->afscCollection->fetchAll(
                [],
                PHP_INT_MAX
            ),
            true
        );

        $afsc = $this->afscCollection->fetch($afscUuid);

        if ($afsc->getUuid() === '') {
            Messages::add(
                Messages::INFO,
                'The specified AFSC does not exist'
            );

            return AppHelpers::redirect('/stats/afscs');
        }

        $data = [
            'uuid' => $afsc->getUuid(),
            'title' => $afsc->getName(),
            'afscList' => $afscList,
        ];

        switch ($type) {
            case self::TYPE_LAST_SEVEN:
                $data = array_merge($data, [
                    'subTitle' => 'Tests Last Seven Days',
                    'period' => 'last-seven',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscAverageLastSevenDays($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscCountLastSevenDays($afsc)
                    ),
                ]);

                if ($data['averages'] === '') {
                    Messages::add(
                        Messages::INFO,
                        "No tests have been taken with {$afsc->getName()} in the last seven days"
                    );

                    return AppHelpers::redirect('/stats/afscs/tests');
                }
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscAverageByMonth($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscCountByMonth($afsc)
                    ),
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscAverageByWeek($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscCountByWeek($afsc)
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscAverageByYear($afsc)
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->afscCountByYear($afsc)
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                return AppHelpers::redirect('/stats/afscs');
                break;
        }

        if ($data['averages'] === '') {
            Messages::add(
                Messages::INFO,
                "No tests have been taken with {$afsc->getName()}"
            );

            return AppHelpers::redirect('/stats/afscs/tests');
        }

        return $this->render(
            'public/stats/afscs/tests.html.twig',
            $data
        );
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscTestsLastSeven(string $afscUuid): Response
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_LAST_SEVEN);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscTestsByMonth(string $afscUuid): Response
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_MONTH);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscTestsByWeek(string $afscUuid): Response
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_WEEK);
    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscTestsByYear(string $afscUuid): Response
    {
        return $this->renderAfscTestsTimeSegment($afscUuid, self::TYPE_YEAR);
    }
}