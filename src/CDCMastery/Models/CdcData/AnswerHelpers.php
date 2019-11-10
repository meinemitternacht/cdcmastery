<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 11:27 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class AnswerHelpers
{
    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * AnswerHelpers constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param array $uuids
     * @return array
     */
    public function fetchCorrectArray(array $uuids): array
    {
        if (count($uuids) === 0) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT
  uuid,
  answerCorrect
FROM answerData
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        $uuidCorrect = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null ) {
                continue;
            }

            $uuidCorrect[$row['uuid']] = (bool)($row['answerCorrect'] ?? false);
        }

        $res->free();

        return $uuidCorrect;
    }
}