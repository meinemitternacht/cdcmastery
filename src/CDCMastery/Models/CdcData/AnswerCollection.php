<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:44 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class AnswerCollection
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
     * @var Answer[]
     */
    private $answers = [];

    /**
     * @var array
     */
    private $questionAnswerMap = [];

    /**
     * AnswerCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    private function create_object(array $row): Answer
    {
        $answer = new Answer();
        $answer->setUuid($row['uuid']);
        $answer->setQuestionUuid($row['questionUUID']);
        $answer->setText($row['answerText']);
        $answer->setCorrect((bool)$row['answerCorrect']);

        $this->mapQuestionAnswer($answer);
        return $answer;
    }

    private function create_objects(array $data): array
    {
        $answers = [];
        foreach ($data as $row) {
            $answers[$row['uuid']] = $this->create_object($row);
        }

        $this->answers = array_merge($this->answers,
                                     $answers);

        return $answers;
    }

    /**
     * @param Afsc $afsc
     * @param string $uuid
     * @return Answer
     */
    public function fetch(Afsc $afsc, string $uuid): Answer
    {
        if (empty($uuid) || empty($afsc->getUuid())) {
            return new Answer();
        }

        if (isset($this->answers[$uuid])) {
            return $this->answers[$uuid];
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

            $qry = sprintf($qry,
                           ENCRYPTION_KEY);
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Answer();
        }

        $stmt->bind_result(
            $_uuid,
            $questionUuid,
            $text,
            $correct
        );

        $stmt->fetch();
        $stmt->close();

        $answer = $this->create_object(
            [
                'uuid' => $uuid,
                'questionUUID' => $questionUuid,
                'answerText' => $text,
                'answerCorrect' => $correct,
            ]
        );

        return $answer;
    }

    /**
     * @param Afsc $afsc
     * @param string[] $uuids
     * @return Answer[]
     */
    public function fetchArray(Afsc $afsc, array $uuids): array
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
WHERE uuid IN ('{$uuids}')
ORDER BY uuid
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        $this->create_objects($rows);

        return array_intersect_key(
            $this->answers,
            array_flip($uuids)
        );
    }

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
            return [];
        }

        if (!$stmt->bind_param('s',
                               $question_uuid)) {
            $stmt->close();
            return [];
        }

        if (!$stmt->execute()) {
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
     * @param string $questionUuid
     * @return Answer[]
     */
    public function getQuestionAnswers(string $questionUuid): array
    {
        if (empty($questionUuid) || empty($this->answers)) {
            return [];
        }

        return array_intersect_key(
            $this->answers,
            array_flip($this->questionAnswerMap[$questionUuid] ?? [])
        );
    }

    /**
     * @param Answer $answer
     */
    private function mapQuestionAnswer(Answer $answer): void
    {
        if (empty($answer->getUuid()) || empty($answer->getQuestionUuid())) {
            return;
        }

        if (!is_array($this->questionAnswerMap)) {
            $this->questionAnswerMap = [];
        }

        if (!isset($this->questionAnswerMap[$answer->getQuestionUuid()])) {
            $this->questionAnswerMap[$answer->getQuestionUuid()] = [];
        }

        $this->questionAnswerMap[$answer->getQuestionUuid()][] = $answer->getUuid();
    }

    /**
     * @param Afsc $afsc
     * @param array $uuids
     */
    public function preloadQuestionAnswers(Afsc $afsc, array $uuids): void
    {
        if (count($uuids) === 0) {
            return;
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
WHERE questionUUID IN ('{$uuids_str}')
ORDER BY uuid
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $answer = new Answer();
            $answer->setUuid($row['uuid'] ?? '');
            $answer->setQuestionUuid($row['questionUUID'] ?? '');
            $answer->setText($row['answerText'] ?? '');
            $answer->setCorrect($row['answerCorrect'] ?? false);

            $this->answers[$row['uuid']] = $answer;
            $this->mapQuestionAnswer($answer);
        }

        $res->free();
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

        $this->answers[$answer->getUuid()] = $answer;
        $this->mapQuestionAnswer($answer);

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
        $stmt->bind_param(
            'ssss',
            $uuid,
            $questionUuid,
            $text,
            $correct
        );

        if (!$stmt->execute()) {
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
        if (empty($afsc->getUuid()) || empty($answers)) {
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
        $stmt->bind_param(
            'sssi',
            $uuid,
            $questionUuid,
            $text,
            $correct
        );

        $c = count($answers);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($answers[$i])) {
                continue;
            }

            if (!$answers[$i] instanceof Answer) {
                continue;
            }

            $this->answers[$answers[$i]->getUuid()] = $answers[$i];
            $this->mapQuestionAnswer($answers[$i]);

            /** @noinspection PhpUnusedLocalVariableInspection */
            $uuid = $answers[$i]->getUuid();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $questionUuid = $answers[$i]->getQuestionUuid();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $text = $answers[$i]->getText();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $correct = $answers[$i]->isCorrect();

            if (!$stmt->execute()) {
                continue;
            }
        }

        $stmt->close();
    }
}