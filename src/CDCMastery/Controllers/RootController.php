<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 8:13 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Models\Messages\Messages;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootController
{
    /**
     * @var Logger $log
     */
    protected $log;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var Request
     */
    protected $request;

    /**
     * RootController constructor.
     * @param Logger $logger
     * @param \Twig_Environment $twig
     */
    public function __construct(Logger $logger, \Twig_Environment $twig)
    {
        $this->log = $logger;
        $this->twig = $twig;

        $this->request = Request::createFromGlobals();
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param array $parameters
     * @param Request|null $request
     * @return bool
     */
    public function checkParameters(array $parameters, ?Request $request = null): bool
    {
        foreach ($parameters as $parameter) {
            if ($request !== null && $request->request->has($parameter)) {
                continue;
            }

            if ($request !== null && !$request->request->has($parameter)) {
                return false;
            }

            if (!$this->has($parameter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param null|mixed $default
     * @param int $filter
     * @param array|mixed $options
     * @return mixed
     */
    public function filter(string $key, $default = null, int $filter = FILTER_DEFAULT, $options = [])
    {
        return $this->request->request->filter($key, $default, $filter, $options);
    }

    /**
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->request->request->get($key, $default);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->request->request->has($key);
    }

    /**
     * @param string $template
     * @param array $data
     * @param int $status
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(string $template, array $data = [], int $status = 200): Response
    {
        return new Response(
            $this->twig->render(
                $template,
                array_merge(
                    $data,
                    [
                        'messages' => Messages::get(),
                        'uri' => $this->request->getRequestUri(),
                    ]
                )
            ),
            $status
        );
    }
}