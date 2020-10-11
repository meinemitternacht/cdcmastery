<?php
declare(strict_types=1);

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Tests;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthActions;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Archive\ArchiveReader;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscActions;
use CDCMastery\Models\Users\Associations\Subordinate\SubordinateActions;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserActions;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use CDCMastery\Models\Users\Associations\Subordinate\UserSupervisorAssociations;
use CDCMastery\Models\Users\Associations\Subordinate\UserTrainingManagerAssociations;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Users extends Admin
{
    private EmailCollection $emails;
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
    private PasswordResetCollection $pw_resets;
    private ActivationCollection $activations;
    private UserHelpers $user_helpers;

    /**
     * Users constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param EmailCollection $emails
     * @param UserCollection $users
     * @param BaseCollection $bases
     * @param RoleCollection $roles
     * @param OfficeSymbolCollection $symbols
     * @param TestStats $test_stats
     * @param TestCollection $tests
     * @param TestDataHelpers $test_data_helpers
     * @param AfscCollection $afscs
     * @param \CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations $afsc_assocs
     * @param UserTrainingManagerAssociations $tm_assocs
     * @param UserSupervisorAssociations $su_assocs
     * @param PasswordResetCollection $pw_resets
     * @param ActivationCollection $activations
     * @param UserHelpers $user_helpers
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        EmailCollection $emails,
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
        UserSupervisorAssociations $su_assocs,
        PasswordResetCollection $pw_resets,
        ActivationCollection $activations,
        UserHelpers $user_helpers
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->emails = $emails;
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
        $this->pw_resets = $pw_resets;
        $this->activations = $activations;
        $this->user_helpers = $user_helpers;
    }

    private function get_user(string $uuid): User
    {
        $user = $this->users->fetch($uuid);

        if ($user === null || $user->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified User does not exist');

            $this->trigger_request_debug(__METHOD__);
            $this->redirect('/admin/users')->send();
            exit;
        }

        return $user;
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

    public function do_ajax_user_search(): void
    {
        $this->session->save();
        $term = $this->request->query->get('term');

        $matches = [];
        $len = strlen($term);
        if (!$term || $len < 3 || $len > 255) { // min 3 characters, max 255 characters
            goto out_return;
        }

        $terms = [$term];
        if (str_contains($term, ' ')) {
            $terms = explode(' ', $term);
        }

        $terms = array_slice($terms, 0, 5); // maximum of 5 terms
        foreach ($terms as $term) {
            $matches[] = $this->users->search($term);
        }

        if ($matches) {
            $matches = count($matches) > 1
                ? array_intersect_key(...$matches)
                : array_shift($matches);

            if (!$matches) {
                goto out_return;
            }

            $matches = array_map(static function (User $v): array {
                return [
                    'uuid' => $v->getUuid(),
                    'name' => "{$v->getLastName()}, {$v->getFirstName()} {$v->getRank()}",
                    'url' => "/admin/users/{$v->getUuid()}",
                ];
            }, $matches);

            uasort($matches, static function (array $a, array $b): int {
                return $a[ 'name' ] <=> $b[ 'name' ];
            });

            $matches = array_slice($matches, 0, 30);
        }

        out_return:
        /** @noinspection UnusedFunctionResultInspection */
        (new JsonResponse(array_values($matches)))->send();
        exit;
    }

    /**
     * @param string $uuid
     * @return Response
     * @throws AccessDeniedException
     */
    public function do_edit(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'handle',
            'email',
            'rank',
            'first_name',
            'last_name',
            'base',
            'time_zone',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/edit");
        }

        return (new UserActions($this->log,
                                $this->auth_helpers,
                                $this->bases,
                                $this->symbols,
                                $this->roles,
                                $this->users,
                                $this->user_helpers))
            ->do_edit($this->flash(),
                      $this->request,
                      $user,
                      "/admin/users/{$user->getUuid()}",
                      "/admin/users/{$user->getUuid()}/edit");
    }

    public function do_afsc_association_add(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'new_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        return (new UserAfscActions($this->log,
                                    $this->afscs,
                                    $this->afsc_assocs))
            ->do_afsc_association_add($this->flash(),
                                      $this->request,
                                      $user,
                                      $this->get_user($this->auth_helpers->get_user_uuid()),
                                      true,
                                      "/admin/users/{$user->getUuid()}/afsc",
                                      "/admin/users/{$user->getUuid()}/afsc");
    }

    public function do_afsc_association_approve(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'approve_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        return (new UserAfscActions($this->log,
                                    $this->afscs,
                                    $this->afsc_assocs))
            ->do_afsc_association_approve($this->flash(),
                                          $this->request,
                                          $user,
                                          $this->get_user($this->auth_helpers->get_user_uuid()),
                                          "/admin/users/{$user->getUuid()}/afsc",
                                          "/admin/users/{$user->getUuid()}/afsc");
    }

    public function do_afsc_association_remove(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'del_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        return (new UserAfscActions($this->log,
                                    $this->afscs,
                                    $this->afsc_assocs))
            ->do_afsc_association_remove($this->flash(),
                                         $this->request,
                                         $user,
                                         $this->get_user($this->auth_helpers->get_user_uuid()),
                                         "/admin/users/{$user->getUuid()}/afsc",
                                         "/admin/users/{$user->getUuid()}/afsc");
    }

    public function do_supervisor_association_add(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'new_super',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_association_add($this->flash(),
                                 $this->request,
                                 Role::TYPE_SUPERVISOR,
                                 $user,
                                 $this->get_user($this->auth_helpers->get_user_uuid()),
                                 "/admin/users/{$user->getUuid()}/supervisors",
                                 "/admin/users/{$user->getUuid()}/supervisors");
    }

    public function do_supervisor_association_remove(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'del_super',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_association_remove($this->flash(),
                                    $this->request,
                                    Role::TYPE_SUPERVISOR,
                                    $user,
                                    $this->get_user($this->auth_helpers->get_user_uuid()),
                                    "/admin/users/{$user->getUuid()}/supervisors",
                                    "/admin/users/{$user->getUuid()}/supervisors");
    }

    public function do_tm_association_add(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'new_tm',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_association_add($this->flash(),
                                 $this->request,
                                 Role::TYPE_TRAINING_MANAGER,
                                 $user,
                                 $this->get_user($this->auth_helpers->get_user_uuid()),
                                 "/admin/users/{$user->getUuid()}/training-managers",
                                 "/admin/users/{$user->getUuid()}/training-managers");
    }

    public function do_tm_association_remove(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'del_tm',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_association_remove($this->flash(),
                                    $this->request,
                                    Role::TYPE_TRAINING_MANAGER,
                                    $user,
                                    $this->get_user($this->auth_helpers->get_user_uuid()),
                                    "/admin/users/{$user->getUuid()}/training-managers",
                                    "/admin/users/{$user->getUuid()}/training-managers");
    }

    public function do_delete_incomplete_tests(string $uuid): Response
    {
        $user = $this->get_user($uuid);
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

        if (!is_array($tests) || !$tests) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no tests to delete for this user');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $tests_str = implode(', ', array_map(static function (Test $v): string {
            if (!$v->getTimeStarted()) {
                return $v->getUuid();
            }
            return "{$v->getUuid()} [{$v->getTimeStarted()->format(DateTimeHelpers::DT_FMT_DB)}]";
        }, $tests));
        $this->log->info("delete incomplete tests :: {$user->getName()} [{$user->getUuid()}] :: {$tests_str} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->tests->deleteArray(TestHelpers::listUuid($tests));

        $this->flash()->add(MessageTypes::SUCCESS,
                            'All incomplete tests for this user have been removed from the database');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function do_password_reset(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $initiator = $this->get_user($this->auth_helpers->get_user_uuid());

        return (new AuthActions($this->log,
                                $this->pw_resets,
                                $this->activations,
                                $this->emails))
            ->do_password_reset($this->flash(),
                                $user,
                                $initiator,
                                "/admin/users/{$user->getUuid()}",
                                "/admin/users/{$user->getUuid()}");
    }

    public function do_resend_activation(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $initiator = $this->get_user($this->auth_helpers->get_user_uuid());

        return (new AuthActions($this->log,
                                $this->pw_resets,
                                $this->activations,
                                $this->emails))
            ->do_resend_activation($this->flash(),
                                   $user,
                                   $initiator,
                                   "/admin/users/{$user->getUuid()}",
                                   "/admin/users/{$user->getUuid()}");
    }

    public function do_subordinates_add(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $params = [
            'new_users',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/subordinates");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_subordinates_add($this->flash(),
                                  $this->request,
                                  $role,
                                  $user,
                                  $this->get_user($this->auth_helpers->get_user_uuid()),
                                  "/admin/users/{$user->getUuid()}/subordinates",
                                  "/admin/users/{$user->getUuid()}/subordinates");

        out_bad_role:
        $this->trigger_request_debug(__METHOD__);
        $this->flash()->add(MessageTypes::WARNING,
                            'This user must be a training manager or a supervisor to have subordinates');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function do_subordinates_remove(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $params = [
            'del_users',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/users/{$user->getUuid()}/subordinates");
        }

        return (new SubordinateActions($this->log,
                                       $this->roles,
                                       $this->users,
                                       $this->su_assocs,
                                       $this->tm_assocs))
            ->do_subordinates_remove($this->flash(),
                                     $this->request,
                                     $role,
                                     $user,
                                     $this->get_user($this->auth_helpers->get_user_uuid()),
                                     "/admin/users/{$user->getUuid()}/subordinates",
                                     "/admin/users/{$user->getUuid()}/subordinates");

        out_bad_role:
        $this->trigger_request_debug(__METHOD__);
        $this->flash()->add(MessageTypes::WARNING,
                            'This user must be a training manager or a supervisor to have subordinates');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function toggle_disabled(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        if (!$this->auth_helpers->assert_admin()) {
            $role = $this->roles->fetch($user->getRole());

            if (!$role) {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The system encountered an error fetching the specified user'
                );

                return $this->redirect("/admin/users/{$user->getUuid()}");
            }

            switch ($role->getType()) {
                case Role::TYPE_ADMIN:
                case Role::TYPE_SUPER_ADMIN:
                case Role::TYPE_QUESTION_EDITOR:
                    $this->log->alert("disable user fail :: tgt user {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");
                    $this->flash()->add(
                        MessageTypes::ERROR,
                        'Your account type cannot disable that user'
                    );

                    return $this->redirect("/admin/users/{$user->getUuid()}");
            }
        }

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

        $this->log->info(($was_disabled
                             ? 'reactivate'
                             : 'disable') . " user :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function show_home(): Response
    {
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->filter_int_default(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->filter_int_default(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sortCol
            ? [$this->validate_sort($sortCol, $sortDir)]
            : [
                new UserSortOption(UserSortOption::COL_NAME_LAST),
                new UserSortOption(UserSortOption::COL_NAME_FIRST),
                new UserSortOption(UserSortOption::COL_RANK),
                new UserSortOption(UserSortOption::COL_BASE),
            ];

        $sort[] = new UserSortOption(UserSortOption::COL_UUID);

        $n_users = $this->users->count();
        $users = $this->users->fetchAll($sort, $curPage * $numRecords, $numRecords);

        $base_uuids = [];
        $role_uuids = [];
        $office_symbol_uuids = [];
        foreach ($users as $user) {
            $base_uuids[ $user->getBase() ] = true;
            $role_uuids[ $user->getRole() ] = true;
            $office_symbol_uuids[ $user->getOfficeSymbol() ] = true;
        }

        $bases = $this->bases->fetchArray(array_keys($base_uuids));
        $roles = $this->roles->fetchArray(array_keys($role_uuids));
        $symbols = $this->symbols->fetchArray(array_keys($office_symbol_uuids));

        $pagination = ArrayPaginator::buildLinks(
            '/admin/users',
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_users,
                $numRecords
            ),
            $numRecords,
            $n_users,
            $sortCol,
            $sortDir
        );

        $data = [
            'users' => $users,
            'bases' => $bases,
            'roles' => $roles,
            'symbols' => $symbols,
            'pagination' => $pagination,
            'n_users' => $n_users,
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

    public function show_edit(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $this->symbols->fetch($u_symbol);
        }

        $symbols = $this->symbols->fetchAll();
        uasort($symbols, static function (OfficeSymbol $a, OfficeSymbol $b): int {
            return $a->getSymbol() <=> $b->getSymbol();
        });

        $data = [
            'user' => $user,
            'base' => $base,
            'bases' => $this->bases->fetchAll(),
            'symbol' => $symbol ?? null,
            'symbols' => $symbols,
            'ranks' => UserHelpers::listRanks(),
            'role' => $role,
            'roles' => $this->roles->fetchAll(),
            'time_zones' => DateTimeHelpers::list_time_zones(),
        ];

        return $this->render(
            'admin/users/edit.html.twig',
            $data
        );
    }

    public function show_afsc_associations(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);
        $afsc_assocs = $this->afsc_assocs->fetchAllByUser($user);

        $cmp = static function (Afsc $a, Afsc $b): int {
            return $a->getName() . $a->getEditCode() <=> $b->getName() . $b->getEditCode();
        };

        uasort($afscs, $cmp);

        $available = array_filter(
            $afscs,
            static function (Afsc $v): bool {
                return !$v->isHidden() && !$v->isObsolete();
            }
        );

        $data = [
            'user' => $user,
            'role' => $this->roles->fetch($user->getRole()),
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
                'available' => array_diff_key($available, array_flip($afsc_assocs->getAfscs())),
            ],
        ];

        return $this->render(
            'admin/users/afsc/associations.html.twig',
            $data
        );
    }

    public function show_supervisor_associations(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);

        if (!$base) {
            throw new RuntimeException("invalid base :: {$user->getBase()}");
        }

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $su_assocs = $this->users->fetchArray($this->su_assocs->fetchAllByUser($user), $user_sort);

        $su_uuids = [];
        foreach ($su_assocs as $su_assoc) {
            $su_uuids[] = $su_assoc->getUuid();
        }

        $su_uuids = array_flip($su_uuids);

        $avail_supers = $this->users->filterByBase($base, $user_sort);
        $avail_supers = array_filter(
            $avail_supers,
            static function (User $v) use ($su_uuids, $role): bool {
                return !isset($su_uuids[ $v->getUuid() ]) &&
                       $v->getRole() === $role->getUuid();
            }
        );

        $data = [
            'user' => $user,
            'role' => $this->roles->fetch($user->getRole()),
            'base' => $base,
            'assocs' => [
                'su' => $su_assocs,
                'available' => $avail_supers,
            ],
        ];

        return $this->render(
            'admin/users/supervisors/associations.html.twig',
            $data
        );
    }

    public function show_tm_associations(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetchType(Role::TYPE_TRAINING_MANAGER);

        if (!$base) {
            throw new RuntimeException("invalid base :: {$user->getBase()}");
        }

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $tm_assocs = $this->users->fetchArray($this->tm_assocs->fetchAllByUser($user), $user_sort);

        $tm_uuids = [];
        foreach ($tm_assocs as $tm_assoc) {
            $tm_uuids[] = $tm_assoc->getUuid();
        }

        $tm_uuids = array_flip($tm_uuids);

        $avail_tms = $this->users->filterByBase($base, $user_sort);
        $avail_tms = array_filter(
            $avail_tms,
            static function (User $v) use ($tm_uuids, $role): bool {
                return !isset($tm_uuids[ $v->getUuid() ]) &&
                       $v->getRole() === $role->getUuid();
            }
        );

        $data = [
            'user' => $user,
            'role' => $this->roles->fetch($user->getRole()),
            'base' => $base,
            'assocs' => [
                'tm' => $tm_assocs,
                'available' => $avail_tms,
            ],
        ];

        return $this->render(
            'admin/users/training-managers/associations.html.twig',
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

            return $this->redirect("/admin/users/{$user->getUuid()}");
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

            return $this->redirect("/admin/users/{$user->getUuid()}");
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

    public function show_password_reset(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $data = [
            'user' => $user,
            'base' => $this->bases->fetch($user->getBase()),
            'role' => $this->roles->fetch($user->getRole()),
        ];

        return $this->render(
            'admin/users/password-reset.html.twig',
            $data
        );
    }

    public function show_resend_activation(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $data = [
            'user' => $user,
            'base' => $this->bases->fetch($user->getBase()),
            'role' => $this->roles->fetch($user->getRole()),
        ];

        return $this->render(
            'admin/users/resend-activation.html.twig',
            $data
        );
    }

    public function show_profile(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            throw new RuntimeException("invalid role :: {$user->getRole()}");
        }

        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $this->symbols->fetch($u_symbol);
        }

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
                        'count' => $this->test_stats->userCountIncompleteOverall($user),
                    ],
                    'practice' => [
                        'count' => $this->test_stats->userCountPracticeOverall($user),
                    ],
                ],
            ],
        ];

        return $this->render(
            'admin/users/profile.html.twig',
            $data
        );
    }

    public function show_subordinates(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $cur = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                $available = $this->users->fetchArray($this->tm_assocs->fetchUnassociatedByTrainingManager($user),
                                                      $user_sort);
                break;
            case Role::TYPE_SUPERVISOR:
                $cur = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);
                $available = $this->users->fetchArray($this->su_assocs->fetchUnassociatedBySupervisor($user),
                                                      $user_sort);
                break;
            default:
                goto out_bad_role;
        }

        $data = [
            'user' => $user,
            'role' => $role,
            'base' => $base,
            'assocs' => [
                'cur' => $cur,
                'available' => $available,
            ],
        ];

        return $this->render(
            'admin/users/subordinates.html.twig',
            $data
        );

        out_bad_role:
        $this->flash()->add(MessageTypes::WARNING,
                            'This user must be a training manager or a supervisor to have subordinates');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function show_delete_incomplete_tests(string $uuid): Response
    {
        $user = $this->get_user($uuid);
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
                'There are no incomplete tests to delete for this user'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                return $b->getTimeStarted() <=> $a->getTimeStarted();
            }
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'user' => $user,
            'role' => $this->roles->fetch($user->getRole()),
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

        if (!$test || !$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $user = $this->users->fetch($test->getUserUuid());

        if (!$user || !$user->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The user account for the specified test could not be found'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        if (!$test->getTimeCompleted() &&
            $user->getUuid() === $this->auth_helpers->get_user_uuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'You cannot view your own incomplete test'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $test_data = $this->test_data_helpers->list($test);

        $time_started = $test->getTimeStarted();
        if ($time_started) {
            $time_started = $time_started->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $time_completed = $test->getTimeCompleted();
        if ($time_completed) {
            $time_completed = $time_completed->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $n_questions = $test->getNumQuestions();
        $n_answered = $this->test_data_helpers->count($test);

        $archived_data = null;
        if ($test->isArchived()) {
            $archived_data = (new ArchiveReader($this->log))->fetch_test($user, $test);
        }

        $data = [
            'user' => $user,
            'test' => $test,
            'role' => $this->roles->fetch($user->getRole()),
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
            'allowScoring' => $this->auth_helpers->assert_admin() && !$time_completed,
            'archivedData' => $archived_data,
        ];

        return $this->render(
            $time_completed
                ? 'admin/users/tests/completed.html.twig'
                : 'admin/users/tests/incompleted.html.twig',
            $data
        );
    }

    private function show_test_history(User $user, int $type): Response
    {
        switch ($type) {
            case Test::STATE_COMPLETE:
                $path = "/admin/users/{$user->getUuid()}/tests";
                $typeStr = 'complete';
                $template = 'admin/users/tests/history-complete.html.twig';
                break;
            case Test::STATE_INCOMPLETE:
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

        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->filter_int_default(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->filter_int_default(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

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
            static function (Test $v) use ($type) {
                switch ($type) {
                    case Test::STATE_COMPLETE:
                        if ($v->getTimeCompleted() !== null) {
                            return true;
                        }
                        break;
                    case Test::STATE_INCOMPLETE:
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
                $type === Test::STATE_INCOMPLETE
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
            count($userTests),
            $col,
            $dir
        );

        return $this->render(
            $template,
            [
                'user' => $user,
                'role' => $this->roles->fetch($user->getRole()),
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
        return $this->show_test_history($this->get_user($uuid), Test::STATE_COMPLETE);
    }

    public function show_test_history_incomplete(string $uuid): Response
    {
        return $this->show_test_history($this->get_user($uuid), Test::STATE_INCOMPLETE);
    }
}
