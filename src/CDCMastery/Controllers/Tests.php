<?php

namespace CDCMastery\Controllers;

use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Tests\QuestionResponse;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHandler;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Tests\TestOptions;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use Exception;
use Monolog\Logger;
use mysqli;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use function count;

class Tests extends RootController
{
    private const TYPE_ALL = 0;
    private const TYPE_COMPLETE = 1;
    private const TYPE_INCOMPLETE = 2;

    /**
     * @var AuthHelpers
     */
    private $auth_helpers;

    /**
     * @var UserCollection
     */
    private $users;

    /**
     * @var TestCollection
     */
    private $tests;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TestHelpers
     */
    private $test_helpers;

    /**
     * @var UserAfscAssociations
     */
    private $user_afscs;

    /**
     * @var AfscCollection
     */
    private $afscs;

    /**
     * @var mysqli
     */
    private $db;

    /**
     * @var TestDataHelpers
     */
    private $test_data_helpers;

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
        mysqli $mysqli,
        TestDataHelpers $test_data_helpers
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->users = $users;
        $this->tests = $tests;
        $this->config = $config;
        $this->test_helpers = $test_helpers;
        $this->user_afscs = $user_afscs;
        $this->afscs = $afscs;
        $this->db = $mysqli;
        $this->test_data_helpers = $test_data_helpers;
    }

    /**
     * @return Response
     */
    public function do_delete_incomplete_tests(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        if (!is_array($tests) || count($tests) === 0) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no tests to delete');

            return $this->redirect('/');
        }

        $this->tests->deleteArray(
            TestHelpers::listUuid($tests)
        );

        $this->flash()->add(MessageTypes::SUCCESS,
                            'All incomplete tests have been removed from the database');

        return $this->redirect('/tests/new');
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function do_delete_test(string $testUuid): Response
    {
        $test = $this->tests->fetch($testUuid);

        if (($error = $this->validate_test($test)) instanceof Response) {
            return $error;
        }

        if ($test->getScore() !== 0.00 || $test->getTimeCompleted() !== null) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You cannot delete a completed test'
            );

            return $this->redirect('/');
        }

        $this->tests->delete($test->getUuid());

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
        $numQuestions = $this->filter('numQuestions', FILTER_VALIDATE_INT);

        if (!is_array($afscs) || count($afscs) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You must select at least one AFSC'
            );

            return $this->redirect('/tests/new');
        }

        if ($numQuestions === false) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The provided amount of questions for the test was invalid'
            );

            return $this->redirect('/tests/new');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
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

        if (count($validAfscs) === 0) {
            $this->log->addWarning(
                'create test failed :: afsc not associated :: user ' .
                $user->getUuid() .
                ' [' .
                $user->getName() .
                '] :: AFSC list ' .
                implode(',', $afscs)
            );

            $this->flash()->add(
                MessageTypes::WARNING,
                'None of the provided AFSCs are associated with your account'
            );

            return $this->redirect('/tests/new');
        }

        /** @var Afsc[] $validAfscs */
        $validAfscs = $this->afscs->fetchArray($validAfscs);

        if (count($validAfscs) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'None of the provided AFSCs are valid'
            );

            return $this->redirect('/tests/new');
        }

        foreach ($validAfscs as $validAfsc) {
            $isAuthorized = $this->user_afscs->assertAuthorized(
                $user,
                $validAfsc
            );

            if (!$isAuthorized) {
                $this->log->addWarning(
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

        $test_data_helpers = new TestDataHelpers($this->db,
                                                 $this->log);

        $testOptions = new TestOptions();
        $testOptions->setNumQuestions($numQuestions);
        $testOptions->setUser($user);
        $testOptions->setAfscs($validAfscs);

        $newTest = TestHandler::factory($this->db,
                                        $this->log,
                                        $test_data_helpers,
                                        $testOptions);

        if (($newTest->getTest()->getUuid() ?? '') === '') {
            $this->log->addWarning(
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

        if (($error = $this->validate_test($test)) instanceof Response) {
            /** @todo send message that test does not exist */
            /** @todo send message that test does not belong to this user */
            exit;
        }

        if ($test->isComplete()) {
            /** @todo send message that test is already complete */
            exit;
        }

        $test_data_helpers = new TestDataHelpers($this->db,
                                                 $this->log);

        $testHandler = TestHandler::resume($this->db,
                                           $this->log,
                                           $test_data_helpers,
                                           $test);

        $payload = json_decode($this->getRequest()->getContent() ?? null);

        if ($payload === null || !isset($payload->action)) {
            /** @todo send message that request was malformed */
            exit;
        }

        switch ($payload->action) {
            case TestHandler::ACTION_NO_ACTION:
                break;
            case TestHandler::ACTION_SUBMIT_ANSWER:
                if (!isset($payload->question) || !isset($payload->answer)) {
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

                return new Response(
                    json_encode([
                                    'redirect' => '/tests/' . $testUuid . '?score',
                                ]),
                    200,
                    ['Content-Type', 'application/json']
                );
            default:
                /** @todo handle bad action */
                break;
        }

        return new JsonResponse($testHandler->getDisplayData());
    }

    /**
     * @return Response
     */
    public function show_delete_incomplete_tests(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function (Test $v) {
                return $v->getScore() === 0.00 && $v->getTimeCompleted() === null;
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
            function (Test $a, Test $b) {
                return $b->getTimeStarted()->format('U') <=> $a->getTimeStarted()->format('U');
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

        if (($error = $this->validate_test($test)) instanceof Response) {
            return $error;
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

        if (empty($user->getUuid())) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'An error has occurred while fetching your user data'
            );

            return $this->redirect('/auth/logout');
        }

        $userAfscCollection = $this->user_afscs->fetchAllByUser($user);

        if (empty($userAfscCollection->getAfscs())) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'You are not associated with any AFSCs'
            );

            return $this->redirect('/');
        }

        $userAfscs = $this->afscs->fetchArray($userAfscCollection->getAfscs());
        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function (Test $v) {
                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        uasort(
            $tests,
            function (Test $a, Test $b) {
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
            case self::TYPE_ALL:
                $path = '/tests/history/all';
                $typeStr = 'all';
                $template = 'tests/history-combined.html.twig';
                break;
            case self::TYPE_COMPLETE:
                $path = '/tests/history';
                $typeStr = 'complete';
                $template = 'tests/history-complete.html.twig';
                break;
            case self::TYPE_INCOMPLETE:
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

        $sortCol = $this->getRequest()->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->getRequest()->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->getRequest()->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->getRequest()->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        [$col, $dir] = self::validate_test_sort($sortCol, $sortDir);
        $userTests = $this->tests->fetchAllByUser($user,
                                                  [
                                                      $col => $dir,
                                                  ]);

        if (empty($userTests)) {
            $this->flash()->add(
                MessageTypes::INFO,
                'You have not taken any tests'
            );

            return $this->redirect('/');
        }

        $userTests = array_filter(
            $userTests,
            function (Test $v) use ($type) {
                switch ($type) {
                    case Tests::TYPE_ALL:
                        return true;
                    case Tests::TYPE_COMPLETE:
                        if ($v->getScore() > 0 && $v->getTimeCompleted() !== null) {
                            return true;
                        }
                        break;
                    case Tests::TYPE_INCOMPLETE:
                        if ($v->getScore() < 1 && $v->getTimeCompleted() === null) {
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
                $type === self::TYPE_ALL
                    ? 'You have not taken any tests'
                    : ($type === self::TYPE_INCOMPLETE
                    ? 'You have not started any ' . $typeStr . ' tests'
                    : 'You have not taken any ' . $typeStr . ' tests')
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
        return $this->show_test_history(self::TYPE_COMPLETE);
    }

    /**
     * @return Response
     */
    public function show_test_history_incomplete(): Response
    {
        return $this->show_test_history(self::TYPE_INCOMPLETE);
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function show_test(string $testUuid): Response
    {
        $test = $this->tests->fetch($testUuid);

        if ($test->isComplete()) {
            return $this->show_test_complete($test);
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

        $data = [
            'showUser' => false,
            'timeStarted' => $test->getTimeStarted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
            'timeCompleted' => $test->getTimeCompleted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
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
            case TestCollection::COL_CUR_QUESTION:
            case TestCollection::COL_NUM_ANSWERED:
            case TestCollection::COL_NUM_MISSED:
            case TestCollection::COL_SCORE:
            case TestCollection::COL_IS_COMBINED:
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

    private function validate_test(Test $test): ?Response
    {
        if (($test->getUuid() ?? '') === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The provided Test ID does not exist'
            );

            return $this->redirect('/tests/new');
        }

        if ($test->getUserUuid() !== $this->auth_helpers->get_user_uuid()) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The selected test does not belong to your user account'
            );

            return $this->redirect('/');
        }

        return null;
    }
}