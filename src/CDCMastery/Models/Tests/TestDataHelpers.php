<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 11:18 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\Answer;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionAnswer;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use Monolog\Logger;
use mysqli;

class TestDataHelpers
{
    private mysqli $db;
    private Logger $log;
    private AfscCollection $afscs;
    private QuestionHelpers $question_helpers;
    private QuestionCollection $questions;
    private AnswerCollection $answers;

    public function __construct(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        QuestionHelpers $question_helpers,
        QuestionCollection $questions,
        AnswerCollection $answers
    ) {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->afscs = $afscs;
        $this->question_helpers = $question_helpers;
        $this->questions = $questions;
        $this->answers = $answers;
    }

    /**
     * @param Test $test
     * @return int
     */
    public function count(Test $test): int
    {
        if (!$test->getUuid()) {
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        if (!$stmt->bind_param('s', $testUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return 0;
        }

        $stmt->bind_result(
            $responseCount
        );

        $stmt->fetch();
        $stmt->close();

        return $responseCount ?? 0;
    }

    public function fetch(Test $test, Question $question): ?Answer
    {
        if (empty($test->getUuid()) || empty($question->getUuid())) {
            return null;
        }

        $test_uuid = $test->getUuid();
        $q_uuid = $question->getUuid();

        $qry = <<<SQL
SELECT
  answerUUID
FROM testData
WHERE testUUID = ?
  AND questionUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param(
                'ss',
                $test_uuid,
                $q_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result(
            $auuid
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($auuid) || $auuid === '') {
            return null;
        }

        $afsc = $this->afscs->fetch($question->getAfscUuid());

        if (!$afsc) {
            return null;
        }

        return $this->answers->fetch($afsc,
                                     $auuid);
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
ORDER BY questionUUID
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s', $testUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $questionUuid,
            $answerUuid
        );

        $quuids = QuestionHelpers::listUuid(
            $test->getQuestions()
        );

        $answered = [];
        while ($stmt->fetch()) {
            if (!isset($questionUuid) || $questionUuid === '') {
                continue;
            }

            if (!isset($answerUuid) || $answerUuid === '') {
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
        foreach ($quuids as $idx => $quuid) {
            if (!isset($quuids[ $idx ])) {
                continue;
            }

            if (isset($answered[ $quuid ])) {
                continue;
            }

            $unanswered[] = $idx;
        }

        return $unanswered;
    }

    /**
     * @param Test $test
     * @return QuestionAnswer[]
     */
    public function list(Test $test): array
    {
        if (!$test->getUuid()) {
            return [];
        }

        $testUuid = $test->getUuid();

        $qry = <<<SQL
SELECT
  questionUUID,
  answerUUID
FROM testData
WHERE testUUID = ?
ORDER BY questionUUID
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s', $testUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $q_uuid,
            $a_uuid
        );

        $question_answer_uuids = [];
        while ($stmt->fetch()) {
            if (!isset($q_uuid) || $q_uuid === '') {
                continue;
            }

            if (!isset($a_uuid) || $a_uuid === '') {
                continue;
            }

            $question_answer_uuids[ $q_uuid ] = $a_uuid;
        }

        $stmt->close();

        if (!$question_answer_uuids) {
            $qas = [];
            goto out_return;
        }

        $q_afscs = $this->question_helpers->getQuestionsAfscUuids(array_keys($question_answer_uuids));
        $afscs = $this->afscs->fetchArray(array_keys($q_afscs));
        $questions = [];
        $answers = [];
        $correct = [];
        foreach ($q_afscs as $afsc_uuid => $q_uuids) {
            $question_objs = $this->questions->fetchArray($afscs[ $afsc_uuid ], $q_uuids);
            $questions[] = $question_objs;

            $tgt_afsc = $afscs[ $afsc_uuid ];
            $tgt_answer_uuids = array_values(array_intersect_key($question_answer_uuids, $question_objs));
            $answer_objs = $this->answers->fetchArray($tgt_afsc,
                                                      $tgt_answer_uuids);
            $correct[] = $this->answers->fetchCorrectByQuestions($tgt_afsc,
                                                                 $question_objs);

            foreach ($answer_objs as $answer) {
                $aquuid = $answer->getQuestionUuid();
                if (!isset($answers[ $aquuid ])) {
                    $answers[ $aquuid ] = [];
                }

                $answers[ $aquuid ] = $answer;
            }
        }

        if ($questions) {
            $questions = array_replace(...$questions);
        }

        if ($correct) {
            $correct = array_replace(...$correct);
        }

        $qas = [];
        foreach ($question_answer_uuids as $q_uuid => $a_uuid) {
            $qa = new QuestionAnswer();
            $qa->setQuestion($questions[ $q_uuid ]);
            $qa->setAnswer($answers[ $q_uuid ]);
            $qa->setCorrect($correct[ $q_uuid ]);
            $qas[ $q_uuid ] = $qa;
        }

        out_return:
        $t_questions = $test->getQuestions();
        if ($t_questions) {
            $keys = array_flip(QuestionHelpers::listUuid($t_questions));
            $q_diff = array_diff_key($keys, $qas);

            if ($q_diff) {
                foreach ($q_diff as $q_unanswered_key) {
                    if (!isset($t_questions[ $q_unanswered_key ])) {
                        continue;
                    }

                    $qa = new QuestionAnswer();
                    $qa->setQuestion($t_questions[ $q_unanswered_key ]);
                    $qas[ $t_questions[ $q_unanswered_key ]->getUuid() ] = $qa;
                }
            }

            $qas = array_replace($keys, $qas);
        }

        return array_values($qas);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('sss',
                               $testUuid,
                               $questionUuid,
                               $answerUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }
}