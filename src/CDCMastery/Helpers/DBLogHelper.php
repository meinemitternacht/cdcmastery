<?php


namespace CDCMastery\Helpers;


use Monolog\Logger;
use mysqli;
use mysqli_stmt;

class DBLogHelper
{
    /**
     * @param Logger $logger
     * @param string $method
     * @param string $query
     * @param mixed $stmt_or_db
     */
    public static function query_error(Logger $logger, string $method, string $query, $stmt_or_db = null): void
    {
        $stmt_error = '[null statement]';
        if ($stmt_or_db instanceof mysqli_stmt) {
            $stmt_error = $stmt_or_db->error;
        }

        if ($stmt_or_db instanceof mysqli) {
            $stmt_error = $stmt_or_db->error;
        }

        $logger->warn('query failed in ' . $method . ' :: ' . serialize($query) . ' :: ' . $stmt_error);
    }
}