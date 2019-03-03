<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:27 PM
 */

namespace CDCMastery\Helpers;


use Symfony\Component\HttpFoundation\Response;

class AppHelpers
{
    /**
     * @param string $destination
     * @return Response
     */
    public static function redirect(string $destination): Response
    {
        $protocol = isset($_SERVER['HTTPS'])
            ? 'https://'
            : 'http://';

        $response = new Response();
        $response->headers->add(['Location' => $protocol . $_SERVER['HTTP_HOST'] . $destination]);

        return $response;
    }
}