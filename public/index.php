<?php
/** @noinspection PhpRedundantCatchClauseInspection */
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 7/2/17
 * Time: 1:27 PM
 */
/** @var \DI\Container $container */
$container = require __DIR__ . '/../src/CDCMastery/Bootstrap.php';
$dispatcher = require APP_DIR . '/Routes.php';

try {
    $config = $container->get(\CDCMastery\Models\Config\Config::class);
    $log = $container->get(\Monolog\Logger::class);
} catch (\Throwable $e) {
    $msg = 'Unable to retrieve configuration or logger.';
    $response = new \Symfony\Component\HttpFoundation\Response($msg, 500);
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

if (!\CDCMastery\Models\Auth\AuthHelpers::isLoggedIn()) {
    $publicRoutes = array_flip($config->get(['system', 'routing', 'public']));

    if (!isset($publicRoutes[$path])) {
        $_SESSION['login-redirect'] = $path;

        \CDCMastery\Models\Messages\Messages::add(
            \CDCMastery\Models\Messages\Messages::WARNING,
            'You must log in to continue'
        );

        $response = \CDCMastery\Helpers\AppHelpers::redirect(
            '/auth/login'
        );

        goto out_respond;
    }
}

switch ($route[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $msg = '404: ' . $_SERVER['REQUEST_URI'] . ' could not be found';
        $response = new \Symfony\Component\HttpFoundation\Response($msg, 404);
        $log->error($msg);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $msg = '405: ' . $_SERVER['REQUEST_URI'] . ' method not allowed';
        $response = new \Symfony\Component\HttpFoundation\Response($msg, 405);
        $log->error($msg);
        break;
    case FastRoute\Dispatcher::FOUND:
        try {
            [, $controller, $parameters] = $route;
            $response = $container->call($controller, $parameters);
        } catch (\Invoker\Exception\NotEnoughParametersException $notEnoughParametersException) {
            $msg = '400: ' . $_SERVER['REQUEST_URI'] . ' bad request :: ' . $notEnoughParametersException;
            $response = new \Symfony\Component\HttpFoundation\Response($msg, 400);
            $log->error($msg);
        } catch (\Invoker\Exception\NotCallableException $notCallableException) {
            $msg = '500: Not Callable :: ' . $notCallableException;
            $response = new \Symfony\Component\HttpFoundation\Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
            $log->error($msg);
        } catch (Error $typeError) {
            $msg = '500: TypeError :: ' . $typeError;
            $response = new \Symfony\Component\HttpFoundation\Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
            $log->error($msg);
        } catch (Exception $exception) {
            $msg = '500: Exception :: ' . $exception;
            $response = new \Symfony\Component\HttpFoundation\Response($msg, 500);
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
        $response = new \Symfony\Component\HttpFoundation\Response($msg, 500);
        $log->info('Request URI: ' . $_SERVER['REQUEST_URI']);
        $log->error($msg);
        break;
}

out_respond:
if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
    $response->send();
    exit;
}

echo $response;