<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;

use CDCMastery\Exceptions\Parameters\MissingParameterException;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\ParameterHelpers;
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

class Tests extends RootController
{
    const TYPE_ALL = 0;
    const TYPE_COMPLETE = 1;
    const TYPE_INCOMPLETE = 2;

    /**
     * @return string
     */
    public function processDeleteIncompleteTests(): string
    {
        $userCollection = $this->container->get(UserCollection::class);

        $user = $userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $testCollection = $this->container->get(TestCollection::class);
        $tests = $testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && is_null($v->getTimeCompleted());
            }
        );

        if (empty($tests)) {
            Messages::add(
                Messages::INFO,
                'There are no tests to delete'
            );

            AppHelpers::redirect('/');
        }

        $testCollection->deleteArray(
            TestHelpers::listUuid($tests)
        );

        Messages::add(
            Messages::SUCCESS,
            'All incomplete tests have been removed from the database'
        );

        AppHelpers::redirect('/tests/new');
    }

    /**
     * @param string $testUuid
     * @return string
     */
    public function processDeleteTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            AppHelpers::redirect('/');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            AppHelpers::redirect('/');
        }

        if ($test->getScore() > 0 || !is_null($test->getTimeCompleted())) {
            Messages::add(
                Messages::WARNING,
                'You cannot delete a completed test'
            );

            AppHelpers::redirect('/');
        }

        $testCollection->delete($test->getUuid());

        Messages::add(
            Messages::SUCCESS,
            'The specified test has been removed from the database'
        );

        return AppHelpers::redirect('/tests/new');
    }

    /**
     * @return string
     */
    public function processNewTest(): string
    {
        try {
            ParameterHelpers::checkRequiredParameters(
                $this->getRequest(), [
                    'afscs',
                    'numQuestions'
                ]
            );
        } catch (MissingParameterException $e) {
            Messages::add(
                Messages::WARNING,
                'Please ensure all required options are selected before beginning a test'
            );

            AppHelpers::redirect('/tests/new');
        }

        $afscs = $this->getRequest()->request->get('afscs');
        $numQuestions = $this->getRequest()->request->filter('numQuestions', FILTER_VALIDATE_INT);

        if (!is_array($afscs) || empty($afscs)) {
            Messages::add(
                Messages::WARNING,
                'You must select at least one AFSC'
            );

            AppHelpers::redirect('/tests/new');
        }

        if ($numQuestions === false) {
            Messages::add(
                Messages::WARNING,
                'The provided amount of questions for the test was invalid'
            );

            AppHelpers::redirect('/tests/new');
        }

        $config = $this->container->get(Config::class);
        $testHelpers = $this->container->get(TestHelpers::class);
        $userCollection = $this->container->get(UserCollection::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $user = $userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $userAfscCollection = $userAfscAssociations->fetchAllByUser($user);

        $validAfscs = array_intersect(
            $userAfscCollection->getAfscs(),
            $afscs
        );

        if ($testHelpers->countIncomplete($user) > $config->get(['testing', 'maxIncomplete']) ?? 0) {
            Messages::add(
                Messages::WARNING,
                'You have too many incomplete tests.  Please finish your current tests before beginning a new one.'
            );

            AppHelpers::redirect('/tests/new');
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

            AppHelpers::redirect('/tests/new');
        }

        $afscCollection = $this->container->get(AfscCollection::class);
        $validAfscs = $afscCollection->fetchArray($validAfscs);

        if (empty($validAfscs)) {
            Messages::add(
                Messages::WARNING,
                'None of the provided AFSCs are valid'
            );

            AppHelpers::redirect('/tests/new');
        }

        foreach ($validAfscs as $validAfsc) {
            $isAuthorized = $userAfscAssociations->assertAuthorized(
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

                AppHelpers::redirect('/tests/new');
            }
        }

        $testOptions = new TestOptions();
        $testOptions->setNumQuestions($numQuestions);
        $testOptions->setUser($user);
        $testOptions->setAfscs($validAfscs);

        $newTest = TestHandler::factory(
            $this->container->get(\mysqli::class),
            $this->container->get(Logger::class),
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

            AppHelpers::redirect('/tests/new');
        }

        return AppHelpers::redirect('/tests/' . $newTest->getTest()->getUuid());
    }

    /**
     * @param string $testUuid
     * @return string
     */
    public function processTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            /** @todo send message that test does not exist */
            return '';
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            /** @todo send message that test does not belong to this user */
            return '';
        }

        if ($test->isComplete()) {
            /** @todo send message that test is already complete */
            return '';
        }

        $testHandler = TestHandler::resume(
            $this->container->get(\mysqli::class),
            $this->container->get(Logger::class),
            $test
        );

        $payload = json_decode($this->getRequest()->getContent() ?? null);

        if (is_null($payload) || !isset($payload->action)) {
            /** @todo send message that request was malformed */
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

                $config = $this->container->get(Config::class);

                if ($testHandler->getTest()->getScore() > $config->get(['testing', 'passingScore'])) {
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

                return json_encode([
                    'redirect' => '/tests/' . $testUuid . '?score'
                ]);
                break;
            default:
                /** @todo handle bad action */
                break;
        }

        return json_encode($testHandler->getDisplayData());
    }

    /**
     * @return string
     */
    public function renderDeleteIncompleteTests(): string
    {
        $userCollection = $this->container->get(UserCollection::class);

        $user = $userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        $testCollection = $this->container->get(TestCollection::class);
        $tests = $testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && is_null($v->getTimeCompleted());
            }
        );

        if (empty($tests)) {
            Messages::add(
                Messages::INFO,
                'There are no tests to delete'
            );

            AppHelpers::redirect('/');
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
            'tests' => $tests
        ];

        return $this->render(
            'tests/delete-incomplete.html.twig',
            $data
        );
    }

    /**
     * @param string $testUuid
     * @return string
     */
    public function renderDeleteTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            AppHelpers::redirect('/');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            AppHelpers::redirect('/');
        }

        if ($test->getScore() > 0 || !is_null($test->getTimeCompleted())) {
            Messages::add(
                Messages::WARNING,
                'You cannot delete a completed test'
            );

            AppHelpers::redirect('/');
        }

        $data = [
            'test' => TestHelpers::formatHtml([$test])[0] ?? []
        ];

        return $this->render(
            'tests/delete.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    public function renderNewTest(): string
    {
        $config = $this->container->get(Config::class);
        $userCollection = $this->container->get(UserCollection::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $user = $userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        if (empty($user->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'An error has occurred while fetching your user data'
            );

            AppHelpers::redirect('/auth/logout');
        }

        $userAfscCollection = $userAfscAssociations->fetchAllByUser($user);

        if (empty($userAfscCollection->getAfscs())) {
            Messages::add(
                Messages::WARNING,
                'You are not associated with any AFSCs'
            );

            AppHelpers::redirect('/');
        }

        $afscCollection = $this->container->get(AfscCollection::class);
        $userAfscs = $afscCollection->fetchArray($userAfscCollection->getAfscs());

        $userAfscList = [];
        /** @var Afsc $afsc */
        foreach ($userAfscs as $afsc) {
            $userAfscList[] = [
                'uuid' => $afsc->getUuid(),
                'name' => $afsc->getName(),
                'description' => $afsc->getDescription(),
                'version' => $afsc->getVersion()
            ];
        }

        $testCollection = $this->container->get(TestCollection::class);
        $tests = $testCollection->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && is_null($v->getTimeCompleted());
            }
        );

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
            'afscList' => $userAfscList,
            'tests' => $tests,
            'disableNewTest' => count($tests) > ($config->get(['testing', 'maxIncomplete']) ?? 0)
        ];

        return $this->render(
            'tests/new.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    public function renderTestsHome(): string
    {
        return AppHelpers::redirect('/tests/history');
    }

    /**
     * @param int $type
     * @return string
     */
    private function renderTestHistory(int $type): string
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

        $userCollection = $this->container->get(UserCollection::class);
        $user = $userCollection->fetch(
            SessionHelpers::getUserUuid()
        );

        list($col, $dir) = self::validateSort($sortCol, $sortDir);
        $testCollection = $this->container->get(TestCollection::class);
        $userTests = $testCollection->fetchAllByUser(
            $user, [
                $col => $dir
            ]
        );

        if (empty($userTests)) {
            Messages::add(
                Messages::INFO,
                'You have not taken any tests'
            );

            AppHelpers::redirect('/');
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

            AppHelpers::redirect('/');
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
                    'dir' => $sortDir
                ]
            ]
        );
    }

    /**
     * @return string
     */
    public function renderTestHistoryAll(): string
    {
        return $this->renderTestHistory(self::TYPE_ALL);
    }

    /**
     * @return string
     */
    public function renderTestHistoryComplete(): string
    {
        return $this->renderTestHistory(self::TYPE_COMPLETE);
    }

    /**
     * @return string
     */
    public function renderTestHistoryIncomplete(): string
    {
        return $this->renderTestHistory(self::TYPE_INCOMPLETE);
    }

    /**
     * @param string $testUuid
     * @return string
     */
    public function renderTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            AppHelpers::redirect('/tests/new');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            AppHelpers::redirect('/');
        }

        if ($test->isComplete()) {
            return $this->renderTestComplete($test);
        }

        return $this->renderTestIncomplete($testUuid);
    }

    /**
     * @param Test $test
     * @return string
     */
    private function renderTestComplete(Test $test): string
    {
        $testDataHelpers = $this->container->get(TestDataHelpers::class);
        $testData = $testDataHelpers->list($test);

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
            'testData' => $testData
        ];

        return $this->render(
            'tests/completed.html.twig',
            $data
        );
    }

    /**
     * @param string $testUuid
     * @return string
     */
    private function renderTestIncomplete(string $testUuid): string
    {
        return $this->render(
            'tests/test.html.twig', [
                'testUuid' => $testUuid
            ]
        );
    }

    /**
     * @param null|string $column
     * @param null|string $direction
     * @return array
     */
    private function validateSort(?string $column, ?string $direction): array {
        if (is_null($column)) {
            $column = 'timeStarted';
        }

        if (is_null($direction)) {
            $direction = 'DESC';
        }

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