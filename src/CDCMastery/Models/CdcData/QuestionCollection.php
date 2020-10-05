<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 10:12 PM
 */

namespace CDCMastery\Models\CdcData;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class QuestionCollection
{
    protected mysqli $db;
    protected Logger $log;

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

    /**
     * @param array $rows
     * @return Question[]
     */
    private function create_objects(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $question = new Question();
            $question->setUuid($row[ 'uuid' ]);
            $question->setAfscUuid($row[ 'afscUUID' ]);
            $question->setText($row[ 'questionText' ]);
            $question->setDisabled((bool)($row[ 'disabled' ] ?? false));

            $out[ $row[ 'uuid' ] ] = $question;
        }

        return $out;
    }

    public function delete(string $uuid): void
    {
        if (!$uuid) {
            return;
        }

        $qry = <<<SQL
DELETE FROM questionData
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

        $qry = <<<SQL
DELETE FROM answerData
WHERE questionUUID = ?
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

    public function fetch(Afsc $afsc, string $uuid): ?Question
    {
        if (!$uuid || !$afsc->getUuid()) {
            return null;
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('ss', $uuid, $afsc_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text,
            $disabled
        );

        $stmt->fetch();
        $stmt->close();

        $row = [
            'uuid' => $uuid,
            'afscUUID' => $afscUuid,
            'questionText' => $text,
            'disabled' => $disabled,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    /**
     * @param Afsc $afsc
     * @return Question[]
     */
    public function fetchAfsc(Afsc $afsc): array
    {
        if (!$afsc->getUuid()) {
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
  AES_DECRYPT(questionText, '%s') AS questionText,
  disabled
FROM questionData
WHERE afscUUID = ?
ORDER BY questionText
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY
            );
        }

        $uuid = $afsc->getUuid();

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $text,
            $disabled
        );

        $rows = [];
        while ($stmt->fetch()) {
            if ($_uuid === null) {
                continue;
            }

            $rows[] = [
                'uuid' => $_uuid,
                'afscUUID' => $afscUuid,
                'questionText' => $text,
                'disabled' => $disabled,
            ];
        }

        $stmt->close();

        return $this->create_objects($rows);
    }

    /**
     * @param Afsc $afsc
     * @param string[] $uuids
     * @return Question[]
     */
    public function fetchArray(Afsc $afsc, array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids = array_map(
            [$this->db, 'real_escape_string'],
            $uuids
        );

        $uuids_str = implode("','", $uuids);

        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuids_str}')
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

    private function _orderFouo(string $uuids_str): array
    {
        if (!$uuids_str) {
            return [];
        }

        $qry = <<<SQL
SELECT
  questionData.uuid AS uuid,
  afscList.fouo AS fouo
FROM questionData
LEFT JOIN afscList ON afscList.uuid = questionData.afscUUID
WHERE questionData.uuid IN ('{$uuids_str}')
ORDER BY afscList.fouo
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $uuidFouo = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ], $row[ 'fouo' ])) {
                continue;
            }

            $uuidFouo[ $row[ 'uuid' ] ?? '' ] = (bool)($row[ 'fouo' ] ?? false);
        }

        $res->free();

        return $uuidFouo;
    }

    private function _fetchMixedEncrypted(array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids = array_map(
            [$this->db, 'real_escape_string'],
            $uuids
        );

        $uuids_str = implode("','", $uuids);

        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  AES_DECRYPT(questionText, '%s') as questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

        $qry = sprintf(
            $qry,
            ENCRYPTION_KEY
        );

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

    private function _fetchMixedUnencrypted(array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids = array_map(
            [$this->db, 'real_escape_string'],
            $uuids
        );

        $uuids_str = implode("','", $uuids);

        $qry = <<<SQL
SELECT
  uuid,
  afscUUID,
  questionText,
  disabled
FROM questionData
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

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

        $out = [
            $this->_fetchMixedEncrypted($uuidEncrypted),
            $this->_fetchMixedUnencrypted($uuidUnencrypted),
        ];

        return $out
            ? array_merge(...$out)
            : [];
    }

    /**
     * @param Afsc $afsc
     * @param Question $question
     */
    public function save(Afsc $afsc, Question $question): void
    {
        if (!$afsc->getUuid() || !$question->getUuid()) {
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('sssi',
                               $uuid,
                               $afscUuid,
                               $text,
                               $disabled) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
        if (!$questions || !$afsc->getUuid()) {
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