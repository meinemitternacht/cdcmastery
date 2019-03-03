<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;

use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Tests\QuestionResponse;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHandler;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Tests\TestOptions;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Tests extends RootController
{
    private const TYPE_ALL = 0;
    private const TYPE_COMPLETE = 1;
    private const TYPE_INCOMPLETE = 2;

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @var TestCollection
     */
    private $testCollection;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TestHelpers
     */
    private $testHelpers;

    /**
     * @var UserAfscAssociations
     */
    private $userAfscAssociations;

    /**
     * @var AfscCollection
     */
    private $afscCollection;

    /**
     * @var \mysqli
     */
    private $db;

    /**
     * @var TestDataHelpers
     */
    private $testDataHelpers;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        UserCollection $userCollection,
        TestCollection $testCollection,
        Config $config,
        TestHelpers $testHelpers,
        UserAfscAssociations $userAfscAssociations,
        AfscCollection $afscCollection,
        \mysqli $mysqli,
        TestDataHelpers $testDataHelpers
    ) {
        parent::__construct($logger, $twig);

        $this->userCollection = $userCollection;
        $this->testCollection = $testCollection;
        $this->config = $config;
        $this->testHelpers = $testHelpers;
        $this->userAfscAssociations = $userAfscAssociations;
        $this->afscCollection = $afscCollection;
        $this->db = $mysqli;
        $this->testDataHelpers = $testDataHelpers;
    }

    /**
     * @return Response
     */
    public function processDeleteIncompleteTests(): Response
    {
        $user = $this->userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $tests = $this->testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        if (!is_array($tests) || \count($tests) === 0) {
            Messages::add(
                Messages::INFO,
                'There are no tests to delete'
            );

            return AppHelpers::redirect('/');
        }

        $this->testCollection->deleteArray(
            TestHelpers::listUuid($tests)
        );

        Messages::add(
            Messages::SUCCESS,
            'All incomplete tests have been removed from the database'
        );

        return AppHelpers::redirect('/tests/new');
    }

    /**
     * @param string $testUuid
     * @return Response
     */
    public function processDeleteTest(string $testUuid): Response
    {
        $test = $this->testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            return AppHelpers::redirect('/');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            return AppHelpers::redirect('/');
        }

        if ($test->getScore() > 0 || $test->getTimeCompleted() !== null) {
            Messages::add(
                Messages::WARNING,
                'You cannot delete a completed test'
            );

            return AppHelpers::redirect('/');
        }

        $this->testCollection->delete($test->getUuid());

        Messages::add(
            Messages::SUCCESS,
            'The specified test has been removed from the database'
        );

        return AppHelpers::redirect('/tests/new');
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function processNewTest(): Response
    {
        $parameters = [
            'afscs',
            'numQuestions',
        ];

        if (!$this->checkParameters($parameters, $this->request)) {
            Messages::add(
                Messages::WARNING,
                'Please ensure all required options are selected before beginning a test'
            );

            return AppHelpers::redirect('/tests/new');
        }

        $afscs = $this->get('afscs');
        $numQuestions = $this->filter('numQuestions', FILTER_VALIDATE_INT);

        if (!is_array($afscs) || empty($afscs)) {
            Messages::add(
                Messages::WARNING,
                'You must select at least one AFSC'
            );

            return AppHelpers::redirect('/tests/new');
        }

        if ($numQuestions === false) {
            Messages::add(
                Messages::WARNING,
                'The provided amount of questions for the test was invalid'
            );

            return AppHelpers::redirect('/tests/new');
        }

        $user = $this->userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $userAfscCollection = $this->userAfscAssociations->fetchAllByUser($user);

        $validAfscs = array_intersect(
            $userAfscCollection->getAfscs(),
            $afscs
        );

        if ($this->testHelpers->countIncomplete($user) > $this->config->get(['testing', 'maxIncomplete']) ?? 0) {
            Messages::add(
                Messages::WARNING,
                'You have too many incomplete tests.  Please finish your current tests before beginning a new one.'
            );

            return AppHelpers::redirect('/tests/new');
        }

        if (empty($validAfscs)) {
            $this->log->addWarning(
                'create test failed :: afsc not associated :: user ' .
                $user->getUuid() .
                ' [' .
                $user->getName() .
                '] :: AFSC list ' .
                implode(',', $afscs)
            );

            Messages::add(
                Messages::WARNING,
                'None of the provided AFSCs are associated with your account'
            );

            return AppHelpers::redirect('/tests/new');
        }

        /** @var Afsc[] $validAfscs */
        $validAfscs = $this->afscCollection->fetchArray($validAfscs);

        if (empty($validAfscs)) {
            Messages::add(
                Messages::WARNING,
                'None of the provided AFSCs are valid'
            );

            return AppHelpers::redirect('/tests/new');
        }

        foreach ($validAfscs as $validAfsc) {
            $isAuthorized = $this->userAfscAssociations->assertAuthorized(
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

                Messages::add(
                    Messages::WARNING,
                    'You are not authorized to take tests for ' .
                    $validAfsc->getName()
                );

                return AppHelpers::redirect('/tests/new');
            }
        }

        $testOptions = new TestOptions();
        $testOptions->setNumQuestions($numQuestions);
        $testOptions->setUser($user);
        $testOptions->setAfscs($validAfscs);

        $newTest = TestHandler::factory(
            $this->db,
            $this->log,
            $testOptions
        );

        if (empty($newTest->getTest()->getUuid())) {
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

            Messages::add(
                Messages::WARNING,
                'We could not generate a test using those options'
            );

            return AppHelpers::redirect('/tests/new');
        }

        return AppHelpers::redirect('/tests/' . $newTest->getTest()->getUuid());
    }

    /**
     * @param string $testUuid
     * @return Response
     * @throws \Exception
     */
    public function processTest(string $testUuid): Response
    {
        $test = $this->testCollection->fetch($testUuid);

        if ($test->getUuid() === '') {
            /** @todo send message that test does not exist */
            exit;
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            /** @todo send message that test does not belong to this user */
            exit;
        }

        if ($test->isComplete()) {
            /** @todo send message that test is already complete */
            exit;
        }

        $testHandler = TestHandler::resume(
            $this->db,
            $this->log,
            $test
        );

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
                    Messages::add(
                        Messages::SUCCESS,
                        'Congratulations, you passed the test!'
                    );
                } else {
                    Messages::add(
                        Messages::WARNING,
                        'Oh, no! You fell a little short of the goal.  Keep studying!'
                    );
                }

                $response = new Response(
                    json_encode([
                        'redirect' => '/tests/' . $testUuid . '?score',
                    ]),
                    200,
                    ['Content-Type', 'application/json']
                );

                return $response;
            default:
                /** @todo handle bad action */
                break;
        }

        $response = new Response(
            json_encode($testHandler->getDisplayData()),
            200,
            ['Content-Type', 'application/json']
        );

        return $response;
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderDeleteIncompleteTests(): Response
    {
        $user = $this->userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $tests = $this->testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        if (\count($tests) === 0) {
            Messages::add(
                Messages::INFO,
                'There are no tests to delete'
            );

            return AppHelpers::redirect('/');
        }

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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderDeleteTest(string $testUuid): Response
    {
        $test = $this->testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            return AppHelpers::redirect('/');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            return AppHelpers::redirect('/');
        }

        if ($test->getScore() > 0 || $test->getTimeCompleted() !== null) {
            Messages::add(
                Messages::WARNING,
                'You cannot delete a completed test'
            );

            return AppHelpers::redirect('/');
        }

        $data = [
            'test' => TestHelpers::formatHtml([$test])[0] ?? [],
        ];

        return $this->render(
            'tests/delete.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderNewTest(): Response
    {
        $user = $this->userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        if (empty($user->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'An error has occurred while fetching your user data'
            );

            return AppHelpers::redirect('/auth/logout');
        }

        $userAfscCollection = $this->userAfscAssociations->fetchAllByUser($user);

        if (empty($userAfscCollection->getAfscs())) {
            Messages::add(
                Messages::WARNING,
                'You are not associated with any AFSCs'
            );

            return AppHelpers::redirect('/');
        }

        $userAfscs = $this->afscCollection->fetchArray($userAfscCollection->getAfscs());

        $userAfscList = [];
        /** @var Afsc $afsc */
        foreach ($userAfscs as $afsc) {
            $userAfscList[] = [
                'uuid' => $afsc->getUuid(),
                'name' => $afsc->getName(),
                'description' => $afsc->getDescription(),
                'version' => $afsc->getVersion(),
            ];
        }

        $tests = $this->testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        uasort(
            $tests,
            function (Test $a, Test $b) {
                if ($a->getTimeStarted() === null || $b->getTimeStarted() === null) {
                    return 0;
                }

                return $a->getTimeStarted()->format('U') <=> $b->getTimeStarted()->format('U');
            }
        );

        $tests = array_reverse(
            $tests,
            true
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'afscList' => $userAfscList,
            'tests' => $tests,
            'disableNewTest' => \count($tests) >= $this->config->get(['testing', 'maxIncomplete']) ?? 0,
        ];

        return $this->render(
            'tests/new.html.twig',
            $data
        );
    }

    /**
     * @return Response
     */
    public function renderTestsHome(): Response
    {
        return AppHelpers::redirect('/tests/history');
    }

    /**
     * @param int $type
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderTestHistory(int $type): Response
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
                Messages::add(
                    Messages::INFO,
                    'We made a mistake when processing that request'
                );

                return AppHelpers::redirect('/');
                break;
        }

        $sortCol = $this->getRequest()->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->getRequest()->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->getRequest()->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->getRequest()->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $user = $this->userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        list($col, $dir) = self::validateSort($sortCol, $sortDir);
        $userTests = $this->testCollection->fetchAllByUser(
            $user, [
                $col => $dir,
            ]
        );

        if (empty($userTests)) {
            Messages::add(
                Messages::INFO,
                'You have not taken any tests'
            );

            return AppHelpers::redirect('/');
        }

        $userTests = array_filter(
            $userTests,
            function ($v) use ($type) {
                if (!$v instanceof Test) {
                    return false;
                }

                switch ($type) {
                    case Tests::TYPE_ALL:
                        return true;
                        break;
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

        if (empty($filteredList)) {
            Messages::add(
                Messages::INFO,
                $type === self::TYPE_ALL
                    ? 'You have not taken any tests'
                    : ($type === self::TYPE_INCOMPLETE)
                    ? 'You have not started any ' . $typeStr . ' tests'
                    : 'You have not taken any ' . $typeStr . ' tests'
            );

            return AppHelpers::redirect('/');
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
            $template, [
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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestHistoryAll(): Response
    {
        return $this->renderTestHistory(self::TYPE_ALL);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestHistoryComplete(): Response
    {
        return $this->renderTestHistory(self::TYPE_COMPLETE);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTestHistoryIncomplete(): Response
    {
        return $this->renderTestHistory(self::TYPE_INCOMPLETE);
    }

    /**
     * @param string $testUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTest(string $testUuid): Response
    {
        $test = $this->testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            return AppHelpers::redirect('/tests/new');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            return AppHelpers::redirect('/');
        }

        if ($test->isComplete()) {
            return $this->renderTestComplete($test);
        }

        return $this->renderTestIncomplete($testUuid);
    }

    /**
     * @param Test $test
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderTestComplete(Test $test): Response
    {
        $testData = $this->testDataHelpers->list($test);

        $data = [
            'showUser' => false,
            'timeStarted' => $test->getTimeStarted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
            'timeCompleted' => $test->getTimeCompleted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
            'afscList' => AfscHelpers::listNames(
                $test->getAfscs()
            ),
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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderTestIncomplete(string $testUuid): Response
    {
        return $this->render(
            'tests/test.html.twig', [
                'testUuid' => $testUuid,
            ]
        );
    }

    /**
     * @param null|string $column
     * @param null|string $direction
     * @return array
     */
    private function validateSort(?string $column, ?string $direction): array
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
}