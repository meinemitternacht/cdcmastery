<?php


namespace CDCMastery\Controllers;


use Symfony\Component\HttpFoundation\Response;

class About extends RootController
{
    public function show_contact(): Response
    {
        return $this->render('public/about/contact.html.twig');
    }

    public function show_disclaimer(): Response
    {
        return $this->render('public/about/disclaimer.html.twig');
    }

    public function show_privacy_policy(): Response
    {
        return $this->render('public/about/privacy.html.twig');
    }

    public function show_terms_of_use(): Response
    {
        return $this->render('public/about/terms.html.twig');
    }
}