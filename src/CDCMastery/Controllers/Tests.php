<?php
declare(strict_types=1);

namespace CDCMastery\Controllers;

use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Tests\QuestionResponse;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHandler;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Tests\TestOptions;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use Exception;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;
use function count;

class Tests extends RootController
{
    private AuthHelpers $auth_helpers;
    private UserCollection $users;
    private TestCollection $tests;
    private Config $config;
    private TestHelpers $test_helpers;
    private UserAfscAssociations $user_afscs;
    private AfscCollection $afscs;
    private AnswerCollection $answers;
    private TestDataHelpers $test_data_helpers;
    private CdcDataCollection $cdc_data;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        TestCollection $tests,
        Config $config,
        TestHelpers $test_helpers,
        UserAfscAssociations $user_afscs,
        AfscCollection $afscs,
        TestDataHelpers $test_data_helpers,
        CdcDataCollection $cdc_data,
        AnswerCollection $answers
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->users = $users;
        $this->tests = $tests;
        $this->config = $config;
        $this->test_helpers = $test_helpers;
        $this->user_afscs = $user_afscs;
        $this->afscs = $afscs;
        $this->test_data_helpers = $test_data_helpers;
        $this->cdc_data = $cdc_data;
        $this->answers = $answers;
    }

    /**
     * @return Response
     */
    public function do_delete_incomplete_tests(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            static function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getTimeCompleted() === null;
            }
        );

        if (!is_array($tests) || count($tests) === 0) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no tests to delete');

            return $this->redirect('/');
        }

        $this->tests->deleteArray(TestHelpers::listUuid($tests));

        $tests_str = implode(', ', array_map(static function (Test $v): string {
            if (!$v->getTimeStarted()) {
                return $v->getUuid();
            }
            return "{$v->getUuid()} [{$v->getTimeStarted()->format(DateTimeHelpers::DT_FMT_DB)}]";
        }, $tests));
        $this->log->info("delete incomplete tests :: {$user->getName()} [{$user->getUuid()}] :: {$tests_str}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'All incomplete tests for your account have been removed successfully');

        return $this->redirect('/tests/new');
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function do_delete_test(string $testUuid): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'You do not exist'
            );

            return $this->redirect('/');
        }

        $test = $this->tests->fetch($testUuid);

        if (!$test) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test does not exist'
            );

            return $this->redirect('/');
        }

        if (!$this->validate_test($test, false)) {
            return $this->redirect('/');
        }

        if ($test->getScore() !== 0.00 || $test->getTimeCompleted() !== null) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You cannot delete a completed test'
            );

            return $this->redirect('/');
        }

        $this->tests->delete($test->getUuid());

        $this->log->info("delete test :: {$user->getName()} [{$user->getUuid()}] :: {$test->getUuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'The specified test has been removed from the database');

        return $this->redirect('/tests/new');
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function do_new_test(): Response
    {
        $params = [
            'afscs',
            'numQuestions',
        ];

        if (!$this->checkParameters($params)) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'Please ensure all required options are selected before beginning a test'
            );

            return $this->redirect('/tests/new');
        }

        $afscs = $this->get('afscs');
        $numQuestions = $this->filter_int_default('numQuestions');

        $n_afscs = count($afscs);
        if (!is_array($afscs) || $n_afscs === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You must select at least one AFSC'
            );

            return $this->redirect('/tests/new');
        }

        $max_afscs = $this->config->get(['testing', 'maxCategories']);
        if ($n_afscs > $max_afscs) {
            $this->flash()->add(
                MessageTypes::WARNING,
                "You can only take a test using a maximum of {$max_afscs} AFSCs"
            );

            return $this->redirect('/tests/new');
        }

        if (!$numQuestions) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The provided amount of questions for the test was invalid'
            );

            return $this->redirect('/tests/new');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        $userAfscCollection = $this->user_afscs->fetchAllByUser($user);

        $validAfscs = array_intersect(
            $userAfscCollection->getAfscs(),
            $afscs
        );

        if ($this->test_helpers->countIncomplete($user) > ($this->config->get(['testing', 'maxIncomplete']) ?? 0)) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You have too many incomplete tests.  Please finish your current tests before beginning a new one.'
            );

            return $this->redirect('/tests/new');
        }

        if (!$validAfscs) {
            $this->log->warning(
                'create test failed :: afsc not associated :: user ' .
                $user->getName() .
                ' [' .
                $user->getUuid() .
                '] :: AFSC list ' .
                implode(',', $afscs)
            );

            $this->flash()->add(
                MessageTypes::WARNING,
                'None of the provided AFSCs are associated with your account'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/tests/new');
        }

        /** @var Afsc[] $validAfscs */
        $validAfscs = $this->afscs->fetchArray($validAfscs);

        if (!$validAfscs) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'None of the provided AFSCs are valid'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/tests/new');
        }

        foreach ($validAfscs as $validAfsc) {
            $isAuthorized = $this->user_afscs->assertAuthorized(
                $user,
                $validAfsc
            );

            if (!$isAuthorized) {
                $this->log->warning(
                    'create test failed :: afsc not authorized :: user ' .
                    $user->getUuid() .
                    ' [' .
                    $user->getName() .
                    '] :: AFSC ' .
                    $validAfsc->getUuid() .
                    ' [' .
                    $validAfsc->getName() .
                    ']'
                );

                $this->flash()->add(
                    MessageTypes::WARNING,
                    'You are not authorized to take tests for ' .
                    $validAfsc->getName()
                );

                return $this->redirect('/tests/new');
            }
        }

        $testOptions = new TestOptions();
        $testOptions->setNumQuestions(min($numQuestions, $this->config->get(['testing', 'maxQuestions'])));
        $testOptions->setUser($user);
        $testOptions->setAfscs($validAfscs);

        try {
            $newTest = TestHandler::factory($this->log,
                                            $this->afscs,
                                            $this->tests,
                                            $this->cdc_data,
                                            $this->answers,
                                            $this->test_data_helpers,
                                            $testOptions);

            if (($newTest->getTest()->getUuid() ?? '') === '') {
                $this->log->warning(
                    'create test failed :: user ' .
                    $user->getUuid() .
                    ' [' .
                    $user->getName() .
                    '] :: options -- AFSC List ' .
                    implode(',', AfscHelpers::listUuid($testOptions->getAfscs())) .
                    ' :: numQuestions ' .
                    $testOptions->getNumQuestions()
                );

                $this->flash()->add(
                    MessageTypes::WARNING,
                    'We could not generate a test using those options'
                );

                return $this->redirect('/tests/new');
            }
        } catch (Throwable $e) {
            $this->log->debug($e);
            $this->flash()->add(MessageTypes::ERROR, $e->getMessage());
            return $this->redirect('/tests/new');
        }

        $this->log->info("new test :: {$newTest->getTest()->getUuid()} :: {$user->getName()} [{$user->getUuid()}]");
        return $this->redirect('/tests/' . $newTest->getTest()->getUuid());
    }

    /**
     * @param string $testUuid
     * @return Response
     * @throws Exception
     */
    public function do_test_handler(string $testUuid): Response
    {
        $test = $this->tests->fetch($testUuid);

        if (!$test) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test does not exist'
            );

            $this->trigger_request_debug(__METHOD__);
            goto out_redirect_home;
        }

        if (!$this->validate_test($test)) {
            $this->trigger_request_debug(__METHOD__);
            goto out_redirect_home;
        }

        if ($test->isComplete()) {
            goto out_redirect_score;
        }

        $testHandler = TestHandler::resume($this->log,
                                           $this->afscs,
                                           $this->tests,
                                           $this->answers,
                                           $this->test_data_helpers,
                                           $test);

        $payload = json_decode($this->request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if ($payload === null || !isset($payload->action)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered an error while processing your request, please contact the site administrator if the issue persists'
            );

            $this->trigger_request_debug(__METHOD__);
            goto out_redirect_home;
        }

        switch ($payload->action) {
            case TestHandler::ACTION_NO_ACTION:
                break;
            case TestHandler::ACTION_SUBMIT_ANSWER:
                if (!isset($payload->question, $payload->answer)) {
                    break;
                }

                $questionResponse = new QuestionResponse();
                $questionResponse->setTestUuid($testUuid);
                $questionResponse->setQuestionUuid($payload->question);
                $questionResponse->setAnswerUuid($payload->answer);

                $testHandler->saveResponse($questionResponse);
                break;
            case TestHandler::ACTION_NAV_FIRST:
                $testHandler->first();
                break;
            case TestHandler::ACTION_NAV_PREV:
                $testHandler->previous();
                break;
            case TestHandler::ACTION_NAV_NEXT:
                $testHandler->next();
                break;
            case TestHandler::ACTION_NAV_LAST:
                $testHandler->last();
                break;
            case TestHandler::ACTION_NAV_NUM:
                if (!isset($payload->idx)) {
                    break;
                }

                $testHandler->navigate($payload->idx);
                break;
            case TestHandler::ACTION_SCORE_TEST:
                if ($testHandler->getNumAnswered() !== $testHandler->getTest()->getNumQuestions()) {
                    break;
                }

                $testHandler->score();

                if ($testHandler->getTest()->getScore() > $this->config->get(['testing', 'passingScore'])) {
                    $this->flash()->add(
                        MessageTypes::SUCCESS,
                        'Congratulations, you passed the test!'
                    );
                } else {
                    $this->flash()->add(
                        MessageTypes::WARNING,
                        'Oh, no! You fell a little short of the goal.  Keep studying!'
                    );
                }

                goto out_redirect_score;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The system encountered an error while processing your request, please contact the site administrator if the issue persists'
                );

                $this->trigger_request_debug(__METHOD__);
                goto out_redirect_home;
        }

        try {
            return new JsonResponse($testHandler->getDisplayData());
        } catch (Throwable $e) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered an error while processing your request, please contact the site administrator if the issue persists'
            );

            $this->trigger_request_debug(__METHOD__);
            goto out_redirect_home;
        }

        out_redirect_score:
        try {
            return new JsonResponse(['redirect' => '/tests/' . $testUuid . '?score',]);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return new JsonResponse($e->getMessage(), 500);
        }

        out_redirect_home:
        try {
            return new JsonResponse(['redirect' => '/',]);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return new JsonResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return Response
     */
    public function show_delete_incomplete_tests(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            static function (Test $v) {
                return $v->getTimeCompleted() === null;
            }
        );

        if (count($tests) === 0) {
            $this->flash()->add(
                MessageTypes::INFO,
                'There are no tests to delete'
            );

            return $this->redirect('/');
        }

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                return $b->getTimeStarted() <=> $a->getTimeStarted();
            }
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'tests' => $tests,
        ];

        return $this->render(
            'tests/delete-incomplete.html.twig',
            $data
        );
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function show_delete_test(string $testUuid): Response
    {
        $test = $this->tests->fetch($testUuid);

        if (!$test) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test does not exist'
            );

            return $this->redirect('/');
        }

        if (!$this->validate_test($test, false)) {
            return $this->redirect('/');
        }

        if ($test->getScore() > 0 || $test->getTimeCompleted() !== null) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You cannot delete a completed test'
            );

            return $this->redirect('/');
        }

        $data = [
            'test' => TestHelpers::formatHtml([$test])[ 0 ] ?? [],
        ];

        return $this->render(
            'tests/delete.html.twig',
            $data
        );
    }

    /**
     * @return Response
     */
    public function show_new_test(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        $authorized_afscs = $this->user_afscs->fetchAllByUser($user)->getAuthorized();
        if (!$authorized_afscs) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You are not associated with any AFSCs, or all of your AFSC associations are pending approval'
            );

            return $this->redirect('/');
        }

        $userAfscs = $this->afscs->fetchArray($authorized_afscs);
        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            static function (Test $v) {
                return $v->getTimeCompleted() === null;
            }
        );

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                if ($a->getTimeStarted() === null || $b->getTimeStarted() === null) {
                    return 0;
                }

                return $b->getTimeStarted()->format('U') <=> $a->getTimeStarted()->format('U');
            }
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'afscList' => $userAfscs,
            'tests' => $tests,
            'disableNewTest' => count($tests) >= ($this->config->get(['testing', 'maxIncomplete']) ?? 0),
        ];

        return $this->render(
            'tests/new.html.twig',
            $data
        );
    }

    /**
     * @return Response
     */
    public function show_tests_home(): Response
    {
        return $this->redirect('/tests/history');
    }

    /**
     * @param int $type
     * @return Response
     */
    private function show_test_history(int $type): Response
    {
        switch ($type) {
            case Test::TYPE_COMPLETE:
                $path = '/tests/history';
                $typeStr = 'complete';
                $template = 'tests/history-complete.html.twig';
                break;
            case Test::TYPE_INCOMPLETE:
                $path = '/tests/history/incomplete';
                $typeStr = 'incomplete';
                $template = 'tests/history-incomplete.html.twig';
                break;
            default:
                $this->flash()->add(
                    MessageTypes::INFO,
                    'We made a mistake when processing that request'
                );

                return $this->redirect('/');
        }

        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->filter_int_default(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->filter_int_default(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        [$col, $dir] = self::validate_test_sort($sortCol, $sortDir);
        $userTests = $this->tests->fetchAllByUser($user, [$col => $dir]);

        if (empty($userTests)) {
            $this->flash()->add(
                MessageTypes::INFO,
                'You have not taken any tests'
            );

            return $this->redirect('/');
        }

        $userTests = array_filter(
            $userTests,
            static function (Test $v) use ($type) {
                switch ($type) {
                    case Test::TYPE_COMPLETE:
                        if ($v->getTimeCompleted() !== null) {
                            return true;
                        }
                        break;
                    case Test::TYPE_INCOMPLETE:
                        if ($v->getTimeCompleted() === null) {
                            return true;
                        }
                        break;
                }

                return false;
            }
        );

        $userTests = TestHelpers::formatHtml($userTests);

        $filteredList = ArrayPaginator::paginate(
            $userTests,
            $curPage,
            $numRecords
        );

        if (count($filteredList) === 0) {
            $this->flash()->add(
                MessageTypes::INFO,
                $type === Test::TYPE_INCOMPLETE
                    ? 'You have not started any ' . $typeStr . ' tests'
                    : 'You have not taken any ' . $typeStr . ' tests'
            );

            return $this->redirect('/');
        }

        $pagination = ArrayPaginator::buildLinks(
            $path,
            $curPage,
            ArrayPaginator::calcNumPagesData(
                $userTests,
                $numRecords
            ),
            $numRecords,
            count($userTests),
            $col,
            $dir
        );

        return $this->render(
            $template,
            [
                'tests' => $filteredList,
                'pagination' => $pagination,
                'sort' => [
                    'col' => $sortCol,
                    'dir' => $sortDir,
                ],
            ]
        );
    }

    /**
     * @return Response
     */
    public function show_test_history_complete(): Response
    {
        return $this->show_test_history(Test::TYPE_COMPLETE);
    }

    /**
     * @return Response
     */
    public function show_test_history_incomplete(): Response
    {
        return $this->show_test_history(Test::TYPE_INCOMPLETE);
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function show_test(string $testUuid): Response
    {
        $test = $this->tests->fetch($testUuid);
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        if (!$test) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'That test does not exist'
            );

            return $this->redirect('/');
        }

        if (!$this->validate_test($test)) {
            return $this->redirect('/');
        }

        if ($test->isComplete()) {
            return $this->show_test_complete($test);
        }

        if ($test->getNumAnswered() > 0) {
            $this->log->info("resume test :: {$test->getUuid()} :: {$user->getName()} [{$user->getUuid()}]");
        }

        return $this->show_test_incomplete($testUuid);
    }

    /**
     * @param Test $test
     * @return Response
     */
    private function show_test_complete(Test $test): Response
    {
        $testData = $this->test_data_helpers->list($test);

        $time_started = $test->getTimeStarted();
        if ($time_started) {
            $time_started = $time_started->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $time_completed = $test->getTimeCompleted();
        if ($time_completed) {
            $time_completed = $time_completed->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $last_updated = $test->getLastUpdated();
        if ($last_updated) {
            $last_updated = $last_updated->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $data = [
            'showUser' => false,
            'timeStarted' => $time_started,
            'timeCompleted' => $time_completed,
            'lastUpdated' => $last_updated,
            'afscList' => AfscHelpers::listNames($test->getAfscs()),
            'numQuestions' => $test->getNumQuestions(),
            'numMissed' => $test->getNumMissed(),
            'score' => $test->getScore(),
            'isArchived' => $test->isArchived(),
            'testData' => $testData,
        ];

        return $this->render(
            'tests/completed.html.twig',
            $data
        );
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    private function show_test_incomplete(string $testUuid): Response
    {
        return $this->render(
            'tests/test.html.twig',
            [
                'testUuid' => $testUuid,
            ]
        );
    }

    /**
     * @param null|string $column
     * @param null|string $direction
     * @return array
     */
    public static function validate_test_sort(?string $column, ?string $direction): array
    {
        $column = $column ?? 'timeStarted';
        $direction = $direction ?? 'DESC';

        switch ($column) {
            case TestCollection::COL_AFSC_LIST:
            case TestCollection::COL_TIME_STARTED:
            case TestCollection::COL_TIME_COMPLETED:
            case TestCollection::COL_LAST_UPDATED:
            case TestCollection::COL_CUR_QUESTION:
            case TestCollection::COL_NUM_ANSWERED:
            case TestCollection::COL_NUM_MISSED:
            case TestCollection::COL_SCORE:
            case TestCollection::COL_IS_ARCHIVED:
                break;
            default:
                $column = TestCollection::DEFAULT_COL;
                break;
        }

        $direction = strtoupper($direction);

        switch ($direction) {
            case TestCollection::ORDER_ASC:
            case TestCollection::ORDER_DESC:
                break;
            default:
                $direction = TestCollection::DEFAULT_ORDER;
                break;
        }

        return [$column, $direction];
    }

    private function validate_test(?Test $test, bool $validate_afscs = true): bool
    {
        if (!$test || ($test->getUuid() ?? '') === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The provided Test ID does not exist'
            );

            return false;
        }

        if ($test->getUserUuid() !== $this->auth_helpers->get_user_uuid()) {
            $user_name = $this->auth_helpers->get_user_name();
            $user_uuid = $this->auth_helpers->get_user_uuid();
            $this->log->warning("test user mismatch :: test {$test->getUuid()} :: test user {$test->getUserUuid()} :: user {$user_name} [{$user_uuid}]");
            $this->flash()->add(
                MessageTypes::WARNING,
                'The selected test does not belong to your user account'
            );

            return false;
        }

        if (!$validate_afscs) {
            return true;
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            return false;
        }

        $authorized_afscs =
            $this->user_afscs->fetchAllByUser($user)->getAuthorized();

        $userAfscs = $this->afscs->fetchArray($authorized_afscs);

        foreach ($test->getAfscs() as $tgt_afsc) {
            if (!isset($userAfscs[ $tgt_afsc->getUuid() ])) {
                $this->flash()->add(
                    MessageTypes::WARNING,
                    "This test includes information for an AFSC you are no longer associated with: {$tgt_afsc->getName()}"
                );

                return false;
            }
        }

        return true;
    }
}