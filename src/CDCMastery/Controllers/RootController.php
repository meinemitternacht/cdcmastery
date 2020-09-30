<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 8:13 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Helpers\RequestHelpers;
use CDCMastery\Models\Messages\MessageTypes;
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
    protected Logger $log;
    protected Environment $twig;
    protected Session $session;
    protected Request $request;

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
        $protocol = isset($_SERVER[ 'HTTPS' ])
            ? 'https://'
            : 'http://';

        $response = new Response();
        $response->headers->add(['Location' => $protocol . $_SERVER[ 'HTTP_HOST' ] . $destination]);

        return $response;
    }

    /**
     * @param string $destination
     * @return Response
     */
    public function redirect(string $destination): Response
    {
        self::static_redirect($destination)->send();
        exit;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
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
        $missing = [];
        foreach ($parameters as $parameter) {
            if (!$this->has($parameter) || $this->get($parameter) === '') {
                $missing[] = $parameter;
            }
        }

        if (!$missing) {
            return true;
        }

        $this->flash()->add(
            MessageTypes::ERROR,
            'Your request was missing one or more required parameters: ' .
            implode(', ', $missing)
        );

        return false;
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
        return RequestHelpers::filter($this->request, $key, $default, $filter, $options);
    }

    public function filter_bool_default(string $key, ?bool $default = null): ?bool
    {
        return RequestHelpers::filter_bool_default($this->request, $key, $default);
    }

    public function filter_int_default(string $key, ?bool $default = null): ?int
    {
        return RequestHelpers::filter_int_default($this->request, $key, $default);
    }

    public function filter_string_default(string $key): ?string
    {
        return RequestHelpers::filter_string_default($this->request, $key);
    }

    /**
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return RequestHelpers::get($this->request, $key, $default);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return RequestHelpers::has($this->request, $key);
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
            $this->log->error(__METHOD__ . " :: {$e}");

            if ($error_controller) {
                echo "There was a problem handling your request.";
                exit;
            }

            return (new Errors($this->log, $this->twig, $this->session, $this->request))->show_500();
        }
    }

    /** @noinspection JsonEncodingApiUsageInspection */
    public function trigger_request_debug(string $method): void
    {
        $this->log->debug(str_repeat('-', 40));
        $this->log->debug("{$method} :: request debug");
        $this->log->debug(json_encode($this->request->server->all()));
        $this->log->debug(
            json_encode(
                array_diff_key($this->request->request->all(),
                               array_flip(['password', 'password_confirm']))));
        $this->log->debug(json_encode($this->request->query->all()));
        $this->log->debug(json_encode($this->session->all()));
        $this->log->debug(str_repeat('-', 40));
    }
}