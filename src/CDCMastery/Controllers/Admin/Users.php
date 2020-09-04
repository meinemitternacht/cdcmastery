<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Tests;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Users\Role;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserSupervisorAssociations;
use CDCMastery\Models\Users\UserTrainingManagerAssociations;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Users extends Admin
{
    private const TYPE_COMPLETE = 0;
    private const TYPE_INCOMPLETE = 1;

    private UserCollection $users;
    private BaseCollection $bases;
    private RoleCollection $roles;
    private OfficeSymbolCollection $symbols;
    private TestStats $test_stats;
    private TestCollection $tests;
    private TestDataHelpers $test_data_helpers;
    private AfscCollection $afscs;
    private UserAfscAssociations $afsc_assocs;
    private UserTrainingManagerAssociations $tm_assocs;
    private UserSupervisorAssociations $su_assocs;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        RoleCollection $roles,
        OfficeSymbolCollection $symbols,
        TestStats $test_stats,
        TestCollection $tests,
        TestDataHelpers $test_data_helpers,
        AfscCollection $afscs,
        UserAfscAssociations $afsc_assocs,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->users = $users;
        $this->bases = $bases;
        $this->roles = $roles;
        $this->symbols = $symbols;
        $this->test_stats = $test_stats;
        $this->tests = $tests;
        $this->test_data_helpers = $test_data_helpers;
        $this->afscs = $afscs;
        $this->afsc_assocs = $afsc_assocs;
        $this->tm_assocs = $tm_assocs;
        $this->su_assocs = $su_assocs;
    }

    private function get_user(string $uuid): ?User
    {
        $user = $this->users->fetch($uuid);

        if ($user === null || $user->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified User does not exist');

            $this->redirect('/admin/users')->send();
            exit;
        }

        return $user;
    }

    private function validate_sort(string $column, string $direction): ?UserSortOption
    {
        try {
            return new UserSortOption($column,
                                      strtolower($direction ?? 'asc') === 'asc'
                                          ? UserSortOption::SORT_ASC
                                          : UserSortOption::SORT_DESC);
        } catch (Throwable $e) {
            unset($e);
            return null;
        }
    }

    public function do_delete_incomplete_tests(string $uuid): Response
    {
        $user = $this->get_user($uuid);
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
                                'There are no tests to delete for this user');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $this->tests->deleteArray(
            TestHelpers::listUuid($tests)
        );

        $this->flash()->add(MessageTypes::SUCCESS,
                            'All incomplete tests for this user have been removed from the database');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function toggle_disabled(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $was_disabled = $user->isDisabled();
        $user->setDisabled(!$was_disabled);
        $this->users->save($user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The specified user account has been ' .
            ($was_disabled
                ? 'reactivated'
                : 'disabled')
        );

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function show_home(): Response
    {
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sortCol
            ? [self::validate_sort($sortCol, $sortDir)]
            : [
                new UserSortOption(UserSortOption::COL_NAME_LAST),
                new UserSortOption(UserSortOption::COL_NAME_FIRST),
                new UserSortOption(UserSortOption::COL_RANK),
                new UserSortOption(UserSortOption::COL_BASE),
            ];

        $sort[] = new UserSortOption(UserSortOption::COL_UUID);

        $n_users = $this->users->count();
        $users = $this->users->fetchAll($sort, $curPage * $numRecords, $numRecords);
        $bases = $this->bases->fetchArray(array_map(static function (User $v): string {
            return $v->getBase();
        }, $users));
        $roles = $this->roles->fetchArray(array_map(static function (User $v): string {
            return $v->getRole();
        }, $users));
        $symbols = $this->symbols->fetchArray(array_map(static function (User $v): ?string {
            return $v->getOfficeSymbol();
        }, $users));

        $pagination = ArrayPaginator::buildLinks(
            '/admin/users',
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_users,
                $numRecords
            ),
            $numRecords,
            $sortCol,
            $sortDir
        );

        $data = [
            'users' => $users,
            'bases' => $bases,
            'roles' => $roles,
            'symbols' => $symbols,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sortCol,
                'dir' => $sortDir,
            ],
        ];

        return $this->render(
            "admin/users/list.html.twig",
            $data
        );
    }

    public function show_disable(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        if ($user->isDisabled()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'That user is already disabled'
            );

            $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $data = [
            'user' => $user,
            'base' => $this->bases->fetch($user->getBase()),
            'role' => $this->roles->fetch($user->getRole()),
        ];

        return $this->render(
            'admin/users/disable.html.twig',
            $data
        );
    }

    public function show_reactivate(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        if (!$user->isDisabled()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'That user is not currently disabled'
            );

            $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $data = [
            'user' => $user,
            'base' => $this->bases->fetch($user->getBase()),
            'role' => $this->roles->fetch($user->getRole()),
        ];

        return $this->render(
            'admin/users/reactivate.html.twig',
            $data
        );
    }

    public function show_profile(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $this->symbols->fetch($u_symbol);
        }

        $incomplete_tests = array_filter(
            $this->tests->fetchAllByUser($user),
            function (Test $v) {
                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);
        $afsc_assocs = $this->afsc_assocs->fetchAllByUser($user);
        $tm_assocs = $this->users->fetchArray($this->tm_assocs->fetchAllByUser($user), $user_sort);
        $su_assocs = $this->users->fetchArray($this->su_assocs->fetchAllByUser($user), $user_sort);

        $subs = null;

        switch ($role->getType()) {
            case Role::TYPE_SUPERVISOR:
                $subs = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                break;
        }

        $data = [
            'user' => $user,
            'base' => $base,
            'symbol' => $symbol ?? null,
            'role' => $role,
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
            ],
            'assocs' => [
                'tm' => $tm_assocs,
                'su' => $su_assocs,
                'subordinates' => $subs,
            ],
            'stats' => [
                'tests' => [
                    'complete' => [
                        'count' => $this->test_stats->userCountOverall($user),
                        'avg' => $this->test_stats->userAverageOverall($user),
                    ],
                    'incomplete' => [
                        'count' => count($incomplete_tests),
                    ],
                ],
            ],
        ];

        return $this->render(
            'admin/users/profile.html.twig',
            $data
        );
    }

    public function show_delete_incomplete_tests(string $uuid): Response
    {
        $user = $this->get_user($uuid);
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
                'There are no incomplete tests to delete for this user'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        uasort(
            $tests,
            function (Test $a, Test $b) {
                return $b->getTimeStarted()->format('U') <=> $a->getTimeStarted()->format('U');
            }
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'user' => $user,
            'tests' => $tests,
        ];

        return $this->render(
            'admin/users/tests/delete-incomplete.html.twig',
            $data
        );
    }

    public function show_test(string $uuid, string $test_uuid): Response
    {
        $user = $this->get_user($uuid);
        $test = $this->tests->fetch($test_uuid);

        if (!$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            $this->redirect("/admin/users/{$user->getUuid()}");
        }

        if (!$test->isComplete()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Tests that are still in-progress cannot be viewed'
            );

            $this->redirect("/admin/users/{$user->getUuid()}");
        }

        return $this->show_test_complete($user, $test);
    }

    private function show_test_complete(User $user, Test $test): Response
    {
        $testData = $this->test_data_helpers->list($test);

        $data = [
            'user' => $user,
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
            'admin/users/tests/completed.html.twig',
            $data
        );
    }

    private function show_test_history(User $user, int $type): Response
    {
        switch ($type) {
            case self::TYPE_COMPLETE:
                $path = "/admin/users/{$user->getUuid()}/tests";
                $typeStr = 'complete';
                $template = 'admin/users/tests/history-complete.html.twig';
                break;
            case self::TYPE_INCOMPLETE:
                $path = "/admin/users/{$user->getUuid()}/tests/incomplete";
                $typeStr = 'incomplete';
                $template = 'admin/users/tests/history-incomplete.html.twig';
                break;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'We made a mistake when processing that request'
                );

                return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $sortCol = $this->getRequest()->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->getRequest()->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->getRequest()->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->getRequest()->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        [$col, $dir] = Tests::validate_test_sort($sortCol, $sortDir);
        $userTests = $this->tests->fetchAllByUser($user,
                                                  [
                                                      $col => $dir,
                                                  ]);

        if (empty($userTests)) {
            $this->flash()->add(
                MessageTypes::INFO,
                'This user has not taken any tests'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $userTests = array_filter(
            $userTests,
            function (Test $v) use ($type) {
                switch ($type) {
                    case self::TYPE_COMPLETE:
                        if ($v->getScore() > 0 && $v->getTimeCompleted() !== null) {
                            return true;
                        }
                        break;
                    case self::TYPE_INCOMPLETE:
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
                $type === self::TYPE_INCOMPLETE
                    ? 'This account does not have ' . $typeStr . ' tests'
                    : 'This account has not taken any ' . $typeStr . ' tests'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
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
                'user' => $user,
                'tests' => $filteredList,
                'pagination' => $pagination,
                'sort' => [
                    'col' => $sortCol,
                    'dir' => $sortDir,
                ],
            ]
        );
    }

    public function show_test_history_complete(string $uuid): Response
    {
        return $this->show_test_history($this->get_user($uuid), self::TYPE_COMPLETE);
    }

    public function show_test_history_incomplete(string $uuid): Response
    {
        return $this->show_test_history($this->get_user($uuid), self::TYPE_INCOMPLETE);
    }
}