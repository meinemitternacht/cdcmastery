<?php


namespace CDCMastery\Helpers;


use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class RequestHelpers
{
    private static function get_param_source(Request $request, string $key): ParameterBag
    {
        if ($request->query->has($key)) {
            return $request->query;
        }

        return $request->request;
    }

    /**
     * @param Request $request
     * @param string $key
     * @param null $default
     * @param int $filter
     * @param array $options
     * @return mixed
     */
    public static function filter(
        Request $request,
        string $key,
        $default = null,
        int $filter = FILTER_DEFAULT,
        $options = []
    ) {
        return self::get_param_source($request, $key)->filter($key, $default, $filter, $options);
    }

    public static function filter_bool_default(Request $request, string $key, ?bool $default = null): ?bool
    {
        return self::get_param_source($request, $key)
                   ->filter($key,
                            $default,
                            FILTER_VALIDATE_BOOLEAN,
                            FILTER_NULL_ON_FAILURE);
    }

    public static function filter_int_default(Request $request, string $key, ?bool $default = null): ?int
    {
        return self::get_param_source($request, $key)
                   ->filter($key,
                            $default,
                            FILTER_VALIDATE_INT,
                            FILTER_NULL_ON_FAILURE);
    }

    public static function filter_string_default(Request $request, string $key): ?string
    {
        return self::get_param_source($request, $key)
                   ->filter($key,
                            null,
                            FILTER_SANITIZE_STRING,
                            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    public static function get(Request $request, string $key, $default = null)
    {
        return self::get_param_source($request, $key)
                   ->get($key, $default);
    }

    public static function has(Request $request, string $key): bool
    {
        return $request->request->has($key) ||
               $request->query->has($key);
    }
}