<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 11:32 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class QuestionHelpers
{
    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * AnswerHelpers constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
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
        $questions = array_values($questions);

        $c = count($questions);

        $uuidList = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            if (!$questions[$i] instanceof Question) {
                continue;
            }

            $uuidList[] = $questions[$i]->getUuid();
        }

        return $uuidList;
    }

    /**
     * @param $questionOrUuid
     * @return Afsc
     */
    public function getQuestionAfsc($questionOrUuid): Afsc
    {
        if ($questionOrUuid instanceof Question) {
            $questionOrUuid = $questionOrUuid->getUuid();
        }

        if (!is_string($questionOrUuid)) {
            return new Afsc();
        }

        if (empty($questionOrUuid)) {
            return new Afsc();
        }

        $questionUuid = $questionOrUuid;

        $qry = <<<SQL
SELECT
  afscUUID
FROM questionData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $questionUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Afsc();
        }

        $stmt->bind_result(
            $uuid
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($uuid) || $uuid === null || $uuid === '') {
            return new Afsc();
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        return $afscCollection->fetch($uuid);
    }

    /**
     * @param array $uuidList The list of AFSC UUIDs to retrieve counts for
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
            if (!isset($row['count']) || $row['count'] === null) {
                continue;
            }

            if (!isset($row['afscUUID']) || $row['afscUUID'] === null) {
                continue;
            }

            $data[$row['afscUUID']] = (int)$row['count'];
        }

        $res->free();

        return $data;
    }
}