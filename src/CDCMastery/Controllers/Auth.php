<?php
declare(strict_types=1);

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\LoginRateLimiter;
use CDCMastery\Models\Auth\PasswordReset\PasswordReset;
use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use CDCMastery\Models\Auth\Recaptcha\RecaptchaVerify;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Email\Templates\ActivateAccount;
use CDCMastery\Models\Email\Templates\ResetPassword;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Users\Roles\PendingRole;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use DateTime;
use Exception;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
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

    private Config $config;
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
    private PasswordResetCollection $resets;
    private UserAfscAssociations $afsc_assocs;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        Config $config,
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
        EmailCollection $emails,
        PasswordResetCollection $resets,
        UserAfscAssociations $afsc_assocs
    ) {
        parent::__construct($logger, $twig, $session);

        $this->config = $config;
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
        $this->resets = $resets;
        $this->afsc_assocs = $afsc_assocs;
    }

    public function do_activation(?string $code = null): Response
    {
        if ($this->limiter->assert_limited()) {
            $this->log->warning("rate-limited activation attempt :: ip {$_SERVER['REMOTE_ADDR']}");
            $expires = $this->limiter->get_limit_expires_seconds();

            $this->flash()->add(MessageTypes::WARNING,
                                "You have made too many activation attempts, please try again in {$expires} seconds");
            return $this->show_password_reset();
        }

        if (!$code) {
            $code = $this->filter_string_default('code');
        }

        if (!$code) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'No activation code was provided'
            );

            $this->limiter->increment();
            return $this->redirect('/auth/activate');
        }

        $activation = $this->activations->fetch($code);

        if (!$activation) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided activation code was not valid, or your account was already activated. Please sign in to continue.'
            );

            $this->log->warning("activate user failed :: invalid code {$code}");
            $this->limiter->increment();
            return $this->redirect('/auth/activate');
        }

        $this->activations->remove($activation);
        $user = $this->users->fetch($activation->getUserUuid());
        $user_str = $user
            ? "{$user->getName()} [{$user->getUuid()}]"
            : "UNKNOWN USER [{$activation->getUserUuid()}]";

        $this->log->info("activate user :: {$user_str}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your account has been activated successfully, please sign in to continue'
        );

        $this->auth_helpers->set_redirect('/profile/afsc');
        $this->limiter->destroy();
        return $this->redirect('/auth/login');
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function do_activation_resend(): Response
    {
        if ($this->limiter->assert_limited()) {
            $this->log->warning("rate-limited activation resend attempt :: ip {$_SERVER['REMOTE_ADDR']}");
            $expires = $this->limiter->get_limit_expires_seconds();

            $this->flash()->add(MessageTypes::WARNING,
                                "You have made too many activation attempts, please try again in {$expires} seconds");
            return $this->show_password_reset();
        }

        $email = $this->filter('email', null, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);

        if (!$email) {
            $this->log->warning("resend activation failed :: email invalid");
            $this->limiter->increment();
            goto out_return;
        }

        $user_uuid = $this->user_helpers->findByEmail($email);

        if (!$user_uuid) {
            $this->log->warning("resend activation failed :: user not found :: {$email}");
            $this->limiter->increment();
            goto out_return;
        }

        $user = $this->users->fetch($user_uuid);

        if (!$user) {
            $this->log->warning("resend activation failed :: user not found :: {$email} :: user uuid {$user_uuid}");
            $this->limiter->increment();
            goto out_return;
        }

        $activation = $this->activations->fetchByUser($user);

        if (!$activation) {
            $this->log->warning("resend activation failed :: user already activated :: {$user->getName()} [{$user->getUuid()}");
            $this->flash()->add(
                MessageTypes::INFO,
                "Your account has already been activated.  Please sign in below."
            );

            $this->limiter->destroy();
            return $this->redirect('/auth/login');
        }

        $system_user = $this->users->fetch(SYSTEM_UUID);

        if (!$system_user) {
            $this->log->alert("resend activation failed :: system user not found :: {$email} :: user uuid {$user_uuid}");
            goto out_return;
        }

        $this->activations->remove($activation);
        $activation = Activation::factory($user);
        $this->activations->save($activation);
        $this->emails->queue(ActivateAccount::email($system_user,
                                                    $user,
                                                    $activation));

        $this->log->info("resend activation :: {$user->getName()} [{$user->getUuid()}");

        out_return:
        usleep(random_int(200000, 500000));

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'If the e-mail address entered matches an account on file, a new activation e-mail will been sent in a few moments.'
        );

        $this->limiter->destroy();
        return $this->redirect('/auth/activate');
    }

    /**
     * @param string|null $code
     * @return Response
     * @throws Exception
     */
    public function do_password_reset(string $code): Response
    {
        $reset = $this->resets->fetch($code);

        if (!$reset) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided password reset link was invalid, or it has expired'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/auth/reset');
        }

        $user = $this->users->fetch($reset->getUserUuid());

        if (!$user) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The user associated with the password reset link does not exist'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/auth/reset');
        }

        if (!$this->checkParameters(['password', 'password_confirm'])) {
            return $this->redirect("/auth/reset/{$code}");
        }

        $password = $this->get('password');
        $password_confirm = $this->get('password_confirm');

        if ($password !== $password_confirm) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The entered passwords do not match'
            );

            return $this->redirect("/auth/reset/{$code}");
        }

        $complexity_check = AuthHelpers::check_complexity($password, $user->getHandle(), $user->getEmail());

        if ($complexity_check) {
            $flash = $this->flash();
            foreach ($complexity_check as $complexity_error) {
                $flash->add(
                    MessageTypes::ERROR,
                    $complexity_error
                );
            }

            return $this->redirect("/auth/reset/{$code}");
        }

        $user->setPassword(AuthHelpers::hash($password));
        $user->setLegacyPassword(null);
        $this->users->save($user);
        $this->resets->remove($reset);
        $this->limiter->destroy();

        $this->log->info("user password changed :: {$user->getName()} [{$user->getUuid()}]");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your password has been changed successfully. Please sign in to continue.'
        );

        return $this->redirect('/auth/login');
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function do_password_reset_send(): Response
    {
        if ($this->limiter->assert_limited()) {
            $this->log->warning("rate-limited password reset attempt :: ip {$_SERVER['REMOTE_ADDR']}");
            $expires = $this->limiter->get_limit_expires_seconds();

            $this->flash()->add(MessageTypes::WARNING,
                                "You have made too many password reset attempts, please try again in {$expires} seconds");
            return $this->show_password_reset();
        }

        $email = $this->filter('email', null, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);

        if (!$email) {
            $this->limiter->increment();
            $this->log->warning("queue password reset failed :: email invalid");
            goto out_return;
        }

        $user_uuid = $this->user_helpers->findByEmail($email);

        if (!$user_uuid) {
            $this->limiter->increment();
            $this->log->warning("queue password reset failed :: user not found :: {$email}");
            goto out_return;
        }

        $user = $this->users->fetch($user_uuid);

        if (!$user) {
            $this->limiter->increment();
            $this->log->warning("queue password reset failed :: user not found :: {$email} :: user uuid {$user_uuid}");
            goto out_return;
        }

        $reset = $this->resets->fetchByUser($user);

        if ($reset) {
            $this->log->info("remove previous password reset :: {$reset->getUuid()} :: user {$user->getName()} [{$user->getUuid()}]");
            $this->resets->remove($reset);
        }

        $reset = PasswordReset::factory($user);
        $this->resets->save($reset);
        try {
            $system_user = $this->users->fetch(SYSTEM_UUID);

            if (!$system_user) {
                $this->log->alert("queue password reset failed :: system user not found :: {$email} :: user uuid {$user_uuid}");
                goto out_return;
            }

            $this->emails->queue(ResetPassword::email($system_user,
                                                      $user,
                                                      $reset));
        } catch (Throwable $e) {
            $this->log->debug($e);
            unset($e);
        }

        $this->log->info("queue password reset :: {$user->getName()} [{$user->getUuid()}]");

        out_return:
        usleep(random_int(200000, 500000));

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'If the e-mail address entered matches an account on file, a one-time password reset link will been sent in a few moments.'
        );

        $this->limiter->destroy();
        return $this->redirect('/auth/login');
    }

    public function do_registration(string $type): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->flash()->add(
                MessageTypes::INFO,
                'You cannot register for a new account while logged in as another user'
            );

            return $this->redirect('/');
        }

        $params = [
            'username',
            'email',
            'email_confirm',
            'password',
            'password_confirm',
            'first_name',
            'last_name',
            'rank',
            'base',
            'time_zone',
        ];

        $this->session->set('tmp_form', array_diff_key($this->request->request->all(),
                                                       array_flip(['password', 'password_confirm'])));

        if (!$this->checkParameters($params)) {
            goto out_error;
        }

        $username = $this->filter_string_default('username');
        $email = $this->filter('email',
                               null,
                               FILTER_VALIDATE_EMAIL,
                               FILTER_NULL_ON_FAILURE);
        $email_confirm = $this->filter('email',
                                       null,
                                       FILTER_VALIDATE_EMAIL,
                                       FILTER_NULL_ON_FAILURE);
        $rank = $this->filter_string_default('rank');
        $first_name = $this->get('first_name');
        $last_name = $this->get('last_name');
        $base = $this->get('base');
        $time_zone = $this->get('time_zone');
        $password = $this->get('password');
        $password_confirm = $this->get('password_confirm');

        /* required, but a custom error is preferred */
        $g_recaptcha_response = $this->get('g-recaptcha-response');

        /* optional */
        $office_symbol = $this->get('office_symbol');

        if ($office_symbol === '') {
            $office_symbol = null;
        }

        if (!$g_recaptcha_response) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Please complete the reCAPTCHA "I am not a robot" verification'
            );

            $this->log->debug('no reCAPTCHA token provided');
            $this->limiter->increment();
            goto out_error;
        }

        $recaptcha_verify = (new RecaptchaVerify($this->log,
                                                 $this->config->get(['system', 'auth', 'recaptcha', 'secret']),
                                                 $g_recaptcha_response,
                                                 $this->request->getClientIp()))->verify();

        if (!$recaptcha_verify) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'We apologize, but we were unable to verify your reCAPTCHA response. Please contact the site administrator if this issue persists.'
            );

            $this->log->alert("reCAPTCHA code failed verification :: {$g_recaptcha_response} :: {$this->request->getClientIp()}");
            $this->limiter->increment();
            goto out_error;
        }

        $this->log->addDebug("reCAPTCHA code verified :: {$g_recaptcha_response}");

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

        if ($this->config->get(['system', 'auth', 'email', 'require_mil']) &&
            !AuthHelpers::check_email($email)) {
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

        if ($email !== $email_confirm) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The e-mail addresses you entered do not match'
            );

            goto out_error;
        }

        $valid_ranks = UserHelpers::listRanks(false);
        if (!$rank || !isset($valid_ranks[ $rank ]) || trim($rank) === '') {
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

        $tgt_base = $this->bases->fetch($base);
        if (!$tgt_base || $tgt_base->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The chosen Base is invalid'
            );

            goto out_error;
        }

        $valid_time_zones = array_merge(...DateTimeHelpers::list_time_zones(false));
        if (!$time_zone || !in_array($time_zone, $valid_time_zones, true)) {
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
            $this->log->debug('register user fail :: role not found :: type ' . Role::TYPE_USER);
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
        $user->setBase($tgt_base->getUuid());
        $user->setTimeZone($time_zone);
        $user->setLegacyPassword(null);
        $user->setPassword(AuthHelpers::hash($password));
        $user->setRole($role->getUuid());
        $user->setDateRegistered(new DateTime());
        $user->setLastLogin(null);
        $user->setLastActive(null);
        $user->setOfficeSymbol($office_symbol
                                   ?: null);

        $this->users->save($user);
        $activation = Activation::factory($user);
        $this->activations->save($activation);
        $this->log->info("queue activation :: {$activation->getCode()} :: user {$user->getName()} [{$user->getUuid()}]");

        try {
            $system_user = $this->users->fetch(SYSTEM_UUID);

            if (!$system_user) {
                $this->log->alert("register user fail :: system user not found");
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The system encountered a problem while adding your account, please contact the site administrator'
                );

                goto out_error;
            }

            $this->emails->queue(ActivateAccount::email($system_user,
                                                        $user,
                                                        $activation));
        } catch (Exception $e) {
            $this->log->alert($e);
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered a problem sending the activation e-mail, please contact the site administrator'
            );
        }

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
            $this->trigger_request_debug(__METHOD__);
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered a problem while adding your account, please contact the site administrator'
            );

            goto out_error;
        }

        if ($pending_role) {
            $this->pending_roles->save(new PendingRole($user->getUuid(), $pending_role->getUuid(), new DateTime()));
            $this->log->info("queue pending role :: {$user->getName()} [{$user->getUuid()}] :: {$pending_role->getType()}");
        }

        $this->log->alert("register user :: {$user->getName()} [{$user->getUuid()}] :: email {$user->getEmail()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'Your account was created successfully.  An activation link will be sent to the e-mail address provided during registration in a few moments.'
        );

        return $this->redirect("/auth/login");

        out_error:
        return $this->redirect("/auth/register/{$type}");
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function do_login(): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->log->warning('failed login attempt :: already logged in :: ' .
                                "account {$this->auth_helpers->get_user_uuid()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::INFO,
                                'You are already logged in');

            return $this->redirect('/');
        }

        if ($this->limiter->assert_limited()) {
            $this->log->warning("rate-limited login attempt :: username '{$this->get('username')}' :: ip {$_SERVER['REMOTE_ADDR']}");
            $expires = $this->limiter->get_limit_expires_seconds();

            $this->flash()->add(MessageTypes::WARNING,
                                "You have made too many login attempts, please try again in {$expires} seconds");

            return $this->show_login();
        }

        $params = [
            'username',
            'password',
        ];

        if (!$this->checkParameters($params)) {
            $this->limiter->increment();

            $req_str = json_encode($this->request->request->all(), JSON_THROW_ON_ERROR);
            $this->log->warning('login attempt missing parameters :: ' .
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
            $this->log->warning('failed login attempt :: unknown user :: ' .
                                "{$username} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        $user = $this->users->fetch($uuid);

        if (!$user || !$user->assert_valid()) {
            $this->limiter->increment();
            $this->log->warning('failed login attempt :: unknown user :: ' .
                                "{$username} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        $legacy_hash = $user->getLegacyPassword();

        if ($legacy_hash && !AuthHelpers::compare_legacy($password, $legacy_hash)) {
            $this->limiter->increment();
            $this->log->warning('failed login attempt :: legacy password mismatch :: ' .
                                "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        if (!$legacy_hash && !AuthHelpers::compare($password, $user->getPassword())) {
            $this->limiter->increment();
            $this->log->warning('failed login attempt :: password mismatch :: ' .
                                "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        if ($user->isDisabled()) {
            $this->limiter->increment();
            $this->log->warning('failed login attempt :: account disabled :: ' .
                                "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your account has been disabled.  Please contact the site administrator for more information.');

            return $this->show_login();
        }

        if ($this->activations->fetchByUser($user)) {
            $this->log->warning('failed login attempt :: account not activated :: ' .
                                "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your account has not been activated yet. Please check your e-mail for further instructions or contact the site administrator to activate your account manually.');

            return $this->show_login();
        }

        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->log->warning('login error :: role not found :: ' .
                                "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'The system had trouble accessing your account, please contact the site administrator');

            return $this->show_login();
        }

        $this->limiter->destroy();
        $this->auth_helpers->login_hook($user, $role);

        $now = new DateTime();
        $user->setLastActive($now);
        $user->setLastLogin($now);

        if ($legacy_hash) {
            $user->setLegacyPassword(null);
            $user->setPassword(AuthHelpers::hash($password));
        }

        if (!$legacy_hash) {
            AuthHelpers::check_rehash($this->log, $user, $password);
        }

        $this->users->save($user);

        if (!$this->afsc_assocs->fetchAllByUser($user)->getAfscs()) {
            $this->flash()->add(MessageTypes::INFO,
                                "Your account is not associated with any AFSCs. Use the form below to manage associations.");
            $this->auth_helpers->set_redirect('/profile/afsc');
        }

        $this->log->info("login success :: account {$user->getUuid()} " .
                         "'{$user->getName()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            "Welcome, {$user->getName()}! You are now signed in.");

        return $this->redirect($this->auth_helpers->get_redirect() ?? '/');
    }

    public function do_logout(): Response
    {
        $this->log->info("logout success :: account {$this->auth_helpers->get_user_uuid()} " .
                         "'{$this->auth_helpers->get_user_name()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'You have been successfully logged out');

        $this->limiter->destroy();
        $this->auth_helpers->logout_hook();

        return $this->redirect('/');
    }

    public function show_activation(): Response
    {
        return $this->render('public/auth/activate.html.twig');
    }

    public function show_activation_resend(): Response
    {
        return $this->render('public/auth/activate-resend.html.twig');
    }

    public function show_login(): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->log->warning('failed login attempt :: already logged in :: ' .
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

    public function show_password_reset(): Response
    {
        return $this->render('public/auth/password-reset.html.twig');
    }

    public function show_password_reset_change(string $code): Response
    {
        return $this->render('public/auth/password-reset-change.html.twig', ['code' => $code]);
    }

    public function show_registration(?string $type = null): Response
    {
        if ($this->auth_helpers->assert_logged_in()) {
            $this->flash()->add(
                MessageTypes::INFO,
                'You cannot register for a new account while logged in as another user'
            );

            return $this->redirect('/');
        }

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