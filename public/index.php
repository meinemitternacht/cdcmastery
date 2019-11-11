<?php
/** @noinspection PhpRedundantCatchClauseInspection */

use CDCMastery\Controllers\RootController;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use Invoker\Exception\NotCallableException;
use Invoker\Exception\NotEnoughParametersException;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;


try {
    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../src/CDCMastery/Bootstrap.php';
    $dispatcher = require APP_DIR . '/Routes.php';

    $config = $container->get(Config::class);
    $log = $container->get(Logger::class);
    $session = $container->get(Session::class);
    $auth_helpers = $container->get(AuthHelpers::class);
} catch (Throwable $e) {
    if (isset($log) && $log instanceof Logger) {
        $log->addDebug($e);
    }
    $msg = 'Unable to initialize application: ' . $e;
    $response = new Response($msg, 500);
    goto out_respond;
}

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$route = $dispatcher->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $path
);

if (!$auth_helpers->assert_logged_in()) {
    $publicRoutes = array_flip($config->get(['system', 'routing', 'public']));

    if (!isset($publicRoutes[$path])) {
        $auth_helpers->set_redirect($path);
        $session->getFlashBag()->add(MessageTypes::WARNING,
                                     'You must log in to continue');
        $response = RootController::static_redirect('/auth/login');
        goto out_respond;
    }
}

switch ($route[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $msg = '404: ' . $_SERVER['REQUEST_URI'] . ' could not be found';
        $response = new Response($msg, 404);
        $log->error($msg);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $msg = '405: ' . $_SERVER['REQUEST_URI'] . ' method not allowed';
        $response = new Response($msg, 405);
        $log->error($msg);
        break;
    case FastRoute\Dispatcher::FOUND:
        try {
            [, $controller, $parameters] = $route;
            $response = $container->call($controller, $parameters);
        } catch (NotEnoughParametersException $e) {
            $msg = '400: ' . $_SERVER['REQUEST_URI'] . ' bad request :: ' . $e;
            $response = new Response($msg, 400);
            $log->error($msg);
        } catch (NotCallableException $e) {
            $msg = '500: Not Callable :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
            $log->error($msg);
        } catch (Error $e) {
            $msg = '500: TypeError :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
            $log->error($msg);
        } catch (Exception $e) {
            $msg = '500: Exception :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
            $log->error($msg);
        } finally {
            if (!isset($response)) {
                $response = '';
            }
        }
        break;
    default:
        $msg = '500: Not routable';
        $response = new Response($msg, 500);
        $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
        $log->error($msg);
        break;
}

out_respond:
if ($response instanceof Response) {
    $response->send();
    exit;
}

echo $response;