<?php


namespace CDCMastery\Models\Statistics\Bases;


use CDCMastery\Models\Cache\CacheHandler;
use Monolog\Logger;
use mysqli;
use mysqli_stmt;
use RuntimeException;

trait TBaseStats
{
    protected mysqli $db;
    protected Logger $log;
    protected CacheHandler $cache;

    /**
     * Tests constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param CacheHandler $cacheHandler
     */
    public function __construct(mysqli $mysqli, Logger $logger, CacheHandler $cacheHandler)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cache = $cacheHandler;
    }

    protected function prepare_and_bind(string $qry, string $types, ...$binds): mysqli_stmt
    {
        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            throw new RuntimeException("Unable to prepare statement: {$this->db->error}");
        }

        if (!$stmt->bind_param($types, ...$binds) ||
            !$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Unable to execute statement: {$stmt->error}");
        }

        return $stmt;
    }
}