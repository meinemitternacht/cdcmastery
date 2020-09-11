<?php


namespace CDCMastery\Controllers;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\Roles\PendingRole;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use CDCMastery\Models\Users\UserSupervisorAssociations;
use CDCMastery\Models\Users\UserTrainingManagerAssociations;
use DateTime;
use Monolog\Logger;
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
        UserSupervisorAssociations $su_assocs
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
    }

    private function get_user(string $uuid): ?User
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

    public function do_role_request(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'tgt_role',
        ];

        $this->checkParameters($params);

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

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your request has been received and must be approved by an administrator.  ' .
            'Please wait up to 24 hours before contacting us regarding the request. '
        );

        return $this->redirect('/profile');
    }

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

        $this->checkParameters($params);

        $handle = $this->filter_string_default('handle');
        $email = $this->filter('email', null, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
        $rank = $this->filter_string_default('rank');
        $first_name = $this->get('first_name');
        $last_name = $this->get('last_name');
        $base = $this->get('base');
        $time_zone = $this->get('time_zone');

        /* optional */
        $office_symbol = $this->get('office_symbol');
        $new_password = $this->get('new_password');

        if ($office_symbol === '') {
            $office_symbol = null;
        }

        if ($new_password === '') {
            $new_password = null;
        }

        if (!$handle || trim($handle) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The Username field cannot be empty'
            );

            goto out_return;
        }

        if (!$email) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified e-mail address is invalid'
            );

            goto out_return;
        }

        $valid_ranks = UserHelpers::listRanks(false);
        if (!$rank || trim($rank) === '' || !isset($valid_ranks[ $rank ])) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided rank is invalid'
            );

            goto out_return;
        }

        if (!$first_name || trim($first_name) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The First Name field cannot be empty'
            );

            goto out_return;
        }

        if (!$last_name || trim($last_name) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The Last Name field cannot be empty'
            );

            goto out_return;
        }

        $new_base = $this->bases->fetch($base);
        if (!$new_base || $new_base->getUuid() === '' || !$base) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Base is invalid'
            );

            goto out_return;
        }

        $valid_time_zones = array_merge(...DateTimeHelpers::list_time_zones(false));
        if (!$time_zone || !in_array($time_zone, $valid_time_zones)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Time Zone is invalid'
            );

            goto out_return;
        }

        if ($office_symbol) {
            $new_office_symbol = $this->symbols->fetch($office_symbol);

            if (!$new_office_symbol || $new_office_symbol->getUuid() === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The chosen Office Symbol is invalid'
                );

                goto out_return;
            }
        }

        if ($new_password !== null) {
            $complexity_check = AuthHelpers::check_complexity($new_password, $handle, $email);

            if ($complexity_check) {
                foreach ($complexity_check as $complexity_error) {
                    $this->flash()->add(
                        MessageTypes::ERROR,
                        $complexity_error
                    );
                }

                goto out_return;
            }
        }

        $user->setHandle($handle);
        $user->setEmail($email);
        $user->setRank($rank);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $user->setBase($new_base->getUuid());
        $user->setTimeZone($time_zone);
        $user->setOfficeSymbol($office_symbol
                                   ? $new_office_symbol->getUuid()
                                   : null);

        if ($new_password) {
            $user->setPassword(AuthHelpers::hash($new_password));
        }

        $this->users->save($user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your profile information was successfully saved'
        );

        return $this->redirect("/profile");

        out_return:
        return $this->redirect("/profile/edit");
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

            $this->flash()->add(
                MessageTypes::WARNING,
                "Your account already has a role request pending for the '{$pending_role->getName()}' role. " .
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