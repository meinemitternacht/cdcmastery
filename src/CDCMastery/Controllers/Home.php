<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\UserCollection;
use DateTime;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Home extends RootController
{
    private const NUM_COMPLETE_TESTS_DISPLAY = 5;

    /**
     * @var AuthHelpers
     */
    private $auth_helpers;

    /**
     * @var TestStats
     */
    private $test_stats;

    /**
     * @var UserCollection
     */
    private $users;

    /**
     * @var TestCollection
     */
    private $tests;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        TestStats $test_stats,
        UserCollection $users,
        TestCollection $tests
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->test_stats = $test_stats;
        $this->users = $users;
        $this->tests = $tests;
    }

    /**
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function show_home(): Response
    {
        return $this->auth_helpers->assert_logged_in()
            ? $this->show_home_authorized()
            : $this->show_home_anonymous();
    }

    /**
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function show_home_anonymous(): Response
    {
        $data = [
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->averageLastSevenDays()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->countLastSevenDays()),
            ],
            'yearStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->averageByYear()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->countByYear()),
            ],
        ];

        return $this->render('public/home/home.html.twig',
                             $data);
    }

    /**
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function show_home_authorized(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
        $tests = $this->tests->fetchAllByUser($user);

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                return $b->getTimeStarted()->format('U') <=> $a->getTimeStarted()->format('U');
            }
        );

        $tests_complete = [];
        $tests_incomplete = [];
        /** @var Test $test */
        foreach ($tests as $test) {
            if (($test->getUuid() ?? '') === '') {
                continue;
            }

            $started = $test->getTimeStarted();
            $completed = $test->getTimeCompleted();

            if ($test->getTimeCompleted() !== null) {
                if (count($tests_complete) === self::NUM_COMPLETE_TESTS_DISPLAY) {
                    continue;
                }

                $tests_complete[] = [
                    'uuid' => $test->getUuid(),
                    'score' => $test->getScore(),
                    'afsc' => implode(', ', AfscHelpers::listNames($test->getAfscs())),
                    'questions' => $test->getNumQuestions(),
                    'time' => [
                        'started' => ($started !== null)
                            ? $started->format(DateTimeHelpers::DT_FMT_SHORT)
                            : '',
                        'completed' => ($completed !== null)
                            ? $completed->format(DateTimeHelpers::DT_FMT_SHORT)
                            : '',
                    ],
                ];

                continue;
            }

            $tests_incomplete[] = [
                'uuid' => $test->getUuid(),
                'afsc' => implode(', ', AfscHelpers::listNames($test->getAfscs())),
                'answered' => $test->getNumAnswered(),
                'questions' => $test->getNumQuestions(),
                'time' => [
                    'started' => ($started !== null)
                        ? $started->format(DateTimeHelpers::DT_FMT_SHORT)
                        : '',
                ],
            ];
        }

        $daysAgo_7 = DateTimeHelpers::xDaysAgo(7);
        $daysAgo_30 = DateTimeHelpers::xDaysAgo(30);
        $daysAgo_90 = DateTimeHelpers::xDaysAgo(90);
        $now = new DateTime();

        $data = [
            'generalStats' => [
                'avg' => [
                    'overall' => $this->test_stats->userAverageOverall($user),
                    'lastSeven' => $this->test_stats->userAverageBetween($user, $daysAgo_7, $now),
                    'lastThirty' => $this->test_stats->userAverageBetween($user, $daysAgo_30, $now),
                    'lastNinety' => $this->test_stats->userAverageBetween($user, $daysAgo_90, $now),
                ],
                'count' => [
                    'overall' => $this->test_stats->userCountOverall($user),
                    'lastSeven' => $this->test_stats->userCountBetween($user, $daysAgo_7, $now),
                    'lastThirty' => $this->test_stats->userCountBetween($user, $daysAgo_30, $now),
                    'lastNinety' => $this->test_stats->userCountBetween($user, $daysAgo_90, $now),
                ],
            ],
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->userAverageLastSevenDays($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->userCountLastSevenDays($user)),
            ],
            'monthStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->userAverageByMonth($user)),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->userCountByMonth($user)),
            ],
            'tests' => [
                'complete' => $tests_complete,
                'incomplete' => $tests_incomplete,
            ],
        ];

        return $this->render('home/home.html.twig',
                             $data);
    }
}