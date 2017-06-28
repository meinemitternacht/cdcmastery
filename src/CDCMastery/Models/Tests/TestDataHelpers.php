<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 11:18 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Models\CdcData\Answer;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\AnswerHelpers;
use CDCMastery\Models\CdcData\Question;
use Monolog\Logger;

class TestDataHelpers
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
     * TestDataHelpers constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    public function fetch(Test $test, Question $question): Answer
    {
        if (empty($test->getUuid()) || empty($question->getUuid())) {
            return new Answer();
        }

        $testUuid = $test->getUuid();
        $questionUuid = $question->getUuid();

        $qry = <<<SQL
SELECT
  answerUUID
FROM testData
WHERE testUUID = ?
  AND questionUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $testUuid,
            $questionUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Answer();
        }

        $stmt->bind_result(
            $answerUuid
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($answerUuid) || is_null($answerUuid) || empty($answerUuid)) {
            return new Answer();
        }

        $answerHelpers = new AnswerHelpers(
            $this->db,
            $this->log
        );

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        return $answerCollection->fetch(
            $answerHelpers->getAnswerAfsc($answerUuid),
            $answerUuid
        );
    }

    public function save(QuestionResponse $questionResponse): void
    {
        if (empty($questionResponse->getTestUuid())) {
            return;
        }

        if (empty($questionResponse->getQuestionUuid())) {
            return;
        }

        if (empty($questionResponse->getAnswerUuid())) {
            return;
        }

        $testUuid = $questionResponse->getTestUuid();
        $questionUuid = $questionResponse->getQuestionUuid();
        $answerUuid = $questionResponse->getAnswerUuid();

        $qry = <<<SQL
INSERT INTO testData
  (testUUID, questionUUID, answerUUID) 
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  testUUID=VALUES(testUUID),
  questionUUID=VALUES(questionUUID),
  answerUUID=VALUES(answerUUID)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $testUuid,
            $questionUuid,
            $answerUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }
}