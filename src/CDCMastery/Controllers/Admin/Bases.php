<?php
declare(strict_types=1);

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\Bases\Bases as BaseStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Twig\CreateSortLink;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\UserCollection;
use DateTime;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Bases extends Admin
{
    private UserCollection $users;
    private BaseCollection $bases;
    private BaseStats $stats;
    private TestCollection $tests;
    private TestDataHelpers $test_data;
    private OfficeSymbolCollection $symbols;
    private RoleCollection $roles;

    /**
     * Bases constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param UserCollection $users
     * @param BaseCollection $bases
     * @param BaseStats $base_stats
     * @param TestCollection $tests
     * @param TestDataHelpers $test_data
     * @param OfficeSymbolCollection $symbols
     * @param RoleCollection $roles
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        UserCollection $users,
        BaseCollection $bases,
        BaseStats $base_stats,
        TestCollection $tests,
        TestDataHelpers $test_data,
        OfficeSymbolCollection $symbols,
        RoleCollection $roles
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->users = $users;
        $this->bases = $bases;
        $this->stats = $base_stats;
        $this->tests = $tests;
        $this->test_data = $test_data;
        $this->symbols = $symbols;
        $this->roles = $roles;
    }

    private function get_base(string $uuid): Base
    {
        $base = $this->bases->fetch($uuid);

        if ($base === null || $base->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified Base does not exist');

            $this->trigger_request_debug(__METHOD__);
            $this->redirect('/admin/bases')->send();
            exit;
        }

        return $base;
    }

    private function validate_sort(string $column, string $direction): ?ISortOption
    {
        try {
            return new UserSortOption($column,
                                      strtolower($direction ?? 'asc') === 'asc'
                                          ? ISortOption::SORT_ASC
                                          : ISortOption::SORT_DESC);
        } catch (Throwable $e) {
            $this->log->debug($e);
            unset($e);
            return null;
        }
    }

    public function do_add(?Base $base = null): Response
    {
        $edit = $base !== null;

        $params = [
            'name',
        ];

        if (!$this->checkParameters($params)) {
            goto out_return;
        }

        $name = $this->filter_string_default('name');

        $old_name = $edit
            ? $base->getName()
            : null;
        if (!$edit) {
            $base = new Base();
            $base->setUuid(UUID::generate());
        }

        $base->setName($name);

        foreach ($this->bases->fetchAll() as $db_base) {
            if ($edit && $db_base->getUuid() === $base->getUuid()) {
                continue;
            }

            if ($db_base->getName() === $name) {
                $this->flash()->add(MessageTypes::ERROR,
                                    "The specified Base '{$base->getName()}' already exists in the database");
                goto out_return;
            }
        }

        $this->bases->save($base);
        $edit
            ? $this->log->info("admin edit base :: {$old_name} -> {$base->getName()} :: {$base->getUuid()}")
            : $this->log->info("admin add base :: {$base->getName()} :: {$base->getUuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            $edit
                                ? "The specified Base '{$base->getName()}' was modified successfully"
                                : "The specified Base '{$base->getName()}' was added successfully");

        out_return:
        return $this->redirect("/admin/bases/{$base->getUuid()}");
    }

    public function do_edit(string $uuid): Response
    {
        return $this->do_add($this->get_base($uuid));
    }

    public function show_base_users(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $sort_col = $this->get(ArrayPaginator::VAR_SORT);
        $sort_dir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $cur_page = $this->filter_int_default(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $n_records = $this->filter_int_default(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sort_col
            ? [$this->validate_sort($sort_col, $sort_dir)]
            : [
                new UserSortOption(UserSortOption::COL_NAME_LAST),
                new UserSortOption(UserSortOption::COL_NAME_FIRST),
                new UserSortOption(UserSortOption::COL_RANK),
                new UserSortOption(UserSortOption::COL_BASE),
            ];

        $sort[] = new UserSortOption(UserSortOption::COL_UUID);

        $n_users = $this->users->countByBase($base);
        $base_users = $this->users->filterByBase($base, $sort, $cur_page * $n_records, $n_records);

        $tgt_symbols = [];
        $tgt_roles = [];
        foreach ($base_users as $user) {
            $tgt_symbols[ $user->getOfficeSymbol() ] = true;
            $tgt_roles[ $user->getRole() ] = true;
        }

        $symbols = $this->symbols->fetchArray(array_keys($tgt_symbols));
        $roles = $this->roles->fetchArray(array_keys($tgt_roles));

        $pagination = ArrayPaginator::buildLinks(
            "/admin/bases/{$uuid}/users",
            $cur_page,
            ArrayPaginator::calcNumPagesNoData(
                $n_users,
                $n_records
            ),
            $n_records,
            $n_users,
            $sort_col,
            $sort_dir
        );

        $data = [
            'users' => $base_users,
            'base' => $base,
            'roles' => $roles,
            'symbols' => $symbols,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sort_col,
                'dir' => $sort_dir,
            ],
        ];

        return $this->render(
            'admin/bases/users.html.twig',
            $data
        );
    }

    public function show_test(string $uuid, string $test_uuid): Response
    {
        $base = $this->get_base($uuid);
        $test = $this->tests->fetch($test_uuid);

        if (!$test || !$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            return $this->redirect("/admin/bases/{$base->getUuid()}");
        }

        $user = $this->users->fetch($test->getUserUuid());

        if (!$user || !$user->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The user account for the specified test could not be found'
            );

            return $this->redirect("/admin/bases/{$base->getUuid()}");
        }

        if (!$test->getTimeCompleted() &&
            $user->getUuid() === $this->auth_helpers->get_user_uuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'You cannot view your own incomplete test'
            );

            return $this->redirect("/admin/bases/{$base->getUuid()}");
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
            'base' => $base,
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
                ? 'admin/bases/tests/completed.html.twig'
                : 'admin/bases/tests/incompleted.html.twig',
            $data
        );
    }

    private function show_tests(string $uuid, int $type): Response
    {
        $base = $this->get_base($uuid);
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->filter_int_default(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->filter_int_default(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        switch ($type) {
            case Test::TYPE_COMPLETE:
                $path = "/admin/bases/{$base->getUuid()}/tests";
                $typeStr = 'complete';
                $template = 'admin/bases/tests/list-complete.html.twig';
                $sortCol ??= 'timeCompleted';
                $sortDir ??= 'DESC';
                break;
            case Test::TYPE_INCOMPLETE:
                $path = "/admin/bases/{$base->getUuid()}/tests/incomplete";
                $typeStr = 'incomplete';
                $template = 'admin/bases/tests/list-incomplete.html.twig';
                $sortCol ??= 'timeStarted';
                $sortDir ??= 'DESC';
                break;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'We made a mistake when processing that request'
                );

                return $this->redirect("/admin/bases/{$base->getUuid()}");
        }

        [$col, $dir] = \CDCMastery\Controllers\Tests::validate_test_sort($sortCol, $sortDir);
        $n_tests = $this->tests->countAllByBase($type, $base);
        $tests = $this->tests->fetchAllByBase($type, $base, [$col => $dir], $curPage * $numRecords, $numRecords);

        if (!$tests) {
            $this->flash()->add(
                MessageTypes::INFO,
                "There are no {$typeStr} tests in the database"
            );

            return $this->redirect("/admin/bases/{$base->getUuid()}");
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
                'base' => $base,
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

    public function show_tests_complete(string $uuid): Response
    {
        return $this->show_tests($uuid, Test::TYPE_COMPLETE);
    }

    public function show_tests_incomplete(string $uuid): Response
    {
        return $this->show_tests($uuid, Test::TYPE_INCOMPLETE);
    }

    public function show_home(): Response
    {
        $sort_col = $this->get(ArrayPaginator::VAR_SORT, 'name');
        $sort_dir = $this->get(ArrayPaginator::VAR_DIRECTION, CreateSortLink::DIR_ASC);

        $bases = $this->bases->fetchAll();
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while loading your user account');

            return $this->redirect('/auth/logout');
        }

        if ($user->getBase()) {
            $my_base = $this->bases->fetch($user->getBase());
        }

        usort(
            $bases,
            static function (Base $a, Base $b) use ($sort_col, $sort_dir): int {
                $l = $sort_dir === CreateSortLink::DIR_ASC
                    ? $a
                    : $b;
                $r = $sort_dir === CreateSortLink::DIR_ASC
                    ? $b
                    : $a;

                switch ($sort_col) {
                    case 'tests-complete':
                        return $l->getTestsComplete() <=> $r->getTestsComplete();
                    case 'tests-incomplete':
                        return $l->getTestsIncomplete() <=> $r->getTestsIncomplete();
                    case 'users':
                        return $l->getUsers() <=> $r->getUsers();
                    case 'name':
                    default:
                        return $l->getName() <=> $r->getName();
                }
            }
        );

        $data = [
            'bases' => $bases,
            'my_base' => $my_base ?? null,
            'sort' => [
                'col' => $sort_col,
                'dir' => $sort_dir,
            ],
        ];

        return $this->render(
            'admin/bases/list.html.twig',
            $data
        );
    }

    public function show_edit(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $data = [
            'base' => $base,
        ];

        return $this->render(
            "admin/bases/edit.html.twig",
            $data
        );
    }

    /**
     * @param array $stats
     * @param int $limit
     * @return array|null
     * @throws JsonException
     */
    private function format_overview_graph_data(array &$stats, int $limit = -1): ?array
    {
        /*
         *  [
         *    user_uuid => [
         *      tAvg => test average (float),
         *      tCount => test count (int)
         *    ]
         *  ]
         */

        $users = $this->users->fetchArray(array_keys($stats));

        $average_data = [];
        $count_data = [];
        $i = 0;
        foreach ($stats as $user_uuid => $stat) {
            if (!isset($users[ $user_uuid ])) {
                continue;
            }

            $name = $users[ $user_uuid ]->getName();
            $ll = $users[ $user_uuid ]->getLastLogin();
            $stats[ $user_uuid ][ 'name' ] = $name;
            $stats[ $user_uuid ][ 'last_login' ] = $ll
                ? $ll->format(DateTimeHelpers::DT_FMT_SHORT)
                : 'Never';

            if (++$i <= $limit && $limit !== -1) {
                $average_data[] = [
                    'toolTipContent' => <<<LBL
{$name}<br>
Average: {$stat['tAvg']}<br>
Tests: {$stat['tCount']}
LBL,
                    'x' => $i,
                    'y' => $stat[ 'tAvg' ],
                ];

                $count_data[] = [
                    'toolTipContent' => null,
                    'x' => $i,
                    'y' => $stat[ 'tCount' ],
                ];
            }
        }

        if (!$average_data || !$count_data) {
            return null;
        }

        return [
            'avg' => json_encode($average_data, JSON_THROW_ON_ERROR),
            'count' => json_encode($count_data, JSON_THROW_ON_ERROR),
        ];
    }

    public function show_overview(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $limit = 100;
        $base_users_avg_count = $this->stats->averageCountOverallByUser($base,
                                                                        (new DateTime())->modify('-1 year'));

        try {
            $graph_data = $this->format_overview_graph_data($base_users_avg_count, $limit);
        } catch (JsonException $e) {
            $this->log->debug($e);
            $graph_data = null;
        }

        $data = [
            'base' => $base,
            'bases' => $this->bases->fetchAll(),
            'graph' => $graph_data,
            'stats' => [
                'tests' => $this->stats->countOverall($base),
                'average' => $this->stats->averageOverall($base),
                'data' => $base_users_avg_count,
                'limit' => $limit,
            ],
        ];

        return $this->render(
            "admin/bases/overview.html.twig",
            $data
        );
    }
}