<?php


namespace CDCMastery\Controllers;


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

        $new_afsc = $this->get('new_afsc');

        if (!is_array($new_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/profile/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($new_afsc);
        $tgt_afscs_fouo = array_filter($tgt_afscs, static function (Afsc $v): bool {
            return $v->isFouo();
        });
        $tgt_afscs_non_fouo = array_filter($tgt_afscs, static function (Afsc $v): bool {
            return !$v->isFouo();
        });

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

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

        if ($tgt_afscs_fouo) {
            $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
                return "{$v->getName()} [{$v->getUuid()}]";
            }, $tgt_afscs_fouo));
            $pending_str = !$override
                ? ' pending'
                : null;
            $this->log->info("add{$pending_str} afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$this->auth_helpers->get_user_uuid()}");

            $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs_fouo, $override);
        }

        if ($tgt_afscs_non_fouo) {
            $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
                return "{$v->getName()} [{$v->getUuid()}]";
            }, $tgt_afscs_non_fouo));
            $this->log->info("add afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$this->auth_helpers->get_user_uuid()}");

            $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs_non_fouo, true);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully added. ' .
            'FOUO AFSC associations may take up to 24 hours to be approved.'
        );

        return $this->redirect("/profile/afsc");
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

        $del_afsc = $this->get('del_afsc');

        if (!is_array($del_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/profile/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($del_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/profile/afsc");
        }

        $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_afscs));
        $this->log->info("delete afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$this->auth_helpers->get_user_uuid()}");

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->remove($user, $tgt_afsc);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully removed'
        );

        return $this->redirect("/profile/afsc");
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

        $new_super = $this->get('new_super');

        if (!is_array($new_super)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/profile/supervisors");
        }

        $tgt_supers = $this->users->fetchArray($new_super);

        if (!$tgt_supers) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/profile/supervisors");
        }

        $super_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_supers));
        $this->log->info("add supervisor assocs :: {$user->getName()} [{$user->getUuid()}] :: {$super_str}");

        $this->su_assocs->batchAddSupervisorsForUser($tgt_supers, $user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected supervisor associations were successfully added'
        );

        return $this->redirect("/profile/supervisors");
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

        $del_super = $this->get('del_super');

        if (!is_array($del_super)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/profile/supervisors");
        }

        $tgt_supers = $this->users->fetchArray($del_super);

        if (!$tgt_supers) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/profile/supervisors");
        }

        $super_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_supers));
        $this->log->info("delete supervisor assocs :: {$user->getName()} [{$user->getUuid()}] :: {$super_str}");

        foreach ($tgt_supers as $tgt_super) {
            $this->su_assocs->remove($user, $tgt_super);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected supervisor associations were successfully removed'
        );

        return $this->redirect("/profile/supervisors");
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

        $new_tm = $this->get('new_tm');

        if (!is_array($new_tm)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/profile/training-managers");
        }

        $tgt_tms = $this->users->fetchArray($new_tm);

        if (!$tgt_tms) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/profile/training-managers");
        }

        $tm_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_tms));
        $this->log->info("add training manager assocs :: {$user->getName()} [{$user->getUuid()}] :: {$tm_str}");

        $this->tm_assocs->batchAddTrainingManagersForUser($tgt_tms, $user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected training manager associations were successfully added'
        );

        return $this->redirect("/profile/training-managers");
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

        $del_tm = $this->get('del_tm');

        if (!is_array($del_tm)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/profile/training-managers");
        }

        $tgt_tms = $this->users->fetchArray($del_tm);

        if (!$tgt_tms) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/profile/training-managers");
        }

        $tm_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_tms));
        $this->log->info("delete training manager assocs :: {$user->getName()} [{$user->getUuid()}] :: {$tm_str}");

        foreach ($tgt_tms as $tgt_tm) {
            $this->tm_assocs->remove($user, $tgt_tm);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected training manager associations were successfully removed'
        );

        return $this->redirect("/profile/training-managers");
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
        $this->log->info("queue pending role :: {$user->getName()} [{$user->getUuid()}] :: {$role->getType()}");

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

        if (!$this->checkParameters($params)) {
            goto out_return;
        }

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
        if (!$rank || !isset($valid_ranks[ $rank ]) || trim($rank) === '') {
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

        $tgt_base = $this->bases->fetch($base);
        if (!$tgt_base || $tgt_base->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Base is invalid'
            );

            goto out_return;
        }

        $valid_time_zones = array_merge(...DateTimeHelpers::list_time_zones(false));
        if (!$time_zone || !in_array($time_zone, $valid_time_zones, true)) {
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
                $flash = $this->flash();
                foreach ($complexity_check as $complexity_error) {
                    $flash->add(
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
        $user->setBase($tgt_base->getUuid());
        $user->setTimeZone($time_zone);
        $user->setOfficeSymbol($office_symbol
                                   ? $new_office_symbol->getUuid()
                                   : null);

        if ($new_password) {
            $user->setPassword(AuthHelpers::hash($new_password));
            $user->setLegacyPassword(null);
        }

        $this->users->save($user);

        $this->log->info("edit profile :: {$user->getName()} [{$user->getUuid()}]");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your profile information was successfully saved'
        );

        return $this->redirect("/profile");

        out_return:
        return $this->redirect("/profile/edit");
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

        $incomplete_tests = array_filter(
            $this->tests->fetchAllByUser($user),
            static function (Test $v) {
                return $v->getTimeCompleted() === null;
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