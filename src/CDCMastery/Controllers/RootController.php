<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 8:13 PM
 */

namespace CDCMastery\Controllers;


use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class RootController
{
    /**
     * @var Logger $log
     */
    protected $log;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Request
     */
    protected $request;

    /**
     * RootController constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param Request|null $request
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        ?Request $request = null
    ) {
        $this->log = $logger;
        $this->twig = $twig;
        $this->session = $session;

        $this->request = $request ?? Request::createFromGlobals();
    }

    public static function static_redirect(string $destination): Response
    {
        $protocol = isset($_SERVER['HTTPS'])
            ? 'https://'
            : 'http://';

        $response = new Response();
        $response->headers->add(['Location' => $protocol . $_SERVER['HTTP_HOST'] . $destination]);

        return $response;
    }

    /**
     * @param string $destination
     * @return Response
     */
    public function redirect(string $destination): Response
    {
        return self::static_redirect($destination);
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @return FlashBagInterface
     */
    public function flash(): FlashBagInterface
    {
        return $this->session->getFlashBag();
    }

    /**
     * @param array $parameters
     * @return bool
     */
    public function checkParameters(array $parameters): bool
    {
        foreach ($parameters as $parameter) {
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

    public function filter_bool_default(string $key, ?bool $default = null): ?bool
    {
        return $this->request->request->filter($key,
                                               $default,
                                               FILTER_VALIDATE_BOOLEAN,
                                               FILTER_NULL_ON_FAILURE);
    }

    public function filter_string_default(string $key): ?string
    {
        return $this->request->request->filter($key,
                                               null,
                                               FILTER_SANITIZE_STRING,
                                               FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
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
     * @param bool $error_controller
     * @return Response
     */
    public function render(
        string $template,
        array $data = [],
        int $status = 200,
        bool $error_controller = false
    ): Response {
        try {
            return new Response(
                $this->twig->render(
                    $template,
                    array_merge(
                        $data,
                        [
                            'messages' => $this->flash()->all(),
                            'uri' => $this->request->getRequestUri(),
                        ]
                    )
                ),
                $status
            );
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->log->addDebug(__METHOD__ . " :: {$e}");

            if ($error_controller) {
                echo "There was a problem handling your request.";
                exit;
            }

            return (new Errors($this->log, $this->twig, $this->session, $this->request))->show_500();
        }
    }
}