<?php
declare(strict_types=1);


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Database\DBStats;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class DatabaseStats extends Admin
{
    private DBStats $db_stats;

    /**
     * DatabaseStats constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param DBStats $db_stats
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        DBStats $db_stats
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->db_stats = $db_stats;
    }

    public function show_home(): Response
    {
        $t_stats = $this->db_stats->get_table_stats();
        $t_stats_cols = [];

        if (isset($t_stats[ 0 ])) {
            $t_stats_cols = array_keys($t_stats[ 0 ]);
        }

        $data = [
            't_stats' => $t_stats,
            't_stats_cols' => $t_stats_cols,
        ];

        return $this->render(
            'admin/db/stats.html.twig',
            $data
        );
    }
}
