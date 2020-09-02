<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:15 AM
 */

use CDCMastery\Exceptions\Database\DatabaseConnectionFailedException;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Twig\CreateSortLink;
use CDCMastery\Models\Twig\RoleTypes;
use CDCMastery\Models\Twig\UserProfileLink;
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
    Environment::class => function (ContainerInterface $c) {
        $config = $c->get(Config::class);
        $auth_helpers = $c->get(AuthHelpers::class);
        $loader = new FilesystemLoader(VIEWS_DIR);

        $debug = $config->get(['system', 'debug'])
            ?: false;
        $twig = $debug
            ? new Environment($loader, ['debug' => true])
            : new Environment($loader, ['debug' => false, 'cache' => '/tmp/twig_cache']);

        $loggedIn = $auth_helpers->assert_logged_in();

        $twig->addGlobal('cdc_debug', $debug
            ?: false);
        $twig->addGlobal('loggedIn', $loggedIn);
        $twig->addGlobal('cssList', $config->get(['twig', 'assets', 'css']));
        $twig->addGlobal('jsList', $config->get(['twig', 'assets', 'js']));
        $twig->addGlobal('passingScore', $config->get(['testing', 'passingScore']));

        if ($loggedIn) {
            $twig->addGlobal('isAdmin', $auth_helpers->assert_admin());
            $twig->addGlobal('isSupervisor', $auth_helpers->assert_supervisor());
            $twig->addGlobal('isTrainingManager', $auth_helpers->assert_training_manager());
        }

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addExtension(new CreateSortLink());
        $twig->addExtension(new UserProfileLink());
        $twig->addExtension(new RoleTypes());

        return $twig;
    },
    Logger::class => function (ContainerInterface $c) {
        $logger = new Monolog\Logger('CDC');
        $config = $c->get(Config::class);

        $formatter = new LineFormatter(
            null,
            null,
            false,
            true
        );

        if ($config->get(['system','debug'])) {
            $debugHandler = new StreamHandler(
                $config->get(['system','log','debug']),
                Logger::DEBUG
            );

            $debugHandler->setFormatter($formatter);
            $logger->pushHandler($debugHandler);
        }

        $general_log = $config->get(['system','log','general']);
        if (file_exists($general_log) && !is_writable($general_log)) {
            $logger->alert('Log file is not writable: ' . $config->get(['system','log','general']));
            goto out_return;
        }

        $streamHandler = new StreamHandler(
            $config->get(['system','log','general']),
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
    Memcached::class => function (ContainerInterface $c) {
        $memcached = new Memcached();

        $config = $c->get(Config::class);

        $hostList = $config->get(['system', 'memcached']);

        if (is_array($hostList) && count($hostList) > 0) {
            foreach ($hostList as $host) {
                if (!isset($host->host) || !isset($host->port)) {
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
    mysqli::class => function (ContainerInterface $c) {
        $config = $c->get(Config::class);

        $db_conf = $config->get(['system','debug'])
            ? $config->get(['database','dev'])
            : $config->get(['database','prod']);

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
    Session::class => function () {
        $session = new Session();
        $session->start();

        return $session;
    }
];