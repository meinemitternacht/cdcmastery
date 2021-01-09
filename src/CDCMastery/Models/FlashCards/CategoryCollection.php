<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 1:54 PM
 */

namespace CDCMastery\Models\FlashCards;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Sorting\Cards\CardCategorySortOption;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class CategoryCollection
{
    private const CHUNK_LEN = 512;

    protected mysqli $db;
    protected Logger $log;
    protected Config $cfg;

    public function __construct(mysqli $mysqli, Logger $logger, Config $cfg)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cfg = $cfg;
    }

    public function count(): int
    {
        $qry = <<<SQL
SELECT COUNT(*) AS count FROM flashCardCategories
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
    }

    public function countUser(User $user): int
    {
        $user_uuid = $user->getUuid();

        $qry = <<<SQL
# noinspection SqlResolve

SELECT 
  COUNT(*) AS count
FROM flashCardCategories
WHERE (categoryType = 'private' AND categoryCreatedBy = ?)
  OR categoryType = 'global'
  OR (
      categoryType = 'afsc'
      AND
      categoryBinding IN (
          SELECT afscUUID FROM userAFSCAssociations
          WHERE userAFSCAssociations.userUUID = ?
      )
  )
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        if (!$stmt->bind_param('ss', $user_uuid, $user_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)($count ?? 0);
    }

    /**
     * @param array $rows
     * @return Category[]
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

            $cat = new Category();
            $cat->setUuid($row[ 'uuid' ]);
            $cat->setName($row[ 'categoryName' ]);
            $cat->setEncrypted((bool)$row[ 'categoryEncrypted' ]);
            $cat->setType($row[ 'categoryType' ]);
            $cat->setBinding($row[ 'categoryBinding' ]);
            $cat->setCreatedBy($row[ 'categoryCreatedBy' ]);
            $cat->setComments($row[ 'categoryComments' ]);

            $out[ $row[ 'uuid' ] ] = $cat;
        }

        return $out;
    }

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

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    public function deleteArray(array $uuids, int $chunk_len = self::CHUNK_LEN): void
    {
        if (!$uuids) {
            return;
        }

        $uuidsFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuids
        );

        foreach (array_chunk($uuidsFiltered, $chunk_len) as $filtered_chunk) {
            $uuidsString = implode("','", $filtered_chunk);

            $qry = <<<SQL
DELETE FROM flashCardCategories
WHERE uuid IN ('{$uuidsString}')
SQL;

            $res = $this->db->query($qry);

            if ($res === false) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            }
        }
    }

    public function fetch(string $uuid): ?Category
    {
        if (!$uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT 
  uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            return null;
        }

        $stmt->bind_result(
            $_uuid,
            $name,
            $encrypted,
            $type,
            $binding,
            $createdBy,
            $comments
        );

        $stmt->fetch();
        $stmt->close();

        $row = [
            'uuid' => $_uuid,
            'categoryName' => $name,
            'categoryEncrypted' => $encrypted,
            'categoryType' => $type,
            'categoryBinding' => $binding,
            'categoryCreatedBy' => $createdBy,
            'categoryComments' => $comments,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    public function fetchAfsc(Afsc $afsc): ?Category
    {
        $afsc_uuid = $afsc->getUuid();

        if (!$afsc_uuid) {
            return null;
        }

        $type_afsc = Category::TYPE_AFSC;
        $qry = <<<SQL
SELECT 
  uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
WHERE categoryType = '{$type_afsc}'
    AND categoryBinding = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $afsc_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result(
            $_uuid,
            $name,
            $encrypted,
            $type,
            $binding,
            $createdBy,
            $comments
        );

        $stmt->fetch();
        $stmt->close();

        if (!$_uuid) {
            return null;
        }

        $row = [
            'uuid' => $_uuid,
            'categoryName' => $name,
            'categoryEncrypted' => $encrypted,
            'categoryType' => $type,
            'categoryBinding' => $binding,
            'categoryCreatedBy' => $createdBy,
            'categoryComments' => $comments,
        ];

        return $this->create_objects([$row])[ $_uuid ] ?? null;
    }

    /**
     * @param array|null $sort_options
     * @param int|null $start
     * @param int|null $limit
     * @param string|null $tgt_type
     * @return Category[]
     */
    public function fetchAll(
        ?array $sort_options = null,
        ?int $start = null,
        ?int $limit = null,
        ?string $tgt_type = null
    ): array {
        if (!$sort_options) {
            $sort_options = [new CardCategorySortOption(CardCategorySortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
            if (!$sort_option) {
                continue;
            }

            $join_str_tmp = $sort_option->getJoinClause();

            if ($join_str_tmp) {
                $join_strs[] = $join_str_tmp;
                $sort_strs[] = "{$sort_option->getJoinTgtSortColumn()} {$sort_option->getDirection()}";
                continue;
            }

            $sort_strs[] = "`flashCardCategories`.`{$sort_option->getColumn()}` {$sort_option->getDirection()}";
        }

        $join_str = $join_strs
            ? implode("\n", $join_strs)
            : null;
        $sort_str = ' ORDER BY ' . implode(', ', $sort_strs);

        $limit_str = null;
        if ($start !== null && $limit !== null) {
            $limit_str = "LIMIT {$start}, {$limit}";
        }

        $where_str = null;
        if ($tgt_type) {
            $where_str = "WHERE categoryType = '{$this->db->real_escape_string($tgt_type)}'";
        }

        $qry = <<<SQL
# noinspection SqlResolve

SELECT 
  flashCardCategories.uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
{$join_str}
{$where_str}
{$sort_str}
{$limit_str}
SQL;

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
     * @param User $user
     * @param array|null $sort_options
     * @param int|null $start
     * @param int|null $limit
     * @return Category[]
     */
    public function fetchAllByUser(
        User $user,
        ?array $sort_options = null,
        ?int $start = null,
        ?int $limit = null
    ): array {
        if (!$sort_options) {
            $sort_options = [new CardCategorySortOption(CardCategorySortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
            if (!$sort_option) {
                continue;
            }

            $join_str_tmp = $sort_option->getJoinClause();

            if ($join_str_tmp) {
                $join_strs[] = $join_str_tmp;
                $sort_strs[] = "{$sort_option->getJoinTgtSortColumn()} {$sort_option->getDirection()}";
                continue;
            }

            $sort_strs[] = "`flashCardCategories`.`{$sort_option->getColumn()}` {$sort_option->getDirection()}";
        }

        $join_str = $join_strs
            ? implode("\n", $join_strs)
            : null;
        $sort_str = ' ORDER BY ' . implode(', ', $sort_strs);

        $limit_str = null;
        if ($start !== null && $limit !== null) {
            $limit_str = "LIMIT {$start}, {$limit}";
        }

        $qry = <<<SQL
# noinspection SqlResolve

SELECT 
  flashCardCategories.uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
{$join_str}
WHERE (categoryType = 'private' AND categoryCreatedBy = ?)
      OR (categoryType = 'global' AND CategoryBinding IS NULL)
      OR (
          categoryType = 'global'
          AND
          categoryBinding IN (
              SELECT afscUUID FROM userAFSCAssociations
              WHERE userAFSCAssociations.userUUID = ?
                AND userAFSCAssociations.userAuthorized = 1
          )
      )
      OR (
          categoryType = 'afsc'
          AND
          categoryBinding IN (
              SELECT afscUUID FROM userAFSCAssociations
              WHERE userAFSCAssociations.userUUID = ?
                AND userAFSCAssociations.userAuthorized = 1
          )
      )
{$sort_str}
{$limit_str}
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $user_uuid = $user->getUuid();
        if (!$stmt->bind_param('sss', $user_uuid, $user_uuid, $user_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $name,
            $encrypted,
            $type,
            $binding,
            $createdBy,
            $comments
        );

        $rows = [];
        while ($stmt->fetch()) {
            $rows[] = [
                'uuid' => $_uuid,
                'categoryName' => $name,
                'categoryEncrypted' => $encrypted,
                'categoryType' => $type,
                'categoryBinding' => $binding,
                'categoryCreatedBy' => $createdBy,
                'categoryComments' => $comments,
            ];
        }

        $stmt->close();
        return $this->create_objects($rows);
    }

    /**
     * @param string[] $uuidList
     * @param ISortOption[]|null $sort_options
     * @return Category[]
     */
    public function fetchArray(array $uuidList, ?array $sort_options = null): array
    {
        if (!$uuidList) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        if (!$sort_options) {
            $sort_options = [new CardCategorySortOption(CardCategorySortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
            if (!$sort_option) {
                continue;
            }

            $join_str_tmp = $sort_option->getJoinClause();

            if ($join_str_tmp) {
                $join_strs[] = $join_str_tmp;
                $sort_strs[] = "{$sort_option->getJoinTgtSortColumn()} {$sort_option->getDirection()}";
                continue;
            }

            $sort_strs[] = "`flashCardCategories`.`{$sort_option->getColumn()}` {$sort_option->getDirection()}";
        }

        $join_str = $join_strs
            ? implode("\n", $join_strs)
            : null;
        $sort_str = ' ORDER BY ' . implode(', ', $sort_strs);

        $qry = <<<SQL
# noinspection SqlResolve

SELECT 
  uuid,
  categoryName,
  categoryEncrypted,
  categoryType,
  categoryBinding,
  categoryCreatedBy,
  categoryComments
FROM flashCardCategories
{$join_str}
WHERE uuid IN ('{$uuidListString}')
{$sort_str}
SQL;

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
     * @return Category[]
     *  Delete private flash card categories that are empty, or those that have not been used in a few years
     */
    public function fetchExpired(): array
    {
        $cat_type = Category::TYPE_PRIVATE;

        $dt_empty = new DateTime();
        $dt_populated = clone $dt_empty;

        $dt_empty = $dt_empty->modify($this->cfg->get(['cards', 'retention', 'empty']))
                             ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $dt_populated = $dt_populated->modify($this->cfg->get(['cards', 'retention', 'populated']))
                                     ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);

        /* empty categories */
        $qry = <<<SQL
SELECT
  flashCardCategories.uuid
FROM
  flashCardCategories
LEFT JOIN
  userData uD on flashCardCategories.categoryCreatedBy = uD.uuid
WHERE flashCardCategories.uuid NOT IN
  (
    SELECT DISTINCT(uuid) FROM flashCardData
  )
  AND categoryType = '{$cat_type}'
  AND userLastLogin < '{$dt_empty}';
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $uuids = [];
        if (!$res->num_rows) {
            goto skip_empty;
        }

        while ($row = $res->fetch_assoc()) {
            $uuids[] = $row[ 'uuid' ];
        }

        $res->close();

        skip_empty:
        /* populated categories */
        $qry = <<<SQL
SELECT
  flashCardCategories.uuid
FROM
  flashCardCategories
LEFT JOIN
  userData uD on flashCardCategories.categoryCreatedBy = uD.uuid
WHERE userLastLogin < '{$dt_populated}'
  AND categoryType = '{$cat_type}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$res->num_rows) {
            return [];
        }

        while ($row = $res->fetch_assoc()) {
            $uuids[] = $row[ 'uuid' ];
        }

        $res->close();

        if (!$uuids) {
            return [];
        }

        return $this->fetchArray(array_unique($uuids));
    }

    public function filterAfsc(?array $sort_options = null, ?int $start = null, ?int $limit = null): array
    {
        return $this->fetchAll($sort_options, $start, $limit, Category::TYPE_AFSC);
    }

    public function filterGlobal(?array $sort_options = null, ?int $start = null, ?int $limit = null): array
    {
        return $this->fetchAll($sort_options, $start, $limit, Category::TYPE_GLOBAL);
    }

    public function filterPrivate(?array $sort_options = null, ?int $start = null, ?int $limit = null): array
    {
        return $this->fetchAll($sort_options, $start, $limit, Category::TYPE_PRIVATE);
    }

    /**
     * @param Category $category
     */
    public function save(Category $category): void
    {
        if (!$category->getUuid()) {
            return;
        }

        $uuid = $category->getUuid();
        $name = $category->getName();
        $encrypted = $category->isEncrypted();
        $type = $category->getType();
        $binding = $category->getBinding();
        $createdBy = $category->getCreatedBy();
        $comments = $category->getComments();

        $qry = <<<SQL
INSERT INTO flashCardCategories
  (uuid, categoryName, categoryEncrypted, categoryType, categoryBinding, categoryCreatedBy, categoryComments)
VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  categoryName=VALUES(categoryName),
  categoryEncrypted=VALUES(categoryEncrypted),
  categoryType=VALUES(categoryType),
  categoryBinding=VALUES(categoryBinding),
  categoryCreatedBy=VALUES(categoryCreatedBy),
  categoryComments=VALUES(categoryComments)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssissss',
                               $uuid,
                               $name,
                               $encrypted,
                               $type,
                               $binding,
                               $createdBy,
                               $comments) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
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
