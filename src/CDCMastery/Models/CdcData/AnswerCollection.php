<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:44 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class AnswerCollection
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
     * @var Answer[]
     */
    private $answers = [];

    /**
     * @var array
     */
    private $questionAnswerMap = [];

    /**
     * AnswerCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
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

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
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

        $answer = new Answer();
        $answer->setUuid($_uuid);
        $answer->setQuestionUuid($questionUuid);
        $answer->setText($text);
        $answer->setCorrect($correct);

        $this->answers[$uuid] = $answer;
        $this->mapQuestionAnswer($answer);

        return $answer;
    }

    /**
     * @param Afsc $afsc
     * @param string[] $uuidList
     * @return Answer[]
     */
    public function fetchArray(Afsc $afsc, array $uuidList): array
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
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE uuid IN ('{$uuidList}')
ORDER BY uuid ASC
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

        return array_intersect_key(
            $this->answers,
            array_flip($uuidList)
        );
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
     * @param array $uuidList
     */
    public function preloadQuestionAnswers(Afsc $afsc, array $uuidList): void
    {
        if (empty($uuidList)) {
            return;
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  answerText,
  answerCorrect
FROM answerData
WHERE questionUUID IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  questionUUID,
  AES_DECRYPT(answerText, '%s') as answerText,
  answerCorrect
FROM answerData
WHERE questionUUID IN ('{$uuidListString}')
ORDER BY uuid ASC
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
     * @return AnswerCollection
     */
    public function refresh(): self
    {
        $this->answers = [];
        $this->questionAnswerMap = [];

        return $this;
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