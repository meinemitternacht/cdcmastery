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
        $logger = new Monolog\Logger('CDC');
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

        if (file_exists($config->get(['system','log','general'])) && !is_writable($config->get(['system','log','general']))) {
            $logger->alert('Log file is not writable: ' . $config->get(['system','log','general']));
            goto out_return;
        }

        $streamHandler = new \Monolog\Handler\StreamHandler(
            $config->get(['system','log','general']),
            \Monolog\Logger::INFO
        );
        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler);

        out_return:
        $syslogHandler = new \Monolog\Handler\SyslogHandler('CDC', LOG_SYSLOG, \Monolog\Logger::WARNING);
        $syslogHandler->setFormatter($formatter);
        $logger->pushHandler($syslogHandler);

        return $logger;
    },
    mysqli::class => function (ContainerInterface $c) {
        $config = $c->get(\CDCMastery\Models\Config\Config::class);

        $database = $config->get(['system','debug'])
            ? $config->get(['database','dev'])
            : $config->get(['database','prod']);

        define('ENCRYPTION_KEY', $config->get(['encryption', 'key']));

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

        $loggedIn = \CDCMastery\Models\Auth\AuthHelpers::isLoggedIn();

        $twig->addGlobal('loggedIn', $loggedIn);
        $twig->addGlobal('cssList', $config->get(['twig','assets','css']));
        $twig->addGlobal('jsList', $config->get(['twig','assets','js']));

        if ($loggedIn) {
            $twig->addGlobal(
                'isAdmin',
                \CDCMastery\Models\Auth\AuthHelpers::isAdmin()
            );
            $twig->addGlobal(
                'isSupervisor',
                \CDCMastery\Models\Auth\AuthHelpers::isSupervisor()
            );
            $twig->addGlobal(
                'isTrainingManager',
                \CDCMastery\Models\Auth\AuthHelpers::isTrainingManager()
            );
        }

        if ($config->get(['system','debug'])) {
            $twig->addExtension(new Twig_Extension_Debug());
        }

        $twig->addExtension(new \CDCMastery\Models\Twig\CreateSortLink());

        return $twig;
    }
];