<?php
/** @noinspection PhpRedundantCatchClauseInspection */

use CDCMastery\Controllers\Errors;
use CDCMastery\Controllers\RootController;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\UserCollection;
use Invoker\Exception\NotCallableException;
use Invoker\Exception\NotEnoughParametersException;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

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
    $response->send();
    exit;
}

define('CDC_DEBUG', (bool)$config->get(['system', 'debug']));
define('SYSTEM_UUID', $config->get(['system', 'uuid']));
define('SUPPORT_EMAIL', $config->get(['email', 'username']));
define('SUPPORT_FACEBOOK_URL', $config->get(['support', 'facebook', 'url']));
define('SUPPORT_FACEBOOK_DISPLAY', $config->get(['support', 'facebook', 'display']));

$path = parse_url(
    $_SERVER[ 'REQUEST_URI' ],
    PHP_URL_PATH
);

$route = $dispatcher->dispatch(
    $_SERVER[ 'REQUEST_METHOD' ],
    $path
);

$logged_in = $auth_helpers->assert_logged_in();

if (!$logged_in) {
    $publicRoutes = array_flip($config->get(['system', 'routing', 'public']));
    $publicPrefixes = $config->get(['system', 'routing', 'public_prefix']);

    foreach ($publicPrefixes as $publicPrefix) {
        if (str_starts_with($path, $publicPrefix)) {
            goto public_route_ok;
        }
    }

    if (!isset($publicRoutes[ $path ])) {
        $auth_helpers->set_redirect($path);
        $session->getFlashBag()->add(MessageTypes::WARNING,
                                     'You must log in to continue');
        $response = RootController::static_redirect('/auth/login');
        goto out_respond;
    }

    public_route_ok:
}

if ($logged_in) {
    $users = $container->get(UserCollection::class);
    $user = $users->fetch($auth_helpers->get_user_uuid());

    if (!$user) {
        $session->getFlashBag()->add(MessageTypes::WARNING,
                                     'Your user account could not be located');
        $response = RootController::static_redirect('/auth/logout');
        goto out_respond;
    }

    if ($auth_helpers->get_role_uuid() !== $user->getRole()) {
        $auth_helpers->logout_hook();
        $session->start();

        $session->getFlashBag()->add(
            MessageTypes::INFO,
            'Your role has been changed. Please log in again.'
        );

        $response = RootController::static_redirect('/auth/login');
        goto out_respond;
    }

    $user->setLastActive(new DateTime());
    $users->save($user);
    date_default_timezone_set($user->getTimeZone());
    DateTimeHelpers::set_user_tz($user->getTimeZone());
}

switch ($route[ 0 ]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $msg = '404: ' . $_SERVER[ 'REQUEST_URI' ] . ' could not be found';
        $response = new Response($msg, 404);
        $log->error($msg);
        goto out_error_404;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $msg = '405: ' . $_SERVER[ 'REQUEST_URI' ] . ' method not allowed';
        $response = new Response($msg, 405);
        $log->error($msg);
        goto out_error_405;
    case FastRoute\Dispatcher::FOUND:
        try {
            [, $controller, $parameters] = $route;
            $response = $container->call($controller, $parameters);
        } catch (NotEnoughParametersException $e) {
            $msg = '400: ' . $_SERVER['REQUEST_URI'] . ' bad request :: ' . $e;
            $response = new Response($msg, 400);
            $log->error($msg);
            goto out_error_400;
        } catch (NotCallableException $e) {
            $msg = '500: Not Callable :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error($msg);
            goto out_error_500;
        } catch (Error $e) {
            $msg = '500: TypeError :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error($msg);
            goto out_error_500;
        } catch (AccessDeniedException $e) {
            $msg = '403: Access Denied :: ' . $e;
            $response = new Response($msg, 403);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error($msg);
            goto out_error_403;
        } catch (Exception $e) {
            $msg = '500: Exception :: ' . $e;
            $response = new Response($msg, 500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error($msg);
            goto out_error_500;
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

out_error_400:
(new Errors($log, $container->get(Environment::class), $session))->show_400()->send();
exit;

out_error_403:
(new Errors($log, $container->get(Environment::class), $session))->show_403()->send();
exit;

out_error_404:
(new Errors($log, $container->get(Environment::class), $session))->show_404()->send();
exit;

out_error_405:
(new Errors($log, $container->get(Environment::class), $session))->show_405()->send();
exit;

out_error_500:
(new Errors($log, $container->get(Environment::class), $session))->show_500()->send();
exit;