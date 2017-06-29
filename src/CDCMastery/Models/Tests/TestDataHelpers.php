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
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionAnswer;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
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

    /**
     * @param Test $test
     * @return int
     */
    public function count(Test $test): int
    {
        if (empty($test->getUuid())) {
            return 0;
        }

        $testUuid = $test->getUuid();

        $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testData
WHERE testUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $testUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result(
            $responseCount
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($responseCount) || is_null($responseCount) || empty($responseCount)) {
            return 0;
        }

        return $responseCount;
    }

    /**
     * @param Test $test
     * @param Question $question
     * @return Answer
     */
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

        $questionHelpers = new QuestionHelpers(
            $this->db,
            $this->log
        );

        $afsc = $questionHelpers->getQuestionAfsc($question);

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        return $answerCollection->fetch(
            $afsc,
            $answerUuid
        );
    }

    public function getUnanswered(Test $test): array
    {
        if (empty($test->getUuid())) {
            return [];
        }

        $testUuid = $test->getUuid();

        $qry = <<<SQL
SELECT
  questionUUID,
  answerUUID
FROM testData
WHERE testUUID = ?
ORDER BY questionUUID ASC
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $testUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $questionUuid,
            $answerUuid
        );

        $questionUuidList = QuestionHelpers::listUuid(
            $test->getQuestions()
        );

        $answered = [];
        while ($stmt->fetch()) {
            if (!isset($questionUuid) || is_null($questionUuid) || empty($questionUuid)) {
                continue;
            }

            if (!isset($answerUuid) || is_null($answerUuid) || empty($answerUuid)) {
                continue;
            }

            $answered[] = $questionUuid;
        }

        $stmt->close();

        if (empty($answered)) {
            return [];
        }

        $answered = array_flip($answered);

        $unanswered = [];
        $c = count($questionUuidList);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($questionUuidList[$i])) {
                continue;
            }

            if (isset($answered[$questionUuidList[$i]])) {
                continue;
            }

            $unanswered[] = $questionUuidList[$i];
        }

        return $unanswered;
    }

    /**
     * @param Test $test
     * @return QuestionAnswer[]
     */
    public function list(Test $test): array
    {
        if (empty($test->getUuid())) {
            return [];
        }

        $testUuid = $test->getUuid();

        $qry = <<<SQL
SELECT
  questionUUID,
  answerUUID
FROM testData
WHERE testUUID = ?
ORDER BY questionUUID ASC
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $testUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $questionUuid,
            $answerUuid
        );

        $qaUuid = [];
        while ($stmt->fetch()) {
            if (!isset($questionUuid) || is_null($questionUuid) || empty($questionUuid)) {
                continue;
            }

            if (!isset($answerUuid) || is_null($answerUuid) || empty($answerUuid)) {
                continue;
            }

            $qaUuid[$questionUuid] = $answerUuid;
        }

        $stmt->close();

        if (empty($qaUuid)) {
            return [];
        }

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $questionHelpers = new QuestionHelpers(
            $this->db,
            $this->log
        );

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        $questionAnswers = [];
        foreach ($qaUuid as $qUuid => $aUuid) {
            $afsc = $questionHelpers->getQuestionAfsc($qUuid);

            $questionAnswer = new QuestionAnswer();
            $questionAnswer->setQuestion(
                $questionCollection->fetch(
                    $afsc,
                    $qUuid
                )
            );
            $questionAnswer->setAnswer(
                $answerCollection->fetch(
                    $afsc,
                    $aUuid
                )
            );
            $questionAnswers[] = $questionAnswer;
        }

        return $questionAnswers;
    }

    /**
     * @param QuestionResponse $questionResponse
     */
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