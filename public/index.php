<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 7/2/17
 * Time: 1:27 PM
 */
/**
 * @var \DI\Container $container
 */
$container = require __DIR__ . '/../src/CDCMastery/Bootstrap.php';
$dispatcher = require APP_DIR . '/Routes.php';

$config = $container->get(\CDCMastery\Models\Config\Config::class);
$log = $container->get(\Monolog\Logger::class);

$path = parse_url(
    $_SERVER[ 'REQUEST_URI' ],
    PHP_URL_PATH
);

$route = $dispatcher->dispatch(
    $_SERVER[ 'REQUEST_METHOD' ],
    $path
);

/**
 * Check desired path against public routes if not logged in
 */
if (!\CDCMastery\Models\Auth\AuthHelpers::isLoggedIn()) {
    $publicRoutes = array_flip($config->get(['system','routing','public']));

    if (!isset($publicRoutes[ $path ])) {
        $_SESSION[ 'login-redirect' ] = $path;

        \CDCMastery\Models\Messages\Messages::add(
            \CDCMastery\Models\Messages\Messages::WARNING,
            'You must log in to continue'
        );

        \CDCMastery\Helpers\AppHelpers::redirect(
            '/auth/login'
        );

        exit(1);
    }
}

switch ($route[ 0 ]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        $response = '';
        $log->error('404: ' . $_SERVER[ 'REQUEST_URI' ] . ' could not be found');
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        $response = '';
        $log->error('405: ' . $_SERVER[ 'REQUEST_URI' ] . ' method not allowed');
        break;
    case FastRoute\Dispatcher::FOUND:
        try {
            $controller = $route[ 1 ];
            $parameters = $route[ 2 ];

            $response = $container->call($controller, $parameters);
        } catch (\Invoker\Exception\NotEnoughParametersException $notEnoughParametersException) {
            http_response_code(400);
            $log->error('400: ' . $_SERVER[ 'REQUEST_URI' ] . ' bad request :: ' . $notEnoughParametersException);
        } catch (\Invoker\Exception\NotCallableException $notCallableException) {
            http_response_code(500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error('500: Not Callable :: ' . $notCallableException);
        } catch (Error $typeError) {
            http_response_code(500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error('500: TypeError :: ' . $typeError);
        } catch (Exception $exception) {
            http_response_code(500);
            $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
            $log->error('500: Exception :: ' . $exception);
        } finally {
            if (!isset($response)) {
                $response = '';
            }
        }
        break;
    default:
        http_response_code(500);
        $response = '';
        $log->info('Request URI: ' . $_SERVER[ 'REQUEST_URI' ]);
        $log->error('500: Not routable');
        break;
}

echo $response;