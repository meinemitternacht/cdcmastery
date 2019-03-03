<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Home extends RootController
{
    /**
     * @var Tests
     */
    private $tests;

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @var TestCollection
     */
    private $testCollection;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        Tests $tests,
        UserCollection $userCollection,
        TestCollection $testCollection
    ) {
        parent::__construct($logger, $twig);

        $this->tests = $tests;
        $this->userCollection = $userCollection;
        $this->testCollection = $testCollection;
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderFrontPage(): Response
    {
        return AuthHelpers::isLoggedIn()
            ? $this->renderFrontPageAuth()
            : $this->renderFrontPageAnon();
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderFrontPageAnon(): Response
    {
        $data = [
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->tests->averageLastSevenDays()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->tests->countLastSevenDays())
            ],
            'yearStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->tests->averageByYear()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->tests->countByYear())
            ]
        ];

        return $this->render(
            'public/home/home.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Exception
     */
    private function renderFrontPageAuth(): Response
    {
        $user = $this->userCollection->fetch(SessionHelpers::getUserUuid());
        $tests = $this->testCollection->fetchAllByUser($user);

        uasort(
            $tests,
            function ($a, $b) {
                /** @var Test $a */
                /** @var Test $b */
                return $a->getTimeStarted()->format('U') <=> $b->getTimeStarted()->format('U');
            }
        );

        $tests = array_reverse(
            $tests,
            true
        );

        $testsComplete = [];
        $testsIncomplete = [];

        /** @var Test $test */
        foreach ($tests as $test) {
            if (empty($test->getUuid())) {
                continue;
            }

            $started = $test->getTimeStarted();
            $completed = $test->getTimeCompleted();

            if ($test->getTimeCompleted() !== null) {
                if (count($testsComplete) === 5) {
                    continue;
                }

                $testsComplete[] = [
                    'uuid' => $test->getUuid(),
                    'score' => $test->getScore(),
                    'afsc' => implode(
                        ', ',
                        AfscHelpers::listNames($test->getAfscs())
                    ),
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

            $testsIncomplete[] = [
                'uuid' => $test->getUuid(),
                'afsc' => implode(
                    ', ',
                    AfscHelpers::listNames($test->getAfscs())
                ),
                'answered' => $test->getNumAnswered(),
                'questions' => $test->getNumQuestions(),
                'time' => [
                    'started' => ($started !== null)
                        ? $started->format(DateTimeHelpers::DT_FMT_SHORT)
                        : ''
                ]
            ];
        }

        $daysAgo_7 = DateTimeHelpers::xDaysAgo(7);
        $daysAgo_30 = DateTimeHelpers::xDaysAgo(30);
        $daysAgo_90 = DateTimeHelpers::xDaysAgo(90);

        $data = [
            'generalStats' => [
                'avg' => [
                    'overall' => $this->tests->userAverageOverall($user),
                    'lastSeven' => $this->tests->userAverageBetween($user, $daysAgo_7, new \DateTime()),
                    'lastThirty' => $this->tests->userAverageBetween($user, $daysAgo_30, new \DateTime()),
                    'lastNinety' => $this->tests->userAverageBetween($user, $daysAgo_90, new \DateTime())
                ],
                'count' => [
                    'overall' => $this->tests->userCountOverall($user),
                    'lastSeven' => $this->tests->userCountBetween($user, $daysAgo_7, new \DateTime()),
                    'lastThirty' => $this->tests->userCountBetween($user, $daysAgo_30, new \DateTime()),
                    'lastNinety' => $this->tests->userCountBetween($user, $daysAgo_90, new \DateTime())
                ]
            ],
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->tests->userAverageLastSevenDays($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($this->tests->userCountLastSevenDays($user))
            ],
            'monthStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->tests->userAverageByMonth($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($this->tests->userCountByMonth($user))
            ],
            'tests' => [
                'complete' => $testsComplete,
                'incomplete' => $testsIncomplete
            ]
        ];

        return $this->render(
            'home/home.html.twig',
            $data
        );
    }
}