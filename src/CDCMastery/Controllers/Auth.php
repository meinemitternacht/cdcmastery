<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Exceptions\Parameters\MissingParameterException;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Helpers\ParameterHelpers;
use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\LoginRateLimiter;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserHelpers;

class Auth extends RootController
{
    /**
     * @return string
     */
    public function processLogin(): string
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

        try {
            ParameterHelpers::checkRequiredParameters(
                $this->getRequest(), [
                    'username',
                    'password'
                ]
            );
        } catch (MissingParameterException $e) {
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

        $username = $this->getRequest()->request->get('username');
        $password = $this->getRequest()->request->get('password');

        $userHelpers = $this->container->get(UserHelpers::class);
        $matchUsername = $userHelpers->findByUsername($username);

        $matchEmail = null;
        if (filter_var($username, FILTER_VALIDATE_EMAIL) !== false) {
            $matchEmail = $userHelpers->findByEmail($username);
        }

        $uuid = is_null($matchUsername)
            ? is_null($matchEmail)
                ? null
                : $matchEmail
            : $matchUsername;

        if (is_null($uuid)) {
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

        $userCollection = $this->container->get(UserCollection::class);
        $user = $userCollection->fetch($uuid);

        if (empty($user->getUuid())) {
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

        $roleCollection = $this->container->get(RoleCollection::class);
        $role = $roleCollection->fetch($user->getRole());

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

        $destination = isset($_SESSION[AuthHelpers::KEY_REDIRECT])
            ? $_SESSION[AuthHelpers::KEY_REDIRECT]
            : '/';

        return AppHelpers::redirect($destination);
    }

    /**
     * @return string
     */
    public function processLogout(): string
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
     * @return string
     */
    public function renderLogin(): string
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