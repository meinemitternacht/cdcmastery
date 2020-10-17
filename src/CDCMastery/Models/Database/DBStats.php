<?php
declare(strict_types=1);


namespace CDCMastery\Models\Database;


use CDCMastery\Helpers\DBLogHelper;
use mysqli;
use Psr\Log\LoggerInterface;

class DBStats
{
    private LoggerInterface $log;
    private mysqli $db;

    public function __construct(LoggerInterface $log, mysqli $db)
    {
        $this->log = $log;
        $this->db = $db;
    }

    public function get_table_stats(): array
    {
        $qry = <<<SQL
SHOW TABLE STATUS LIKE '%'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }

        $filter = array_flip([
                                 'Name',
                                 'Engine',
                                 'Rows',
                                 'Avg_row_length',
                                 'Data_length',
                                 'Index_length',
                                 'Data_free',
                             ]);
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = array_intersect_key($row, $filter);
        }

        $res->free();
        return $out;
    }
}
