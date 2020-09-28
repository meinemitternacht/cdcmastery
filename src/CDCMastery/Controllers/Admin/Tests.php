<?php


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Tests extends Admin
{
    private TestCollection $tests;
    private UserCollection $users;
    private TestDataHelpers $test_data;

    /**
     * Tests constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param TestCollection $tests
     * @param UserCollection $users
     * @param TestDataHelpers $test_data
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        TestCollection $tests,
        UserCollection $users,
        TestDataHelpers $test_data
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->tests = $tests;
        $this->users = $users;
        $this->test_data = $test_data;
    }

    public function show_test(string $test_uuid): Response
    {
        $test = $this->tests->fetch($test_uuid);

        if (!$test || !$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            return $this->redirect("/admin/tests");
        }

        $user = $this->users->fetch($test->getUserUuid());

        if (!$user || !$user->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The user account for the specified test could not be found'
            );

            return $this->redirect("/admin/tests");
        }

        $test_data = $this->test_data->list($test);

        $time_started = $test->getTimeStarted();
        if ($time_started) {
            $time_started = $time_started->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $time_completed = $test->getTimeCompleted();
        if ($time_completed) {
            $time_completed = $time_completed->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $n_questions = $test->getNumQuestions();
        $n_answered = $this->test_data->count($test);

        $data = [
            'user' => $user,
            'timeStarted' => $time_started,
            'timeCompleted' => $time_completed,
            'afscList' => AfscHelpers::listNames($test->getAfscs()),
            'numQuestions' => $n_questions,
            'numAnswered' => $n_answered,
            'numMissed' => $test->getNumMissed(),
            'pctDone' => round(($n_answered / $n_questions) * 100, 2),
            'score' => $test->getScore(),
            'isArchived' => $test->isArchived(),
            'testData' => $test_data,
        ];

        return $this->render(
            $time_completed
                ? 'admin/tests/completed.html.twig'
                : 'admin/tests/incompleted.html.twig',
            $data
        );
    }

    private function show_tests(int $type): Response
    {
        $sortCol = $this->getRequest()->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->getRequest()->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->getRequest()->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->getRequest()->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        switch ($type) {
            case Test::TYPE_COMPLETE:
                $path = "/admin/tests";
                $typeStr = 'complete';
                $template = 'admin/tests/list-complete.html.twig';
                $sortCol ??= 'timeCompleted';
                $sortDir ??= 'DESC';
                break;
            case Test::TYPE_INCOMPLETE:
                $path = "/admin/tests/incomplete";
                $typeStr = 'incomplete';
                $template = 'admin/tests/list-incomplete.html.twig';
                $sortCol ??= 'timeStarted';
                $sortDir ??= 'DESC';
                break;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'We made a mistake when processing that request'
                );

                return $this->redirect('/admin/tests');
        }

        [$col, $dir] = \CDCMastery\Controllers\Tests::validate_test_sort($sortCol, $sortDir);
        $n_tests = $this->tests->countAll($type);
        $tests = $this->tests->fetchAll($type, [$col => $dir], $curPage * $numRecords, $numRecords);

        if (!$tests) {
            $this->flash()->add(
                MessageTypes::INFO,
                "There are no {$typeStr} tests in the database"
            );

            return $this->redirect("/");
        }

        $user_uuids = array_map(static function (Test $v): string {
            return $v->getUserUuid();
        }, $tests);

        $pagination = ArrayPaginator::buildLinks(
            $path,
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_tests,
                $numRecords
            ),
            $numRecords,
            $n_tests,
            $col,
            $dir
        );

        return $this->render(
            $template,
            [
                'users' => $this->users->fetchArray($user_uuids),
                'tests' => TestHelpers::formatHtml($tests),
                'pagination' => $pagination,
                'sort' => [
                    'col' => $sortCol,
                    'dir' => $sortDir,
                ],
            ]
        );
    }

    public function show_tests_complete(): Response
    {
        return $this->show_tests(Test::TYPE_COMPLETE);
    }

    public function show_tests_incomplete(): Response
    {
        return $this->show_tests(Test::TYPE_INCOMPLETE);
    }
}