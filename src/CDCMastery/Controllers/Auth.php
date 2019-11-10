<?php

namespace CDCMastery\Controllers;


use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\LoginRateLimiter;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class Auth extends RootController
{
    /**
     * @var AuthHelpers
     */
    private $auth_helpers;

    /**
     * @var UserHelpers
     */
    private $user_helpers;

    /**
     * @var LoginRateLimiter
     */
    private $limiter;

    /**
     * @var UserCollection
     */
    private $users;

    /**
     * @var RoleCollection
     */
    private $roles;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserHelpers $user_helpers,
        LoginRateLimiter $limiter,
        UserCollection $users,
        RoleCollection $roles
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->user_helpers = $user_helpers;
        $this->limiter = $limiter;
        $this->users = $users;
        $this->roles = $roles;
    }

    /**
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
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

        $parameters = [
            'username',
            'password',
        ];

        if (!$this->checkParameters($parameters)) {
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

        $uuid = $matchUsername === null
            ? ($matchEmail === null ? null : $matchEmail)
            : $matchUsername;

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
                                'Your account has been disabled.  Please contact the help desk for more information.');

            return $this->show_login();
        }

        if (!$this->auth_helpers->compare($password, $user->getPassword())) {
            $this->limiter->increment();
            $this->log->addWarning('failed login attempt :: password mismatch :: ' .
                                   "{$user->getHandle()} :: ip {$_SERVER['REMOTE_ADDR']}");

            $this->flash()->add(MessageTypes::WARNING,
                                'Your username or password is incorrect, please try again');

            return $this->show_login();
        }

        $this->auth_helpers->login_hook($user, $this->roles->fetch($user->getRole()));

        $this->log->addInfo("login success :: account {$user->getUuid()} " .
                            "'{$user->getName()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            "Welcome, {$user->getName()}! You are now signed in.");

        return $this->redirect($this->auth_helpers->get_redirect() ?? '/');
    }

    /**
     * @return Response
     */
    public function do_logout(): Response
    {
        $this->log->addInfo("logout success :: account {$this->auth_helpers->get_user_uuid()} " .
                            "'{$this->auth_helpers->get_user_name()}' :: ip {$_SERVER['REMOTE_ADDR']}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'You have been successfully logged out');

        $this->auth_helpers->logout_hook();

        return $this->redirect('/');
    }

    /**
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
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
}