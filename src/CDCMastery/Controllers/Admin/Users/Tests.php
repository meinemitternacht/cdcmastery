<?php


namespace CDCMastery\Controllers\Admin\Users;


use CDCMastery\Controllers\Admin;
use CDCMastery\Models\Auth\AuthHelpers;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Tests extends Admin
{
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);
    }
}