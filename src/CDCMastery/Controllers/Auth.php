<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\LoginRateLimiter;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Auth extends RootController
{
    /**
     * @var UserHelpers
     */
    private $userHelpers;

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @var RoleCollection
     */
    private $roleCollection;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        UserHelpers $userHelpers,
        UserCollection $userCollection,
        RoleCollection  $roleCollection
    ) {
        parent::__construct($logger, $twig);

        $this->userHelpers = $userHelpers;
        $this->userCollection = $userCollection;
        $this->roleCollection = $roleCollection;
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function processLogin(): Response
    {
        if (AuthHelpers::isLoggedIn()) {
            $this->log->addWarning(
                'failed login attempt :: already logged in :: account ' .
                SessionHelpers::getUserUuid() .
                ' :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::INFO,
                'You are already logged in'
            );

            return AppHelpers::redirect('/');
        }

        if (LoginRateLimiter::assertLimited()) {
            $this->log->addWarning(
                'rate-limited login attempt :: ' .
                serialize($this->request) .
                ' :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'You have made too many login attempts, please try again at a later time'
            );

            return self::renderLogin();
        }

        $parameters = [
            'username',
            'password'
        ];

        if (!$this->checkParameters($parameters, $this->request)) {
            LoginRateLimiter::increment();

            $this->log->addWarning(
                'login attempt missing parameters :: ' .
                serialize($this->request) .
                ' :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'A required field was not provided, please try again'
            );

            return self::renderLogin();
        }

        $username = $this->get('username');
        $password = $this->get('password');

        $matchUsername = $this->userHelpers->findByUsername($username);

        $matchEmail = null;
        if (filter_var($username, FILTER_VALIDATE_EMAIL) !== false) {
            $matchEmail = $this->userHelpers->findByEmail($username);
        }

        $uuid = $matchUsername === null
            ? ($matchEmail === null ? null : $matchEmail)
            : $matchUsername;

        if ($uuid === null) {
            LoginRateLimiter::increment();

            $this->log->addWarning(
                'failed login attempt :: bad username :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'Your username or password is incorrect, please try again'
            );

            return self::renderLogin();
        }

        $user = $this->userCollection->fetch($uuid);

        if ($user->getUuid() === '') {
            LoginRateLimiter::increment();

            $this->log->addWarning(
                'failed login attempt :: bad username :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'Your username or password is incorrect, please try again'
            );

            return self::renderLogin();
        }

        if ($user->isDisabled()) {
            LoginRateLimiter::increment();

            $this->log->addWarning(
                'failed login attempt :: disabled account :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'Your account has been disabled.  Please contact the helpdesk for more information.'
            );

            return self::renderLogin();
        }

        if (!AuthHelpers::comparePassword($password, $user->getPassword())) {
            LoginRateLimiter::increment();

            $this->log->addWarning(
                'failed login attempt :: bad password :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::WARNING,
                'Your username or password is incorrect, please try again'
            );

            return self::renderLogin();
        }

        $role = $this->roleCollection->fetch($user->getRole());

        AuthHelpers::postprocessLogin($user, $role);

        $this->log->addInfo(
            'login success :: account ' .
            $user->getUuid() .
            ' (' .
            $user->getName() .
            ') :: ip ' .
            $_SERVER['REMOTE_ADDR']
        );

        Messages::add(
            Messages::SUCCESS,
            'Welcome, ' . $user->getName() . '! You are now signed in.'
        );

        return AppHelpers::redirect(
            $_SESSION[AuthHelpers::KEY_REDIRECT] ?? '/'
        );
    }

    /**
     * @return Response
     */
    public function processLogout(): Response
    {
        $this->log->addInfo(
            'logout success:: account ' .
            SessionHelpers::getUserUuid() .
            ' :: ip ' .
            $_SERVER['REMOTE_ADDR']
        );

        AuthHelpers::postprocessLogout();

        Messages::add(
            Messages::SUCCESS,
            'You have been successfully logged out'
        );

        return AppHelpers::redirect('/');
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderLogin(): Response
    {
        if (AuthHelpers::isLoggedIn()) {
            $this->log->addWarning(
                'failed login attempt :: already logged in :: account ' .
                SessionHelpers::getUserUuid() .
                ' :: ip ' .
                $_SERVER['REMOTE_ADDR']
            );

            Messages::add(
                Messages::INFO,
                'You are already logged in'
            );

            return AppHelpers::redirect('/');
        }

        return $this->render('public/auth/login.html.twig');
    }
}