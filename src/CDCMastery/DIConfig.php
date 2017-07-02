<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:15 AM
 */

use Interop\Container\ContainerInterface;


return [
    \Memcached::class => function (ContainerInterface $c) {
        $memcached = new Memcached();

        $config = $c->get(\CDCMastery\Models\Config\Config::class);

        $hostList = $config->get(['system','memcached']);

        if (is_array($hostList) && !empty($hostList)) {
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
    \Monolog\Logger::class => function (ContainerInterface $c) {
        $logger = new Monolog\Logger('DLA');
        $config = $c->get(\CDCMastery\Models\Config\Config::class);

        $formatter = new \Monolog\Formatter\LineFormatter(
            null,
            null,
            false,
            true
        );

        if ($config->get(['system','debug'])) {
            $debugHandler = new \Monolog\Handler\StreamHandler(
                $config->get(['system','log','debug']),
                \Monolog\Logger::DEBUG
            );

            $debugHandler->setFormatter($formatter);
            $logger->pushHandler($debugHandler);
        }

        if (!is_writable($config->get(['system','log','general']))) {
            $logger->alert('Log file is not writable');
        } else {
            $streamHandler = new \Monolog\Handler\StreamHandler(
                $config->get(['system','log','general']),
                \Monolog\Logger::INFO
            );
            $streamHandler->setFormatter($formatter);
            $logger->pushHandler($streamHandler);
        }

        $syslogHandler = new \Monolog\Handler\SyslogHandler('DLA', LOG_SYSLOG, \Monolog\Logger::WARNING);
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        return $logger;
    },
    mysqli::class => function (ContainerInterface $c) {
        $config = $c->get(\CDCMastery\Models\Config\Config::class);

        $database = $config->get(['system','debug'])
            ? $config->get(['database','dev'])
            : $config->get(['database','prod']);

        $db = new mysqli(
            $database->host,
            $database->username,
            $database->password,
            $database->schema,
            $database->port,
            $database->socket
        );

        if ($db->connect_errno) {
            throw new \CDCMastery\Exceptions\Database\DatabaseConnectionFailedException(
                "Could not connect to the database"
            );
        }

        return $db;
    },
    Twig_Environment::class => function (ContainerInterface $c) {
        $config = $c->get(\CDCMastery\Models\Config\Config::class);
        $loader = new Twig_Loader_Filesystem(VIEWS_DIR);

        $twig = $config->get(['system','debug'])
            ? new Twig_Environment($loader, ['debug' => true])
            : new Twig_Environment($loader, ['debug' => false, 'cache' => '/tmp/twig_cache']);

        $loggedIn = \CDCMastery\Helpers\SessionHelpers::isLoggedIn();

        $twig->addGlobal('loggedIn', $loggedIn);
        $twig->addGlobal('cssList', $config->get(['twig','assets','css']));
        $twig->addGlobal('jsList', $config->get(['twig','assets','js']));

        if ($config->get(['system','debug'])) {
            $twig->addExtension(new Twig_Extension_Debug());
        }

        return $twig;
    }
];