<?php

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\LoginRateLimiter;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Email\Templates\ActivateAccount;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Users\Roles\PendingRole;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use DateTime;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Auth extends RootController
{
    private const TYPE_USER = 'user';
    private const TYPE_TRAINING_MANAGER = 'training-manager';
    private const TYPE_SUPERVISOR = 'supervisor';

    private const TYPE_DISPLAY = [
        self::TYPE_USER => 'User',
        self::TYPE_TRAINING_MANAGER => 'Training Manager',
        self::TYPE_SUPERVISOR => 'Supervisor',
    ];

    private AuthHelpers $auth_helpers;
    private UserHelpers $user_helpers;
    private LoginRateLimiter $limiter;
    private UserCollection $users;
    private RoleCollection $roles;
    private BaseCollection $bases;
    private AfscCollection $afscs;
    private OfficeSymbolCollection $symbols;
    private PendingRoleCollection $pending_roles;
    private ActivationCollection $activations;
    private EmailCollection $emails;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserHelpers $user_helpers,
        LoginRateLimiter $limiter,
        UserCollection $users,
        RoleCollection $roles,
        BaseCollection $bases,
        AfscCollection $afscs,
        OfficeSymbolCollection $symbols,
        PendingRoleCollection $pending_roles,
        ActivationCollection $activations,
        EmailCollection $emails
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->user_helpers = $user_helpers;
        $this->limiter = $limiter;
        $this->users = $users;
        $this->roles = $roles;
        $this->bases = $bases;
        $this->afscs = $afscs;
        $this->symbols = $symbols;
        $this->pending_roles = $pending_roles;
        $this->activations = $activations;
        $this->emails = $emails;
    }

    public function do_registration(string $type): Response
    {
        $params = [
            'username',
            'email',
            'password',
            'password_confirm',
            'first_name',
            'last_name',
            'rank',
            'base',
            'time_zone',
        ];

        $this->session->set('tmp_form', $this->request->request->all());

        if (!$this->checkParameters($params)) {
            goto out_error;
        }

        $username = $this->filter_string_default('username');
        $email = $this->filter('email', null, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
        $rank = $this->filter_string_default('rank');
        $first_name = $this->get('first_name');
        $last_name = $this->get('last_name');
        $base = $this->get('base');
        $time_zone = $this->get('time_zone');
        $password = $this->get('password');
        $password_confirm = $this->get('password_confirm');

        /* optional */
        $office_symbol = $this->get('office_symbol');

        if ($office_symbol === '') {
            $office_symbol = null;
        }

        if (!$username || trim($username) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The Username field cannot be empty'
            );

            goto out_error;
        }

        if ($this->user_helpers->findByUsername($username)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'An account with that username already exists, please choose another'
            );

            goto out_error;
        }

        if (!$email) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified e-mail address is invalid'
            );

            goto out_error;
        }

        if (!AuthHelpers::check_email($email)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Your e-mail address must end in .mil'
            );

            goto out_error;
        }

        if ($this->user_helpers->findByEmail($email)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'An account with that e-mail address already exists, please choose another'
            );

            goto out_error;
        }

        $valid_ranks = UserHelpers::listRanks(false);
        if (!$rank || trim($rank) === '' || !isset($valid_ranks[ $rank ])) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided rank is invalid'
            );

            goto out_error;
        }

        if (!$first_name || trim($first_name) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The First Name field cannot be empty'
            );

            goto out_error;
        }

        if (!$last_name || trim($last_name) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The Last Name field cannot be empty'
            );

            goto out_error;
        }

        $new_base = $this->bases->fetch($base);
        if (!$new_base || $new_base->getUuid() === '' || !$base) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Base is invalid'
            );

            goto out_error;
        }

        $valid_time_zones = array_merge(...DateTimeHelpers::list_time_zones(false));
        if (!$time_zone || !in_array($time_zone, $valid_time_zones)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Time Zone is invalid'
            );

            goto out_error;
        }

        if ($office_symbol) {
            $new_office_symbol = $this->symbols->fetch($office_symbol);

            if (!$new_office_symbol || $new_office_symbol->getUuid() === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The chosen Office Symbol is invalid'
                );

                goto out_error;
            }
        }

        if ($password !== $password_confirm) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The entered passwords do not match'
            );

            goto out_error;
        }

        $complexity_check = AuthHelpers::check_complexity($password, $username, $email);

        if ($complexity_check) {
            $flash = $this->flash();
            foreach ($complexity_check as $complexity_error) {
                $flash->add(
                    MessageTypes::ERROR,
                    $complexity_error
                );
            }

            goto out_error;
        }

        $role = $this->roles->fetchType(Role::TYPE_USER);
        if (!$role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered a problem while adding your account, please contact the site administrator'
            );

            goto out_error;
        }

        $user = new User();
        $user->setUuid(UUID::generate());
        $user->setHandle($username);
        $user->setEmail($email);
        $user->setRank($rank);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $user->setBase($base);
        $user->setTimeZone($time_zone);
        $user->setPassword(AuthHelpers::hash($password));
        $user->setRole($role->getUuid());

        if ($office_symbol) {
            $user->setOfficeSymbol($office_symbol);
        }

        $this->users->save($user);
        $activation = Activation::factory($user);
        $this->activations->save($activation);
        $this->emails->queue(ActivateAccount::email($this->users->fetch(SYSTEM_UUID),
                                                    $user,
                                                    $activation));

        $pending_role = null;
        switch ($type) {
            case self::TYPE_SUPERVISOR:
                $pending_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);
                break;
            case self::TYPE_TRAINING_MANAGER:
                $pending_role = $this->roles->fetchType(Role::TYPE_TRAINING_MANAGER);
                break;
        }

        if ($type !== self::TYPE_USER && !$pending_role) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered a problem while adding your account, please contact the site administrator'
            );

            goto out_error;
        }

        if ($pending_role) {
            $this->pending_roles->save(
                new PendingRole($user->getUuid(), $pending_role->getUuid(), new DateTime()));
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your account was created successfully.  An activation link has been sent to the e-mail address provided during registration.'
        );

        return $this->redirect("/auth/login");

        out_error:
        return $this->redirect("/auth/register/{$type}");
    }

    public function do_login(): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->log->addWarning('failed login attempt :: already logged in :: ' .
                                   "account {$this->auth_helpers->get_user_uuid()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::INFO,
                                'You are already logged in');

            return $this->redirect('/');
        }

        if ($this->limiter->assert_limited()) {
            $req_str = json_encode($this->request->request->all());
            $this->log->addWarning("rate-limited login attempt :: {$req_str} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'You have made too many login attempts, please try again at a later time');

            return $this->show_login();
        }

        $params = [
            'username',
            'password',
        ];

        if (!$this->checkParameters($params)) {
            $this->limiter->increment();

            $req_str = json_encode($this->request->request->all());
            $this->log->addWarning('login attempt missing parameters :: ' .
                                   "{$req_str} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'A required field was not provided, please try again');

            return $this->show_login();
        }

        $username = $this->get('username');
        $password = $this->get('password');

        $matchUsername = $this->user_helpers->findByUsername($username);

        $matchEmail = null;
        if (filter_var($username, FILTER_VALIDATE_EMAIL) !== false) {
            $matchEmail = $this->user_helpers->findByEmail($username);
        }

        if (!$matchUsername && !$matchEmail) {
            $uuid = null;
        } elseif ($matchUsername) {
            $uuid = $matchUsername;
        } else {
            $uuid = $matchEmail;
        }

        if ($uuid === null) {
            $this->limiter->increment();
            $this->log->addWarning('failed login attempt :: unknown user :: ' .
                                   "{$username} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        $user = $this->users->fetch($uuid);

        if (!$user->assert_valid()) {
            $this->limiter->increment();
            $this->log->addWarning('failed login attempt :: unknown user :: ' .
                                   "{$username} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        if ($user->isDisabled()) {
            $this->limiter->increment();
            $this->log->addWarning('failed login attempt :: account disabled :: ' .
                                   "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your account has been disabled.  Please contact the site administrator for more information.');

            return $this->show_login();
        }

        if ($this->activations->fetchByUser($user)) {
            $this->log->addWarning('failed login attempt :: account not activated :: ' .
                                   "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your account has not been activated yet. Please check your e-mail for further instructions or contact the site administrator to activate your account manually.');

            return $this->show_login();
        }

        if (!AuthHelpers::compare($password, $user->getPassword())) {
            $this->limiter->increment();
            $this->log->addWarning('failed login attempt :: password mismatch :: ' .
                                   "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->log->addWarning('login error :: role not found :: ' .
                                   "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'The system had trouble accessing your account, please contact the site administrator');

            return $this->show_login();
        }

        $this->auth_helpers->login_hook($user, $role);

        $now = new DateTime();
        $user->setLastActive($now);
        $user->setLastLogin($now);
        $this->users->save($user);

        $this->log->addInfo("login success :: account {$user->getUuid()} " .
                            "'{$user->getName()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            "Welcome, {$user->getName()}! You are now signed in.");

        return $this->redirect($this->auth_helpers->get_redirect() ?? '/');
    }

    public function do_logout(): Response
    {
        $this->log->addInfo("logout success :: account {$this->auth_helpers->get_user_uuid()} " .
                            "'{$this->auth_helpers->get_user_name()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'You have been successfully logged out');

        $this->auth_helpers->logout_hook();

        return $this->redirect('/');
    }

    public function show_login(): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->log->addWarning('failed login attempt :: already logged in :: ' .
                                   "account {$this->auth_helpers->get_user_uuid()} " .
                                   "'{$this->auth_helpers->get_user_name()}' :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(
                MessageTypes::INFO,
                'You are already logged in'
            );

            return $this->redirect('/');
        }

        return $this->render('public/auth/login.html.twig');
    }

    public function show_registration(?string $type = null): Response
    {
        if (!$type) {
            return $this->render('public/auth/register-choose-type.html.twig');
        }

        $data = [
            'type' => $type,
            'type_display' => self::TYPE_DISPLAY[ $type ] ?? null,
            'afscs' => $this->afscs->fetchAll(AfscCollection::SHOW_FOUO),
            'bases' => $this->bases->fetchAll(),
            'ranks' => UserHelpers::listRanks(true, false),
            'symbols' => $this->symbols->fetchAll(),
            'time_zones' => DateTimeHelpers::list_time_zones(),
            'tmp_form' => $this->session->get('tmp_form'),
        ];

        return $this->render(
            'public/auth/register.html.twig',
            $data
        );
    }
}