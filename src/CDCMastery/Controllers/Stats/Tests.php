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
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Tests extends Stats
{
    /**
     * @var \CDCMastery\Models\Statistics\Tests
     */
    private $tests;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        \CDCMastery\Models\Statistics\Tests $tests
    ) {
        parent::__construct($logger, $twig);

        $this->tests = $tests;
    }

    /**
     * @return Response
     */
    public function renderTestsStatsHome(): Response
    {
        return AppHelpers::redirect('/stats/tests/last-seven');
    }

    /**
     * @param int $type
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderTestsByTimeSegment(int $type): Response
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
                        $this->tests->averageLastSevenDays()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->countLastSevenDays()
                    )
                ]);
                break;
            case self::TYPE_MONTH:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Month',
                    'period' => 'month',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->averageByMonth()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->countByMonth()
                    )
                ]);
                break;
            case self::TYPE_WEEK:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Week',
                    'period' => 'week',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->averageByWeek()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->countByWeek()
                    ),
                ]);
                break;
            case self::TYPE_YEAR:
                $data = array_merge($data, [
                    'subTitle' => 'Tests By Year',
                    'period' => 'year',
                    'averages' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->averageByYear()
                    ),
                    'counts' => StatisticsHelpers::formatGraphDataTests(
                        $this->tests->countByYear()
                    ),
                ]);
                break;
            default:
                Messages::add(
                    Messages::WARNING,
                    'Invalid time period'
                );

                return AppHelpers::redirect('/stats/tests');
        }

        if ($data['averages'] === '') {
            Messages::add(
                Messages::INFO,
                "No tests have been taken in the last seven days"
            );

            return AppHelpers::redirect('/stats/bases/tests');
        }

        return $this->render(
            'public/stats/tests.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestsLastSeven(): Response
    {
        return $this->renderTestsByTimeSegment(self::TYPE_LAST_SEVEN);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestsByMonth(): Response
    {
        return $this->renderTestsByTimeSegment(self::TYPE_MONTH);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestsByWeek(): Response
    {
        return $this->renderTestsByTimeSegment(self::TYPE_WEEK);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestsByYear(): Response
    {
        return $this->renderTestsByTimeSegment(self::TYPE_YEAR);
    }
}