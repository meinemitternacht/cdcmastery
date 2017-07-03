<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\SessionHelpers;

class Home extends RootController
{
    public function renderFrontPage(): string
    {
        return SessionHelpers::isLoggedIn()
            ? $this->renderFrontPageAuth()
            : $this->renderFrontPageAnon();
    }

    private function renderFrontPageAnon(): string
    {
        return $this->render(
            'public/home/home.html.twig'
        );
    }

    private function renderFrontPageAuth(): string
    {
        return $this->render(
            'home/home.html.twig'
        );
    }
}