<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 11:27 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class AnswerHelpers
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
     * @param $answerOrUuid
     * @return Afsc
     */
    public function getAnswerAfsc($answerOrUuid): Afsc
    {
        if ($answerOrUuid instanceof Answer) {
            $answerOrUuid = $answerOrUuid->getUuid();
        }

        if (!is_string($answerOrUuid)) {
            return new Afsc();
        }

        if (empty($answerOrUuid)) {
            return new Afsc();
        }

        $answerUuid = $answerOrUuid;

        $qry = <<<SQL
SELECT
  afscList.uuid
FROM answerData
LEFT JOIN questionData ON questionData.uuid = answerData.questionUUID
LEFT JOIN afscList ON afscList.uuid = questionData.afscUUID
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $answerUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Afsc();
        }

        $stmt->bind_result(
            $uuid
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($uuid) || is_null($uuid) || empty($uuid)) {
            return new Afsc();
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        return $afscCollection->fetch($uuid);
    }
}