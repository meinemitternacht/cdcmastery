<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:08 PM
 */

namespace CDCMastery\Controllers;


use http\Exception\RuntimeException;
use Monolog\Logger;

class Admin extends RootController
{
    public function __construct(Logger $logger, \Twig_Environment $twig)
    {
        parent::__construct($logger, $twig);

        if (!\CDCMastery\Models\Auth\AuthHelpers::isAdmin()) {
            throw new RuntimeException('Access Denied');
        }
    }
}