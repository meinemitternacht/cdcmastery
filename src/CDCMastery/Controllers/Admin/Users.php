<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Tests;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\PasswordReset\PasswordReset;
use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Email\Templates\ActivateAccount;
use CDCMastery\Models\Email\Templates\ResetPassword;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use CDCMastery\Models\Users\UserSupervisorAssociations;
use CDCMastery\Models\Users\UserTrainingManagerAssociations;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Users extends Admin
{
    private const TYPE_COMPLETE = 0;
    private const TYPE_INCOMPLETE = 1;

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

    /**
     * Users constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param EmailCollection $emails
     * @param UserCollection $users
     * @param BaseCollection $bases
     * @param RoleCollection $roles
     * @param OfficeSymbolCollection $symbols
     * @param TestStats $test_stats
     * @param TestCollection $tests
     * @param TestDataHelpers $test_data_helpers
     * @param AfscCollection $afscs
     * @param UserAfscAssociations $afsc_assocs
     * @param UserTrainingManagerAssociations $tm_assocs
     * @param UserSupervisorAssociations $su_assocs
     * @param PasswordResetCollection $pw_resets
     * @param ActivationCollection $activations
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
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
        ActivationCollection $activations
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

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

        $handle = $this->filter_string_default('handle');
        $email = $this->filter('email', null, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
        $rank = $this->filter_string_default('rank');
        $first_name = $this->get('first_name');
        $last_name = $this->get('last_name');
        $base = $this->get('base');
        $time_zone = $this->get('time_zone');

        /* optional */
        $office_symbol = $this->get('office_symbol');
        $role = $this->get('role');
        $new_password = $this->get('new_password');

        if ($office_symbol === '') {
            $office_symbol = null;
        }

        if ($role === '') {
            $role = null;
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

        if (!$this->auth_helpers->assert_admin() && $role !== $user->getRole()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Your account type cannot change the role for this user'
            );

            $this->trigger_request_debug(__METHOD__);
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

        if ($role) {
            $new_role = $this->roles->fetch($role);

            if (!$new_role || $new_role->getUuid() === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The chosen Role is invalid'
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

        if ($office_symbol) {
            $user->setOfficeSymbol($new_office_symbol->getUuid());
        }

        if ($role) {
            $user->setRole($new_role->getUuid());
        }

        if ($new_password) {
            $user->setPassword(AuthHelpers::hash($new_password));
        }

        $this->users->save($user);

        $this->log->info("edit user :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The information for that user was successfully saved'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}");

        out_return:
        return $this->redirect("/admin/users/{$user->getUuid()}/edit");
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

        $new_afsc = $this->get('new_afsc');

        if (!is_array($new_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($new_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_afscs));
        $this->log->info("add afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs, true);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully added'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
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

        $approve_afsc = $this->get('approve_afsc');

        if (!is_array($approve_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($approve_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_afscs));
        $this->log->info("approve afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$this->auth_helpers->get_user_uuid()}");

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->authorize($user, $tgt_afsc);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully approved'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
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

        $del_afsc = $this->get('del_afsc');

        if (!is_array($del_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($del_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
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

        return $this->redirect("/admin/users/{$user->getUuid()}/afsc");
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

        $new_super = $this->get('new_super');

        if (!is_array($new_super)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        $tgt_supers = $this->users->fetchArray($new_super);

        if (!$tgt_supers) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        $super_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_supers));
        $this->log->info("add supervisor assocs :: {$user->getName()} [{$user->getUuid()}] :: {$super_str} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->su_assocs->batchAddSupervisorsForUser($tgt_supers, $user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected supervisor associations were successfully added'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
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

        $del_super = $this->get('del_super');

        if (!is_array($del_super)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        $tgt_supers = $this->users->fetchArray($del_super);

        if (!$tgt_supers) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
        }

        $super_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_supers));
        $this->log->info("delete supervisor assocs :: {$user->getName()} [{$user->getUuid()}] :: {$super_str} :: user {$this->auth_helpers->get_user_uuid()}");

        foreach ($tgt_supers as $tgt_super) {
            $this->su_assocs->remove($user, $tgt_super);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected supervisor associations were successfully removed'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/supervisors");
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

        $new_tm = $this->get('new_tm');

        if (!is_array($new_tm)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        $tgt_tms = $this->users->fetchArray($new_tm);

        if (!$tgt_tms) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        $tm_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_tms));
        $this->log->info("add training manager assocs :: {$user->getName()} [{$user->getUuid()}] :: {$tm_str} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->tm_assocs->batchAddTrainingManagersForUser($tgt_tms, $user);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected training manager associations were successfully added'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
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

        $del_tm = $this->get('del_tm');

        if (!is_array($del_tm)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        $tgt_tms = $this->users->fetchArray($del_tm);

        if (!$tgt_tms) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
        }

        $tm_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_tms));
        $this->log->info("delete training manager assocs :: {$user->getName()} [{$user->getUuid()}] :: {$tm_str} :: user {$this->auth_helpers->get_user_uuid()}");

        foreach ($tgt_tms as $tgt_tm) {
            $this->tm_assocs->remove($user, $tgt_tm);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected training manager associations were successfully removed'
        );

        return $this->redirect("/admin/users/{$user->getUuid()}/training-managers");
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

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
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

        if ($this->pw_resets->fetchByUser($user) !== null) {
            $this->flash()->add(MessageTypes::ERROR,
                                'An active password reset request for this user already exists');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $pw_reset = PasswordReset::factory($user);
        $email = ResetPassword::email($initiator, $user, $pw_reset);

        try {
            $this->emails->queue($email);
        } catch (Throwable $e) {
            $this->log->debug($e);
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while attempting to send the password reset e-mail');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $this->pw_resets->save($pw_reset);
        $this->log->info("reset user password :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'A password reset request for this user was successfully initiated');

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function do_resend_activation(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $initiator = $this->get_user($this->auth_helpers->get_user_uuid());

        $activation = Activation::factory($user);
        $email = ActivateAccount::email($initiator, $user, $activation);

        try {
            $this->emails->queue($email);
        } catch (Throwable $e) {
            $this->log->debug($e);
            $this->flash()->add(MessageTypes::ERROR,
                                'The system encountered an error while attempting to resend the activation e-mail');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        $this->activations->save($activation);
        $this->log->info("resend user activation :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'An activation request for this user was successfully initiated');

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

        $this->log->info(($was_disabled
                             ? 'reactivate'
                             : 'disable') . " user :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        return $this->redirect("/admin/users/{$user->getUuid()}");
    }

    public function show_home(): Response
    {
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

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
        $subs = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);

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
            'base' => $base,
            'assocs' => [
                'su' => $su_assocs,
                'subordinates' => $subs,
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
        $subs = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);

        $tm_uuids = [];
        foreach ($tm_assocs as $tm_assoc) {
            $tm_uuids[] = $tm_assoc->getUuid();
        }

        $tm_uuids = array_flip($tm_uuids);

        $avail_supers = $this->users->filterByBase($base, $user_sort);
        $avail_supers = array_filter(
            $avail_supers,
            static function (User $v) use ($tm_uuids, $role): bool {
                return !isset($tm_uuids[ $v->getUuid() ]) &&
                       $v->getRole() === $role->getUuid();
            }
        );

        $data = [
            'user' => $user,
            'base' => $base,
            'assocs' => [
                'tm' => $tm_assocs,
                'subordinates' => $subs,
                'available' => $avail_supers,
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

        if ($this->pw_resets->fetchByUser($user) !== null) {
            $this->flash()->add(MessageTypes::ERROR,
                                'An active password reset request for this user already exists');

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

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

        $incomplete_tests = array_filter(
            $this->tests->fetchAllByUser($user),
            static function (Test $v) {
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
            static function (Test $v) {
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
            static function (Test $a, Test $b) {
                return $b->getTimeStarted() <=> $a->getTimeStarted();
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

        if (!$test || !$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        if (!$test->isComplete()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Tests that are still in-progress cannot be viewed'
            );

            return $this->redirect("/admin/users/{$user->getUuid()}");
        }

        return $this->show_test_complete($user, $test);
    }

    private function show_test_complete(User $user, Test $test): Response
    {
        $testData = $this->test_data_helpers->list($test);

        $time_started = $test->getTimeStarted();
        if ($time_started) {
            $time_started = $time_started->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $time_completed = $test->getTimeCompleted();
        if ($time_completed) {
            $time_completed = $time_completed->format(DateTimeHelpers::DT_FMT_LONG);
        }

        $data = [
            'user' => $user,
            'timeStarted' => $time_started,
            'timeCompleted' => $time_completed,
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
            static function (Test $v) use ($type) {
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