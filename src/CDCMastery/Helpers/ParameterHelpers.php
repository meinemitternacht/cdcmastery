<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/5/2017
 * Time: 8:30 PM
 */

namespace CDCMastery\Helpers;


use CDCMastery\Exceptions\Parameters\MissingParameterException;
use Symfony\Component\HttpFoundation\Request;

class ParameterHelpers
{
    /**
     * @param Request $request
     * @param array $parameters
     * @throws MissingParameterException
     */
    public static function checkRequiredParameters(Request $request, array $parameters): void
    {
        if (empty($parameters)) {
            return;
        }

        foreach ($parameters as $parameter) {
            if (!$request->request->has($parameter)) {
                throw new MissingParameterException('A required parameter is missing: ' . $parameter);
            }
        }
    }
}