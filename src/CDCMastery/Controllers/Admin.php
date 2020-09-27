<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:08 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Config\Config;
use Memcached;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Admin extends RootController
{
    protected AuthHelpers $auth_helpers;
    protected CacheHandler $cache;
    protected Config $config;

    /**
     * Admin constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;

        if ($this->auth_helpers->assert_user() ||
            $this->auth_helpers->assert_supervisor()) {
            $this->trigger_request_debug(__METHOD__);
            throw new AccessDeniedException('Access Denied');
        }

        $this->config = $config;
        $this->cache = $cache;
    }

    public function show_memcached_stats(): Response
    {
        $session_servers = $this->config->get(['system', 'memcached_sessions']);

        $stats = $this->cache->stats();
        if (is_array($session_servers) && $session_servers) {
            $server_conn = new Memcached();
            foreach ($session_servers as $server) {
                if (isset($server->socket) && is_file($server->socket)) {
                    $server_conn->addServer($server->socket, 0);
                    continue;
                }

                if (isset($server->host, $server->port)) {
                    $server_conn->addServer($server->host, $server->port);
                }
            }

            $sess_stats = $server_conn->getStats();

            if ($sess_stats) {
                $stats = array_replace($stats, $sess_stats);
            }
        }

        $data = [
            'stats' => $stats,
        ];

        return $this->render(
            'admin/memcached/stats.html.twig',
            $data
        );
    }
}