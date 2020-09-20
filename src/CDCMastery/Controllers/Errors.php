<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use Symfony\Component\HttpFoundation\Response;

class Errors extends RootController
{
    public function show_400(): Response
    {
        return $this->render('errors/400.html.twig', ['error' => true], 400, true);
    }

    public function show_401(): Response
    {
        return $this->render('errors/401.html.twig', ['error' => true], 401, true);
    }

    public function show_403(): Response
    {
        return $this->render('errors/403.html.twig', ['error' => true], 403, true);
    }

    public function show_404(): Response
    {
        return $this->render('errors/404.html.twig', ['error' => true], 404, true);
    }

    public function show_405(): Response
    {
        return $this->render('errors/405.html.twig', ['error' => true], 405, true);
    }

    public function show_500(): Response
    {
        return $this->render('errors/500.html.twig', ['error' => true], 500, true);
    }
}