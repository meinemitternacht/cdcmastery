<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:12 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class QuestionCollection
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
     * @var Question[]
     */
    private $questions = [];

    /**
     * QuestionCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $qry = <<<SQL
DELETE FROM questionData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $qry = <<<SQL
DELETE FROM answerData
WHERE questionUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        unset($this->questions[ $uuid ]);
    }

    public function deleteArray(array $uuids): void
    {
        foreach ($uuids as $uuid) {
            $this->delete($uuid);
        }
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

        if (isset($this->questions[ $uuid ])) {
            return $this->questions[ $uuid ];
        }

        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE uuid = ?
  AND afscUUID = ?
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText,
  disabled
FROM questionData
WHERE uuid = ?
  AND afscUUID = ?
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $afsc_uuid = $afsc->getUuid();
        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('ss', $uuid, $afsc_uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Question();
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text,
            $disabled
        );

        $stmt->fetch();
        $stmt->close();

        $question = new Question();
        $question->setUuid($_uuid);
        $question->setAfscUuid($afscUuid);
        $question->setText($text);
        $question->setDisabled((bool)($disabled ?? false));

        $this->questions[ $uuid ] = $question;

        return $question;
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

        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE afscUUID = ?
ORDER BY questionText
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as qtext,
  disabled
FROM questionData
WHERE afscUUID = ?
ORDER BY qtext
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $uuid = $afsc->getUuid();

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text,
            $disabled
        );

        $uuidList = [];
        while ($stmt->fetch()) {
            if ($_uuid === null) {
                continue;
            }

            $question = new Question();
            $question->setUuid($_uuid);
            $question->setAfscUuid($afscUuid);
            $question->setText($text);
            $question->setDisabled((bool)($disabled ?? false));

            $this->questions[ $_uuid ] = $question;
            $uuidList[] = $_uuid;
        }

        $stmt->close();

        return array_intersect_key(
            $this->questions,
            array_flip($uuidList)
        );
    }

    /**
     * @param Afsc $afsc
     * @param string[] $uuidList
     * @return Question[]
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
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === null) {
                continue;
            }

            $question = new Question();
            $question->setUuid($row[ 'uuid' ] ?? '');
            $question->setAfscUuid($row[ 'afscUUID' ] ?? '');
            $question->setText($row[ 'questionText' ] ?? '');
            $question->setDisabled((bool)($row[ 'disabled' ] ?? false));

            $this->questions[ $row[ 'uuid' ] ] = $question;
        }

        $res->free();

        return array_intersect_key(
            $this->questions,
            array_flip($uuidList)
        );
    }

    private function _orderFouo(string $uuidListString): array
    {
        if (empty($uuidListString)) {
            return [];
        }

        $qry = <<<SQL
SELECT
  questionData.uuid AS uuid,
  afscList.fouo AS fouo
FROM questionData
LEFT JOIN afscList ON afscList.uuid = questionData.afscUUID
WHERE questionData.uuid IN ('{$uuidListString}')
ORDER BY afscList.fouo
SQL;

        $res = $this->db->query($qry);

        $uuidFouo = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || !isset($row[ 'fouo' ])) {
                continue;
            }

            $uuidFouo[ $row[ 'uuid' ] ?? '' ] = (bool)($row[ 'fouo' ] ?? false);
        }

        $res->free();

        return $uuidFouo;
    }

    private function _fetchMixedEncrypted(array $uuidList): void
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
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === null) {
                continue;
            }

            $question = new Question();
            $question->setUuid($row[ 'uuid' ] ?? '');
            $question->setAfscUuid($row[ 'afscUUID' ] ?? '');
            $question->setText($row[ 'questionText' ] ?? '');
            $question->setDisabled((bool)($row[ 'disabled' ] ?? false));

            $this->questions[ $row[ 'uuid' ] ] = $question;
        }

        $res->free();
    }

    private function _fetchMixedUnencrypted(array $uuidList): void
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
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === null) {
                continue;
            }

            $question = new Question();
            $question->setUuid($row[ 'uuid' ] ?? '');
            $question->setAfscUuid($row[ 'afscUUID' ] ?? '');
            $question->setText($row[ 'questionText' ] ?? '');
            $question->setDisabled((bool)($row[ 'disabled' ] ?? false));

            $this->questions[ $row[ 'uuid' ] ] = $question;
        }

        $res->free();
    }

    public function fetchArrayMixed(array $uuidList): array
    {
        if (empty($uuidList)) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $orderFouo = $this->_orderFouo($uuidListString);

        $uuidEncrypted = [];
        $uuidUnencrypted = [];

        foreach ($orderFouo as $uuid => $isFouo) {
            if ($isFouo) {
                $uuidEncrypted[] = $uuid;
                continue;
            }

            $uuidUnencrypted[] = $uuid;
        }

        $this->_fetchMixedEncrypted($uuidEncrypted);
        $this->_fetchMixedUnencrypted($uuidUnencrypted);

        return array_intersect_key(
            $this->questions,
            array_flip(
                $uuidList
            )
        );
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

        $uuid = $question->getUuid();
        $afscUuid = $question->getAfscUuid();
        $text = $question->getText();
        $disabled = (int)$question->isDisabled();

        $qry = <<<SQL
INSERT INTO questionData
  (uuid, afscUUID, questionText, disabled)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  afscUUID=VALUES(afscUUID),
  questionText=VALUES(questionText),
  disabled=VALUES(disabled)
SQL;

        if ($afsc->isFouo()) {
            $qry = <<<SQL
INSERT INTO questionData
  (uuid, afscUUID, questionText, disabled)
VALUES (?, ?, AES_ENCRYPT(?, '%s'), ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  afscUUID=VALUES(afscUUID),
  questionText=VALUES(questionText),
  disabled=VALUES(disabled)
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sssi',
            $uuid,
            $afscUuid,
            $text,
            $disabled
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
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

        foreach ($questions as $question) {
            if (!$question instanceof Question) {
                continue;
            }

            $this->save($afsc, $question);
        }
    }
}