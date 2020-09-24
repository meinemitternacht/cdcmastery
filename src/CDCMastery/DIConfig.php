<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:15 AM
 */

use CDCMastery\Exceptions\Database\DatabaseConnectionFailedException;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Twig\CreateSortLink;
use CDCMastery\Models\Twig\RoleTypes;
use CDCMastery\Models\Twig\StringFunctions;
use CDCMastery\Models\Twig\UserProfileLink;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\UserAfscAssociations;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;


return [
    Environment::class => static function (ContainerInterface $c) {
        $config = $c->get(Config::class);
        $auth_helpers = $c->get(AuthHelpers::class);
        $loader = new FilesystemLoader(VIEWS_DIR);

        $debug = $config->get(['system', 'debug'])
            ?: false;
        $twig = $debug
            ? new Environment($loader, ['debug' => true, 'cache' => '/tmp/twig_cache'])
            : new Environment($loader, ['debug' => false, 'cache' => '/tmp/twig_cache']);

        $loggedIn = $auth_helpers->assert_logged_in();

        $twig->addGlobal('cdc_debug', $debug);
        $twig->addGlobal('cur_url', parse_url($_SERVER[ 'REQUEST_URI' ], PHP_URL_PATH));
        $twig->addGlobal('logged_in', $loggedIn);
        $twig->addGlobal('css_assets', $config->get(['twig', 'assets', 'css']));
        $twig->addGlobal('js_assets', $config->get(['twig', 'assets', 'js']));
        $twig->addGlobal('passing_score', $config->get(['testing', 'passingScore']));

        if ($loggedIn) {
            $twig->addGlobal('cur_user_uuid', $auth_helpers->get_user_uuid());
            $twig->addGlobal('cur_user_name', $auth_helpers->get_user_name());
            $twig->addGlobal('is_admin', $auth_helpers->assert_admin());
            $twig->addGlobal('is_supervisor', $auth_helpers->assert_supervisor());
            $twig->addGlobal('is_training_manager', $auth_helpers->assert_training_manager());
            $twig->addGlobal('is_user', $auth_helpers->assert_user());
        }

        if (!$auth_helpers->assert_user() && !$auth_helpers->assert_supervisor()) {
            $twig->addGlobal('pending_roles', $c->get(PendingRoleCollection::class)->count());
            $twig->addGlobal('pending_activations', $c->get(ActivationCollection::class)->count());
            $twig->addGlobal('pending_assocs', $c->get(UserAfscAssociations::class)->countPending());
        }

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addExtension(new CreateSortLink());
        $twig->addExtension(new UserProfileLink());
        $twig->addExtension(new RoleTypes());
        $twig->addExtension(new StringFunctions());

        return $twig;
    },
    Logger::class => static function (ContainerInterface $c) {
        $logger = new Monolog\Logger('CDC');
        $config = $c->get(Config::class);

        $formatter = new LineFormatter(
            null,
            null,
            false,
            true
        );

        $debugHandler = new StreamHandler(
            $config->get(['system', 'log', 'debug']),
            Logger::DEBUG
        );

        $debugHandler->setFormatter($formatter);
        $logger->pushHandler($debugHandler);

        $general_log = $config->get(['system', 'log', 'general']);
        if (file_exists($general_log) && !is_writable($general_log)) {
            $logger->alert('Log file is not writable: ' . $config->get(['system', 'log', 'general']));
            goto out_return;
        }

        $streamHandler = new StreamHandler(
            $config->get(['system', 'log', 'general']),
            Logger::INFO
        );
        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler);

        out_return:
        $syslogHandler = new SyslogHandler('CDC', LOG_SYSLOG, Logger::WARNING);
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        return $logger;
    },
    Memcached::class => static function (ContainerInterface $c) {
        $memcached = new Memcached();

        $hostList = $c->get(Config::class)->get(['system', 'memcached']);

        if (is_array($hostList) && $hostList) {
            foreach ($hostList as $host) {
                if (!isset($host->host, $host->port)) {
                    continue;
                }

                $memcached->addServer(
                    $host->host,
                    $host->port
                );
            }
        }

        return $memcached;
    },
    mysqli::class => static function (ContainerInterface $c) {
        $config = $c->get(Config::class);

        $db_conf = $config->get(['system', 'debug'])
            ? $config->get(['database', 'dev'])
            : $config->get(['database', 'prod']);

        define('ENCRYPTION_KEY', $config->get(['encryption', 'key']));

        $db = new mysqli(
            $db_conf->host,
            $db_conf->username,
            $db_conf->password,
            $db_conf->schema,
            $db_conf->port,
            $db_conf->socket
        );

        if ($db->connect_errno) {
            throw new DatabaseConnectionFailedException(
                "Could not connect to the database"
            );
        }

        return $db;
    },
    Session::class => static function () {
        $session = new Session();
        $session->start();

        return $session;
    },
    Swift_Mailer::class => static function (ContainerInterface $c) {
        $config = $c->get(Config::class);
        $settings = [
            'host' => $config->get(['email', 'host']),
            'port' => $config->get(['email', 'port']),
            'username' => $config->get(['email', 'username']),
            'password' => $config->get(['email', 'password']),
        ];

        return (new Swift_SmtpTransport($settings[ 'host' ], $settings[ 'port' ]))
            ->setUsername($settings[ 'username' ])
            ->setPassword($settings[ 'password' ]);
    },
];