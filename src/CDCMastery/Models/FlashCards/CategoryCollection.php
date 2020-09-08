<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 1:54 PM
 */

namespace CDCMastery\Models\FlashCards;


use Monolog\Logger;
use mysqli;

class CategoryCollection
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
     * @var Category[]
     */
    private $categories = [];

    /**
     * CategoryCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $uuid
     */
    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM flashCardCategories
WHERE uuid = '{$uuid}'
SQL;

        $this->db->query($qry);

        if (isset($this->categories[$uuid])) {
            array_splice(
                $this->categories,
                array_search(
                    $uuid,
                    $this->categories
                ),
                1
            );
        }
    }

    /**
     * @param string $uuid
     * @return Category
     */
    public function fetch(string $uuid): Category
    {
        if (empty($uuid)) {
            return new Category();
        }

        $qry = <<<SQL
SELECT 
  uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryPrivate,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Category();
        }

        $stmt->bind_result(
            $_uuid,
            $name,
            $encrypted,
            $type,
            $binding,
            $private,
            $createdBy,
            $comments
        );

        $stmt->fetch();
        $stmt->close();

        $category = new Category();
        $category->setUuid($_uuid);
        $category->setName($name);
        $category->setEncrypted($encrypted);
        $category->setType($type);
        $category->setBinding($binding);
        $category->setPrivate((bool)$private);
        $category->setCreatedBy($createdBy);
        $category->setComments($comments);

        $this->categories[$uuid] = $category;

        return $category;
    }

    /**
     * @return Category[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT 
  uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryPrivate,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $category = new Category();
            $category->setUuid($row['uuid'] ?? '');
            $category->setName($row['categoryName'] ?? '');
            $category->setEncrypted((bool)($row['categoryEncrypted'] ?? false));
            $category->setType($row['categoryType'] ?? '');
            $category->setBinding($row['categoryBinding'] ?? '');
            $category->setPrivate((bool)($row['categoryPrivate'] ?? ''));
            $category->setCreatedBy($row['categoryCreatedBy'] ?? '');
            $category->setComments($row['categoryComments'] ?? '');

            $this->categories[$row['uuid']] = $category;
        }

        $res->free();

        return $this->categories;
    }

    /**
     * @param string[] $uuidList
     * @return Category[]
     */
    public function fetchArray(array $uuidList): array
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
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryPrivate,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $category = new Category();
            $category->setUuid($row['uuid'] ?? '');
            $category->setName($row['categoryName'] ?? '');
            $category->setEncrypted((bool)($row['categoryEncrypted'] ?? false));
            $category->setType($row['categoryType'] ?? '');
            $category->setBinding($row['categoryBinding'] ?? '');
            $category->setPrivate((bool)($row['categoryPrivate'] ?? ''));
            $category->setCreatedBy($row['categoryCreatedBy'] ?? '');
            $category->setComments($row['categoryComments'] ?? '');

            $this->categories[$row['uuid']] = $category;
        }

        $res->free();

        return $this->categories;
    }

    /**
     * @return CategoryCollection
     */
    public function reset(): self
    {
        $this->categories = [];

        return $this;
    }

    /**
     * @param Category $category
     */
    public function save(Category $category): void
    {
        if (empty($category->getUuid())) {
            return;
        }

        $uuid = $category->getUuid();
        $name = $category->getName();
        $encrypted = $category->isEncrypted();
        $type = $category->getType();
        $binding = $category->getBinding();
        $private = $category->isPrivate();
        $createdBy = $category->getCreatedBy();
        $comments = $category->getComments();

        $qry = <<<SQL
INSERT INTO flashCardCategories
  (uuid, categoryName, categoryEncrypted, categoryType, categoryBinding, categoryPrivate, categoryCreatedBy, categoryComments)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  categoryName=VALUES(categoryName),
  categoryEncrypted=VALUES(categoryEncrypted),
  categoryType=VALUES(categoryType),
  categoryBinding=VALUES(categoryBinding),
  categoryPrivate=VALUES(categoryPrivate),
  categoryCreatedBy=VALUES(categoryCreatedBy),
  categoryComments=VALUES(categoryComments)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssississ',
            $uuid,
            $name,
            $encrypted,
            $type,
            $binding,
            $private,
            $createdBy,
            $comments
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->categories[$uuid] = $category;
    }

    /**
     * @param Category[] $categories
     */
    public function saveArray(array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        foreach ($categories as $category) {
            if (!$category instanceof Category) {
                continue;
            }

            $this->save($category);
        }
    }
}