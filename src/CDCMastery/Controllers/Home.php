<?php
declare(strict_types=1);
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
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\UserCollection;
use DateTime;
use DateTimeZone;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Home extends RootController
{
    private const NUM_COMPLETE_TESTS_DISPLAY = 5;

    private AuthHelpers $auth_helpers;
    private TestStats $test_stats;
    private UserCollection $users;
    private TestCollection $tests;

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
     * @throws JsonException
     */
    public function show_home(): Response
    {
        return $this->auth_helpers->assert_logged_in()
            ? $this->show_home_authorized()
            : $this->show_home_anonymous();
    }

    /**
     * @return Response
     * @throws JsonException
     */
    private function show_home_anonymous(): Response
    {
        $n_users = $this->users->count();
        $data = [
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->averageLastSevenDays()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->countLastSevenDays()),
            ],
            'yearStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests($this->test_stats->averageByYear()),
                'count' => StatisticsHelpers::formatGraphDataTests($this->test_stats->countByYear()),
            ],
            'num_users' => $n_users - ($n_users % 1000),
        ];

        return $this->render('public/home/home.html.twig',
                             $data);
    }

    /**
     * @return Response
     * @throws JsonException
     */
    private function show_home_authorized(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $tests = $this->tests->fetchAllByUser($user);

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                return $b->getTimeStarted() <=> $a->getTimeStarted();
            }
        );

        /* @todo this is terrible.  use the test objects or TestHelpers::formatHtml() */
        $tests_complete = [];
        $tests_incomplete = [];
        /** @var Test $test */
        foreach ($tests as $test) {
            if (($test->getUuid() ?? '') === '') {
                continue;
            }

            $started = $test->getTimeStarted();
            $completed = $test->getTimeCompleted();
            $updated = $test->getLastUpdated();

            if ($test->getTimeCompleted() !== null) {
                if (count($tests_complete) === self::NUM_COMPLETE_TESTS_DISPLAY) {
                    continue;
                }

                $tests_complete[] = [
                    'uuid' => $test->getUuid(),
                    'type' => $test->getType(),
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
                        'updated' => ($updated !== null)
                            ? $updated->format(DateTimeHelpers::DT_FMT_SHORT)
                            : '',
                    ],
                ];

                continue;
            }

            $tests_incomplete[] = [
                'uuid' => $test->getUuid(),
                'type' => $test->getType(),
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

        $daysAgo_7 = DateTimeHelpers::x_days_ago(7);
        $daysAgo_30 = DateTimeHelpers::x_days_ago(30);
        $daysAgo_90 = DateTimeHelpers::x_days_ago(90);
        $now = new DateTime();

        /* @todo create a form and database table to manage these */
        $news_flash_items = [];
        $news_flash_items[] = [
            'message' => <<<MSG
All tests for 2W171 between 12/20/20 and 1/15/21 have been removed due to the presence of incomplete question data.
More than 100 self-test questions were inadvertently added to the database under the testing category for this AFSC.
These questions have been moved to their own flash card category and are viewable to the users associated with that 
AFSC.
MSG,
            'created' => DateTime::createFromFormat('Y-m-d H:i:s', '2021-01-16 21:00:00', new DateTimeZone('EST')),
            'expires' => DateTime::createFromFormat('Y-m-d H:i:s', '2021-02-15 00:00:01', new DateTimeZone('EST')),
        ];

        $news_flash_items = array_filter($news_flash_items, static function (array $v) use ($now): bool {
            /** @var DateTime $expires */
            $expires = $v[ 'expires' ];
            return $expires->getTimestamp() > $now->getTimestamp();
        });

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
            'news_flash_items' => $news_flash_items,
        ];

        return $this->render('home/home.html.twig',
                             $data);
    }
}
