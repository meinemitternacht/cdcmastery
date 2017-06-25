<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:12 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class QuestionCollection
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
     * @var Question[]
     */
    private $questions = [];

    /**
     * QuestionCollection constructor.
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
     * @return Question
     */
    public function fetch(Afsc $afsc, string $uuid): Question
    {
        if (empty($uuid) || empty($afsc->getUuid())) {
            return new Question();
        }

        if (isset($this->questions[$uuid])) {
            return $this->questions[$uuid];
        }

        return $afsc->isFouo()
            ? $this->fetchEncrypted($uuid)
            : $this->fetchUnencrypted($uuid);
    }

    /**
     * @param Afsc $afsc
     * @return Question[]
     */
    public function fetchAfsc(Afsc $afsc): array
    {
        if (empty($afsc->getUuid())) {
            return [];
        }

        return $afsc->isFouo()
            ? $this->fetchAfscEncrypted($afsc->getUuid())
            : $this->fetchAfscUnencrypted($afsc->getUuid());
    }

    /**
     * @param string $afscUuid
     * @return Question[]
     */
    private function fetchAfscEncrypted(string $afscUuid): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText
FROM questionData
WHERE afscUUID = ?
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text
        );

        $uuidList = [];
        while ($stmt->fetch()) {
            if (is_null($_uuid)) {
                continue;
            }

            $question = new Question();
            $question->setUuid($_uuid);
            $question->setAfscUuid($afscUuid);
            $question->setText($text);

            $this->questions[$uuid] = $question;
            $uuidList[] = $uuid;
        }

        $stmt->close();

        return array_intersect_key(
            $this->questions,
            array_flip($uuidList)
        );
    }

    /**
     * @param string $afscUuid
     * @return Question[]
     */
    private function fetchAfscUnencrypted(string $afscUuid): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText
FROM questionData
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text
        );

        $uuidList = [];
        while ($stmt->fetch()) {
            if (is_null($_uuid)) {
                continue;
            }

            $question = new Question();
            $question->setUuid($_uuid);
            $question->setAfscUuid($afscUuid);
            $question->setText($text);

            $this->questions[$uuid] = $question;
            $uuidList[] = $uuid;
        }

        $stmt->close();

        return array_intersect_key(
            $this->questions,
            array_flip($uuidList)
        );
    }

    /**
     * @param Afsc $afsc
     * @param array $uuidList
     * @return array
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

        $afsc->isFouo()
            ? $this->fetchArrayEncrypted($uuidListString)
            : $this->fetchArrayUnencrypted($uuidListString);

        return array_intersect_key(
            $this->questions,
            array_flip($uuidList)
        );
    }

    /**
     * @param string $uuidList
     */
    private function fetchArrayEncrypted(string $uuidList): void
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText
FROM questionData
WHERE uuid IN ('{$uuidList}')
ORDER BY uuid ASC
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $question = new Question();
            $question->setUuid($row['uuid'] ?? '');
            $question->setAfscUuid($row['afscUUID'] ?? '');
            $question->setText($row['questionText'] ?? '');

            $this->questions[$row['uuid']] = $question;
        }

        $res->free();
    }

    /**
     * @param string $uuidList
     */
    private function fetchArrayUnencrypted(string $uuidList): void
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText
FROM questionData
WHERE uuid IN ('{$uuidList}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $question = new Question();
            $question->setUuid($row['uuid'] ?? '');
            $question->setAfscUuid($row['afscUUID'] ?? '');
            $question->setText($row['questionText'] ?? '');

            $this->questions[$row['uuid']] = $question;
        }

        $res->free();
    }

    /**
     * @param string $uuid
     * @return Question
     */
    private function fetchEncrypted(string $uuid): Question
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText
FROM questionData
WHERE uuid = ?
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Question();
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text
        );

        $stmt->fetch();
        $stmt->close();

        $question = new Question();
        $question->setUuid($_uuid);
        $question->setAfscUuid($afscUuid);
        $question->setText($text);

        $this->questions[$uuid] = $question;

        return $question;
    }

    /**
     * @param string $uuid
     * @return Question
     */
    private function fetchUnencrypted(string $uuid): Question
    {
        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText
FROM questionData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Question();
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text
        );

        $stmt->fetch();
        $stmt->close();

        $question = new Question();
        $question->setUuid($_uuid);
        $question->setAfscUuid($afscUuid);
        $question->setText($text);

        $this->questions[$uuid] = $question;

        return $question;
    }

    /**
     * @return QuestionCollection
     */
    public function refresh(): self
    {
        $this->questions = [];

        return $this;
    }

    /**
     * @param Afsc $afsc
     * @param Question $question
     */
    public function save(Afsc $afsc, Question $question): void
    {
        if (empty($afsc->getUuid()) || empty($question->getUuid())) {
            return;
        }

        $afsc->isFouo()
            ? $this->saveEncrypted($question)
            : $this->saveUnencrypted($question);
    }

    /**
     * @param Afsc $afsc
     * @param array $questions
     */
    public function saveArray(Afsc $afsc, array $questions): void
    {
        if (empty($afsc->getUuid()) || empty($questions)) {
            return;
        }

        $c = count($questions);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            if (!$questions[$i] instanceof Question) {
                continue;
            }

            $this->save($afsc, $questions[$i]);
        }
    }

    /**
     * @param Question $question
     */
    private function saveEncrypted(Question $question): void
    {
        if (empty($question->getUuid())) {
            return;
        }

        $uuid = $question->getUuid();
        $afscUuid = $question->getAfscUuid();
        $text = $question->getText();

        $qry = <<<SQL
INSERT INTO questionData
  (uuid, afscUUID, questionText)
VALUES (?, ?, AES_ENCRYPT(?, '%s'))
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  afscUUID=VALUES(afscUUID),
  questionText=VALUES(questionText)
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $uuid,
            $afscUuid,
            $text
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Question $question
     */
    private function saveUnencrypted(Question $question): void
    {
        if (empty($question->getUuid())) {
            return;
        }

        $uuid = $question->getUuid();
        $afscUuid = $question->getAfscUuid();
        $text = $question->getText();

        $qry = <<<SQL
INSERT INTO questionData
  (uuid, afscUUID, questionText)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  afscUUID=VALUES(afscUUID),
  questionText=VALUES(questionText)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $uuid,
            $afscUuid,
            $text
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }
}