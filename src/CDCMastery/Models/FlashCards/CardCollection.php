<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 12:40 PM
 */

namespace CDCMastery\Models\FlashCards;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class CardCollection
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * CardCollection constructor.
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
     * @return Card[]
     */
    private function create_objects(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $card = new Card();
            $card->setUuid($row[ 'uuid' ]);
            $card->setFront($row[ 'frontText' ]);
            $card->setBack($row[ 'backText' ]);
            $card->setCategory($row[ 'cardCategory' ]);

            $out[ $row[ 'uuid' ] ] = $card;
        }

        return $out;
    }

    public function delete(Card $card): void
    {
        $card_uuid = $card->getUuid();
        $qry = <<<SQL
DELETE FROM flashCardData WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $card_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    public function fetch(Category $category, string $uuid): ?Card
    {
        if (!$uuid || !$category->getUuid()) {
            return null;
        }

        $qry = <<<SQL
SELECT 
  uuid,
  frontText,
  backText,
  cardCategory
FROM flashCardData
WHERE uuid = ?
SQL;

        if ($category->isEncrypted()) {
            $qry = <<<SQL
SELECT 
  uuid,
  AES_DECRYPT(frontText, '%s') as frontText,
  AES_DECRYPT(backText, '%s') as backText,
  cardCategory
FROM flashCardData
WHERE uuid = ?
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY,
                ENCRYPTION_KEY
            );
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
            $front,
            $back,
            $_category
        );

        $stmt->fetch();
        $stmt->close();

        $row = [
            'uuid' => $_uuid,
            'frontText' => $front,
            'backText' => $back,
            'cardCategory' => $_category,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    /**
     * @param Category $category
     * @return Card[]
     */
    public function fetchCategory(Category $category): array
    {
        if (empty($category->getUuid())) {
            return [];
        }

        $qry = <<<SQL
SELECT 
  uuid,
  frontText,
  backText,
  cardCategory
FROM flashCardData
WHERE cardCategory = ?
ORDER BY frontText
SQL;

        if ($category->isEncrypted()) {
            $qry = <<<SQL
SELECT 
  uuid,
  AES_DECRYPT(frontText, '%s') as frontText,
  AES_DECRYPT(backText, '%s') as backText,
  cardCategory
FROM flashCardData
WHERE cardCategory = ?
ORDER BY frontText
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY,
                ENCRYPTION_KEY
            );
        }

        $uuid = $category->getUuid();

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
            $front,
            $back,
            $_category
        );

        $rows = [];
        while ($stmt->fetch()) {
            if ($_uuid === null) {
                continue;
            }

            $rows[] = [
                'uuid' => $_uuid,
                'frontText' => $front,
                'backText' => $back,
                'cardCategory' => $_category,
            ];
        }

        $stmt->close();
        return $this->create_objects($rows);
    }

    /**
     * @param Category $category
     * @param string[] $uuidList
     * @return Card[]
     */
    public function fetchArray(Category $category, array $uuidList): array
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
  frontText,
  backText,
  cardCategory
FROM flashCardData
WHERE uuid IN ('{$uuidListString}')
ORDER BY frontText
SQL;

        if ($category->isEncrypted()) {
            $qry = <<<SQL
SELECT 
  uuid,
  AES_DECRYPT(frontText, '%s') as frontText,
  AES_DECRYPT(backText, '%s') as backText,
  cardCategory
FROM flashCardData
WHERE uuid IN ('{$uuidListString}')
ORDER BY frontText
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY,
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
     * @param Category $category
     * @param Card $card
     */
    public function save(Category $category, Card $card): void
    {
        if (!$category->getUuid() || !$card->getUuid()) {
            return;
        }

        $uuid = $card->getUuid();
        $front = $card->getFront();
        $back = $card->getBack();
        $_category = $card->getCategory();

        $qry = <<<SQL
INSERT INTO flashCardData
  (uuid, frontText, backText, cardCategory)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  frontText=VALUES(frontText),
  backText=VALUES(backText),
  cardCategory=VALUES(cardCategory)
SQL;

        if ($category->isEncrypted()) {
            $qry = <<<SQL
INSERT INTO flashCardData
  (uuid, frontText, backText, cardCategory)
VALUES (?, AES_ENCRYPT(?, '%s'), AES_ENCRYPT(?, '%s'), ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  frontText=VALUES(frontText),
  backText=VALUES(backText),
  cardCategory=VALUES(cardCategory)
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY,
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
                               $front,
                               $back,
                               $_category) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Category $category
     * @param Card[] $cards
     */
    public function saveArray(Category $category, array $cards): void
    {
        if (!$cards || !$category->getUuid()) {
            return;
        }

        foreach ($cards as $card) {
            if (!$card instanceof Card) {
                continue;
            }

            $this->save($category, $card);
        }
    }
}
