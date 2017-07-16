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

class Home extends RootController
{
    /**
     * @return string
     */
    public function renderFrontPage(): string
    {
        return AuthHelpers::isLoggedIn()
            ? $this->renderFrontPageAuth()
            : $this->renderFrontPageAnon();
    }

    /**
     * @return string
     */
    private function renderFrontPageAnon(): string
    {
        $statsTests = $this->container->get(Tests::class);

        $data = [
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($statsTests->averageLastSevenDays()),
                'count' => StatisticsHelpers::formatGraphDataTests($statsTests->countLastSevenDays())
            ],
            'yearStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($statsTests->averageByYear()),
                'count' => StatisticsHelpers::formatGraphDataTests($statsTests->countByYear())
            ]
        ];

        return $this->render(
            'public/home/home.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    private function renderFrontPageAuth(): string
    {
        $statsTests = $this->container->get(Tests::class);

        $userCollection = $this->container->get(UserCollection::class);
        $user = $userCollection->fetch(SessionHelpers::getUserUuid());

        $testCollection = $this->container->get(TestCollection::class);
        $tests = $testCollection->fetchAllByUser($user);

        uasort(
            $tests,
            function ($a, $b) {
                /** @var Test $a */
                /** @var Test $b */
                return $a->getTimeStarted()->format('U') <=> $b->getTimeStarted()->format('U');
            }
        );

        $tests = array_slice(
            array_reverse(
                $tests,
                true
            ),
            0,
            10,
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
                    'overall' => $statsTests->userAverageOverall($user),
                    'lastSeven' => $statsTests->userAverageBetween($user, $daysAgo_7, new \DateTime()),
                    'lastThirty' => $statsTests->userAverageBetween($user, $daysAgo_30, new \DateTime()),
                    'lastNinety' => $statsTests->userAverageBetween($user, $daysAgo_90, new \DateTime())
                ],
                'count' => [
                    'overall' => $statsTests->userCountOverall($user),
                    'lastSeven' => $statsTests->userCountBetween($user, $daysAgo_7, new \DateTime()),
                    'lastThirty' => $statsTests->userCountBetween($user, $daysAgo_30, new \DateTime()),
                    'lastNinety' => $statsTests->userCountBetween($user, $daysAgo_90, new \DateTime())
                ]
            ],
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($statsTests->userAverageLastSevenDays($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($statsTests->userCountLastSevenDays($user))
            ],
            'monthStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($statsTests->userAverageByMonth($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($statsTests->userCountByMonth($user))
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