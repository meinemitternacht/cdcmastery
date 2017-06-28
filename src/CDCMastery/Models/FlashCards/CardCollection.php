<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 12:40 PM
 */

namespace CDCMastery\Models\FlashCards;


use Monolog\Logger;

class CardCollection
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
     * @var Card[]
     */
    private $cards = [];

    /**
     * CardCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param Category $category
     * @param string $uuid
     * @return Card
     */
    public function fetch(Category $category, string $uuid): Card
    {
        if (empty($category->getUuid()) || empty($uuid)) {
            return new Card();
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
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Card();
        }

        $stmt->bind_result(
            $_uuid,
            $front,
            $back,
            $_category
        );

        $stmt->fetch();
        $stmt->close();

        $card = new Card();
        $card->setUuid($_uuid);
        $card->setFront($front);
        $card->setBack($back);
        $card->setCategory($_category);

        $this->cards[$uuid] = $card;

        return $card;
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

        $uuid = $category->getUuid();

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $front,
            $back,
            $_category
        );

        $uuidList = [];
        while ($stmt->fetch()) {
            if (is_null($_uuid)) {
                continue;
            }

            $card = new Card();
            $card->setUuid($_uuid);
            $card->setFront($front);
            $card->setBack($back);
            $card->setCategory($_category);

            $this->cards[$uuid] = $card;
            $uuidList[] = $_uuid;
        }

        $stmt->close();

        return array_intersect_key(
            $this->cards,
            array_flip($uuidList)
        );
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
ORDER BY uuid ASC
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
ORDER BY uuid ASC
SQL;

            $qry = sprintf(
                $qry,
                ENCRYPTION_KEY,
                ENCRYPTION_KEY
            );
        }

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $card = new Card();
            $card->setUuid($row['uuid'] ?? '');
            $card->setFront($row['frontText'] ?? '');
            $card->setBack($row['backText'] ?? '');
            $card->setCategory($row['cardCategory'] ?? '');

            $this->cards[$row['uuid']] = $card;
        }

        $res->free();

        return array_intersect_key(
            $this->cards,
            array_flip($uuidList)
        );
    }

    /**
     * @return CardCollection
     */
    public function reset(): self
    {
        $this->cards = [];

        return $this;
    }

    /**
     * @param Category $category
     * @param Card $card
     */
    public function save(Category $category, Card $card): void
    {
        if (empty($category->getUuid()) || empty($card->getUuid())) {
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
        $stmt->bind_param(
            'sss',
            $uuid,
            $front,
            $back,
            $_category
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->cards[$uuid] = $card;
    }

    /**
     * @param Category $category
     * @param Card[] $cards
     */
    public function saveArray(Category $category, array $cards): void
    {
        if (empty($category->getUuid()) || empty($cards)) {
            return;
        }

        $c = count($cards);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($cards[$i])) {
                continue;
            }

            if (!$cards[$i] instanceof Card) {
                continue;
            }

            $this->save($category, $cards[$i]);
        }
    }
}