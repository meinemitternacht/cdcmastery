<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:08 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\AuthHelpers;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Admin extends RootController
{
    protected AuthHelpers $auth_helpers;

    /**
     * Admin constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @throws AccessDeniedException
     */
    public function __construct(Logger $logger, Environment $twig, Session $session, AuthHelpers $auth_helpers)
    {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;

        if (!$this->auth_helpers->assert_admin()) {
            throw new AccessDeniedException('Access Denied');
        }
    }
}