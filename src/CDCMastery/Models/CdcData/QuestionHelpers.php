<?php

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class QuestionHelpers
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * QuestionHelpers constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param Question[] $questions
     * @return string[]
     */
    public static function listUuid(array $questions): array
    {
        return array_map(static function (Question $v): string {
            return $v->getUuid();
        }, $questions);
    }

    public function getQuestionsAfscUuids(array $question_uuids): array
    {
        if (!$question_uuids) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $question_uuids));

        $qry = <<<SQL
SELECT questionData.uuid AS quuid, aL.uuid AS auuid
FROM questionData
LEFT JOIN afscList aL on questionData.afscUUID = aL.uuid
WHERE questionData.uuid IN ('{$uuids_str}')
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $out = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($out[ $row[ 'auuid' ] ])) {
                $out[ $row[ 'auuid' ] ] = [];
            }

            $out[ $row[ 'auuid' ] ][] = $row[ 'quuid' ];
        }

        $res->free();
        return $out;
    }

    /**
     * @param array $uuidList
     *  The list of AFSC UUIDs to retrieve counts for
     * @return array
     */
    public function getNumQuestionsByAfsc(array $uuidList): array
    {
        if (empty($uuidList)) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
SELECT
  afscUUID,
  COUNT(*) AS count
FROM questionData
WHERE afscUUID IN ('{$uuidListString}')
GROUP BY afscUUID
SQL;

        $res = $this->db->query($qry);

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'count' ], $row[ 'afscUUID' ])) {
                continue;
            }

            $data[ $row[ 'afscUUID' ] ] = (int)$row[ 'count' ];
        }

        $res->free();

        return $data;
    }
}