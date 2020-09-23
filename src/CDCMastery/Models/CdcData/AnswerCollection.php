<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:44 PM
 */

namespace CDCMastery\Models\CdcData;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class AnswerCollection
{
    protected mysqli $db;
    protected Logger $log;

    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param array $data
     * @return Answer[]
     */
    private function create_objects(array $data): array
    {
        $answers = [];
        foreach ($data as $row) {
            $answer = new Answer();
            $answer->setUuid($row[ 'uuid' ]);
            $answer->setQuestionUuid($row[ 'questionUUID' ]);
            $answer->setText($row[ 'answerText' ]);
            $answer->setCorrect((bool)$row[ 'answerCorrect' ]);

            $answers[ $row[ 'uuid' ] ] = $answer;
        }

        return $answers;
    }

    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $qry = <<<SQL
DELETE FROM answerData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    public function deleteArray(array $uuids): void
    {
        foreach ($uuids as $uuid) {
            $this->delete($uuid);
        }
    }

    public function fetch(Afsc $afsc, string $uuid): ?Answer
    {
        if (empty($uuid) || empty($afsc->getUuid())) {
            return null;
        }

        $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE uuid = ?
SQL;
        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE uuid = ?
SQL;

            $qry = sprintf($qry, ENCRYPTION_KEY);
        }

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result(
            $_uuid,
            $questionUuid,
            $text,
            $correct
        );

        $stmt->fetch();
        $stmt->close();

        $row = [
            'uuid' => $uuid,
            'questionUUID' => $questionUuid,
            'answerText' => $text,
            'answerCorrect' => $correct,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    /**
     * @param Afsc $afsc
     * @param string[] $uuids
     * @return Answer[]
     */
    public function fetchArray(Afsc $afsc, array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();
        return $this->create_objects($rows);
    }

    /**
     * @param Afsc $afsc
     * @param Question $question
     * @return Answer[]
     */
    public function fetchByQuestion(Afsc $afsc, Question $question): array
    {
        $question_uuid = $question->getUuid();

        if ($question_uuid === '') {
            return [];
        }

        $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE questionUUID = ?
ORDER BY answerText
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE questionUUID = ?
ORDER BY answerText
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s',
                               $question_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        if (!$stmt->bind_result($uuid,
                                $questionUUID,
                                $answerText,
                                $answerCorrect)) {
            $stmt->close();
            return [];
        }

        $rows = [];
        while ($stmt->fetch()) {
            $rows[] = [
                'uuid' => $uuid,
                'questionUUID' => $questionUUID,
                'answerText' => $answerText,
                'answerCorrect' => $answerCorrect,
            ];
        }

        $stmt->close();
        return $this->create_objects($rows);
    }

    /**
     * @param Afsc $afsc
     * @param Question[] $questions
     * @return Answer[]
     */
    public function fetchByQuestions(Afsc $afsc, array $questions): array
    {
        $uuids = array_map(static function (Question $v): string {
            return $v->getUuid();
        }, $questions);

        if (!$uuids) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE questionUUID IN ('{$uuids_str}')
ORDER BY answerText
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE questionUUID IN ('{$uuids_str}')
ORDER BY answerText
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        $res->free();
        return $this->create_objects($rows);
    }

    /**
     * @param Afsc $afsc
     * @param Answer $answer
     */
    public function save(Afsc $afsc, Answer $answer): void
    {
        if (empty($afsc->getUuid()) || empty($answer->getUuid())) {
            return;
        }

        $uuid = $answer->getUuid();
        $questionUuid = $answer->getQuestionUuid();
        $text = $answer->getText();
        $correct = $answer->isCorrect();

        $qry = <<<SQL
INSERT INTO answerData
  (uuid, questionUUID, answerText, answerCorrect)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  questionUUID=VALUES(questionUUID),
  answerText=VALUES(answerText),
  answerCorrect=VALUES(answerCorrect)
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
INSERT INTO answerData
  (uuid, questionUUID, answerText, answerCorrect)
VALUES (?, ?, AES_ENCRYPT(?, '%s'), ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  questionUUID=VALUES(questionUUID),
  answerText=VALUES(answerText),
  answerCorrect=VALUES(answerCorrect)
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssss',
                               $uuid,
                               $questionUuid,
                               $text,
                               $correct) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Afsc $afsc
     * @param array $answers
     */
    public function saveArray(Afsc $afsc, array $answers): void
    {
        if (!$answers || empty($afsc->getUuid())) {
            return;
        }

        $qry = <<<SQL
INSERT INTO answerData
  (uuid, questionUUID, answerText, answerCorrect)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  questionUUID=VALUES(questionUUID),
  answerText=VALUES(answerText),
  answerCorrect=VALUES(answerCorrect)
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
INSERT INTO answerData
  (uuid, questionUUID, answerText, answerCorrect)
VALUES (?, ?, AES_ENCRYPT(?, '%s'), ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  questionUUID=VALUES(questionUUID),
  answerText=VALUES(answerText),
  answerCorrect=VALUES(answerCorrect)
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $uuid = null;
        $questionUuid = null;
        $text = null;
        $correct = null;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('sssi',
                               $uuid,
                               $questionUuid,
                               $text,
                               $correct)) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        foreach ($answers as $answer) {
            if (!$answer instanceof Answer) {
                continue;
            }

            $uuid = $answer->getUuid();
            $questionUuid = $answer->getQuestionUuid();
            $text = $answer->getText();
            $correct = $answer->isCorrect();

            if (!$stmt->execute()) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            }
        }

        $stmt->close();
    }
}