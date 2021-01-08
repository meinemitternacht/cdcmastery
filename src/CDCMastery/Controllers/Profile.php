<?php
declare(strict_types=1);


namespace CDCMastery\Controllers;


use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscActions;
use CDCMastery\Models\Users\Associations\Subordinate\SubordinateActions;
use CDCMastery\Models\Users\Roles\PendingRole;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserActions;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use CDCMastery\Models\Users\Associations\Subordinate\UserSupervisorAssociations;
use CDCMastery\Models\Users\Associations\Subordinate\UserTrainingManagerAssociations;
use DateTime;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Profile extends RootController
{
    private AuthHelpers $auth_helpers;
    private UserCollection $users;
    private BaseCollection $bases;
    private RoleCollection $roles;
    private PendingRoleCollection $pending_roles;
    private OfficeSymbolCollection $symbols;
    private TestStats $test_stats;
    private TestCollection $tests;
    private AfscCollection $afscs;
    private UserAfscAssociations $afsc_assocs;
    private UserTrainingManagerAssociations $tm_assocs;
    private UserSupervisorAssociations $su_assocs;
    private UserHelpers $user_helpers;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        RoleCollection $roles,
        PendingRoleCollection $pending_roles,
        OfficeSymbolCollection $symbols,
        TestStats $test_stats,
        TestCollection $tests,
        AfscCollection $afscs,
        UserAfscAssociations $afsc_assocs,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs,
        UserHelpers $user_helpers
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->users = $users;
        $this->bases = $bases;
        $this->roles = $roles;
        $this->pending_roles = $pending_roles;
        $this->symbols = $symbols;
        $this->test_stats = $test_stats;
        $this->tests = $tests;
        $this->afscs = $afscs;
        $this->afsc_assocs = $afsc_assocs;
        $this->tm_assocs = $tm_assocs;
        $this->su_assocs = $su_assocs;
        $this->user_helpers = $user_helpers;
    }

    private function get_user(string $uuid): User
    {
        $user = $this->users->fetch($uuid);

        if ($user === null || $user->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'You do not exist');

            $this->redirect('/')->send();
            exit;
        }

        return $user;
    }

    public function do_afsc_association_add(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        $params = [
            'new_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/afsc");
        }

        $override = false;
        if ($role) {
            switch ($role->getType()) {
                case Role::TYPE_TRAINING_MANAGER:
                case Role::TYPE_ADMIN:
                case Role::TYPE_SUPER_ADMIN:
                case Role::TYPE_QUESTION_EDITOR:
                    $override = true;
                    break;
            }
        }

        return (new UserAfscActions($this->log,
                                    $this->afscs,
                                    $this->afsc_assocs))
            ->do_afsc_association_add($this->flash(),
                                      $this->request,
                                      $user,
                                      $user,
                                      $override,
                                      '/profile/afsc',
                                      '/profile/afsc');
    }

    public function do_afsc_association_remove(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'del_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/afsc");
        }


        return (new UserAfscActions($this->log,
                                    $this->afscs,
                                    $this->afsc_assocs))
            ->do_afsc_association_remove($this->flash(),
                                         $this->request,
                                         $user,
                                         $user,
                                         '/profile/afsc',
                                         '/profile/afsc');
    }

    public function do_supervisor_association_add(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'new_super',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/supervisors");
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
                                 "/profile/supervisors",
                                 "/profile/supervisors");
    }

    public function do_supervisor_association_remove(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'del_super',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/supervisors");
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
                                    "/profile/supervisors",
                                    "/profile/supervisors");
    }

    public function do_tm_association_add(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'new_tm',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/training-managers");
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
                                 "/profile/training-managers",
                                 "/profile/training-managers");
    }

    public function do_tm_association_remove(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'del_tm',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/profile/training-managers");
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
                                    "/profile/training-managers",
                                    "/profile/training-managers");
    }

    public function do_role_request(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'tgt_role',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect('/profile/role');
        }

        $tgt_role = $this->filter_string_default('tgt_role');

        $tgt_type = null;
        switch ($tgt_role) {
            case 'supervisor':
                $tgt_type = Role::TYPE_SUPERVISOR;
                break;
            case 'training-manager':
                $tgt_type = Role::TYPE_TRAINING_MANAGER;
                break;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The specified role type is invalid'
                );

                $this->trigger_request_debug(__METHOD__);
                return $this->redirect('/profile/role');
        }

        $role = $this->roles->fetchType($tgt_type);

        if (!$role || $role->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified role type is invalid'
            );

            return $this->redirect('/profile/role');
        }

        $request = new PendingRole($user->getUuid(), $role->getUuid(), new DateTime());
        $this->pending_roles->save($request);
        $this->log->alert("queue pending role :: {$user->getName()} [{$user->getUuid()}] :: {$role->getType()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your request has been received and must be approved by an administrator.  ' .
            'Please wait up to 24 hours before contacting us regarding the request. '
        );

        return $this->redirect('/profile');
    }

    /**
     * @return Response
     * @throws AccessDeniedException
     */
    public function do_edit(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

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
            return $this->redirect('/profile/edit');
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
                      '/profile',
                      '/profile/edit');
    }

    public function show_afsc_associations(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'There was a problem accessing your account information'
            );

            return $this->redirect('/');
        }

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
            'role' => $role,
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
                'available' => array_diff_key($available, array_flip($afsc_assocs->getAfscs())),
            ],
        ];

        return $this->render(
            'profile/afsc.html.twig',
            $data
        );
    }

    public function show_supervisor_associations(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());
        $base = $this->bases->fetch($user->getBase());
        $super_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);

        if (!$role || !$super_role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'There was a problem accessing your account information'
            );

            return $this->redirect('/');
        }

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
            static function (User $v) use ($su_uuids, $super_role): bool {
                return !isset($su_uuids[ $v->getUuid() ]) &&
                       $v->getRole() === $super_role->getUuid();
            }
        );

        $data = [
            'user' => $user,
            'base' => $base,
            'role' => $role,
            'assocs' => [
                'su' => $su_assocs,
                'available' => $avail_supers,
            ],
        ];

        return $this->render(
            'profile/super-assocs.html.twig',
            $data
        );
    }

    public function show_tm_associations(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());
        $base = $this->bases->fetch($user->getBase());
        $tm_role = $this->roles->fetchType(Role::TYPE_TRAINING_MANAGER);

        if (!$role || !$tm_role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'There was a problem accessing your account information'
            );

            return $this->redirect('/');
        }

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
            static function (User $v) use ($tm_uuids, $tm_role): bool {
                return !isset($tm_uuids[ $v->getUuid() ]) &&
                       $v->getRole() === $tm_role->getUuid();
            }
        );

        $data = [
            'user' => $user,
            'base' => $base,
            'role' => $role,
            'assocs' => [
                'tm' => $tm_assocs,
                'available' => $avail_tms,
            ],
        ];

        return $this->render(
            'profile/tm-assocs.html.twig',
            $data
        );
    }

    public function show_edit(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
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
            /* show special rank for site admin */
            'ranks' => UserHelpers::listRanks(true, $this->auth_helpers->assert_admin()),
            'role' => $role,
            'roles' => $this->roles->fetchAll(),
            'time_zones' => DateTimeHelpers::list_time_zones(),
        ];

        return $this->render(
            'profile/edit.html.twig',
            $data
        );
    }

    public function show_home(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'There was a problem accessing your account information'
            );

            return $this->redirect('/');
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
            'profile/home.html.twig',
            $data
        );
    }

    public function show_role_request(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        $pending_role_request = $this->pending_roles->fetch($user->getUuid());
        if ($pending_role_request !== null) {
            $pending_role = $this->roles->fetch($pending_role_request->getRoleUuid());

            $pending_role_name = 'UNKNOWN';
            if ($pending_role) {
                $pending_role_name = $pending_role->getName();
            }

            $this->flash()->add(
                MessageTypes::WARNING,
                "Your account already has a role request pending for the '{$pending_role_name}' role. " .
                'Please wait up to 24 hours before contacting us regarding the request. ' .
                'The request was submitted on ' .
                $pending_role_request->getDateRequested()->format(DateTimeHelpers::DT_FMT_LONG) . '.'
            );

            return $this->redirect('/profile');
        }

        $data = [
            'user' => $user,
            'role' => $role,
        ];

        return $this->render(
            'profile/role.html.twig',
            $data
        );
    }
}
