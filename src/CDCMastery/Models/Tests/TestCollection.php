<?php
declare(strict_types=1);

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;
use RuntimeException;

class TestCollection
{
    private const Q_SEARCH_CHUNK_SIZE = 100; /* find test IDs using this many questions at a time */
    public const TABLE_NAME = 'testCollection';

    public const COL_AFSC_LIST = 'afscList';
    public const COL_TIME_STARTED = 'timeStarted';
    public const COL_TIME_COMPLETED = 'timeCompleted';
    public const COL_LAST_UPDATED = 'lastUpdated';
    public const COL_CUR_QUESTION = 'curQuestion';
    public const COL_NUM_ANSWERED = 'numAnswered';
    public const COL_NUM_MISSED = 'numMissed';
    public const COL_SCORE = 'score';
    public const COL_IS_ARCHIVED = 'archived';

    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public const DEFAULT_COL = self::COL_TIME_STARTED;
    public const DEFAULT_ORDER = self::ORDER_DESC;

    private mysqli $db;
    private Logger $log;
    private AfscCollection $afscs;
    private QuestionCollection $questions;

    public function __construct(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        QuestionCollection $questions
    ) {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->afscs = $afscs;
        $this->questions = $questions;
    }

    /**
     * @param array $columnOrders
     * @return string
     */
    private static function generateOrderSuffix(array $columnOrders): string
    {
        if (!$columnOrders) {
            return self::generateOrderSuffix([self::DEFAULT_COL => self::DEFAULT_ORDER]);
        }

        $sql = [];
        foreach ($columnOrders as $column => $order) {
            switch ($column) {
                case self::COL_AFSC_LIST:
                case self::COL_TIME_STARTED:
                case self::COL_TIME_COMPLETED:
                case self::COL_LAST_UPDATED:
                case self::COL_CUR_QUESTION:
                case self::COL_NUM_ANSWERED:
                case self::COL_NUM_MISSED:
                case self::COL_SCORE:
                case self::COL_IS_ARCHIVED:
                    $str = self::TABLE_NAME . '.' . $column;
                    break;
                default:
                    continue 2;
            }

            switch ($order) {
                case self::ORDER_ASC:
                    $str .= ' ASC';
                    break;
                case self::ORDER_DESC:
                default:
                    $str .= ' DESC';
                    break;
            }

            $sql[] = $str;
        }

        return ' ORDER BY ' . implode(' , ', $sql);
    }

    /**
     * @param int $type
     * @return int
     */
    public function countAll(int $type): int
    {
        switch ($type) {
            case Test::STATE_COMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE timeCompleted IS NOT NULL
SQL;
                break;
            case Test::STATE_INCOMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE timeCompleted IS NULL
SQL;
                break;
            default:
                throw new RuntimeException("invalid test type: {$type}");
        }

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
    }

    /**
     * @param int $type
     * @param Base $base
     * @return int
     */
    public function countAllByBase(int $type, Base $base): int
    {
        switch ($type) {
            case Test::STATE_COMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
LEFT JOIN userData uD on testCollection.userUuid = uD.uuid
WHERE timeCompleted IS NOT NULL
  AND uD.userBase = '{$base->getUuid()}'
SQL;
                break;
            case Test::STATE_INCOMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
LEFT JOIN userData uD on testCollection.userUuid = uD.uuid
WHERE timeCompleted IS NULL
  AND uD.userBase = '{$base->getUuid()}'
SQL;
                break;
            default:
                throw new RuntimeException("invalid test type: {$type}");
        }

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
    }

    /**
     * @param array $rows
     * @return Test[]
     * @noinspection SlowArrayOperationsInLoopInspection
     */
    private function create_objects(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $afsc_cache = [];
        $out = [];
        foreach ($rows as $row) {
            $afscs = unserialize($row[ 'afscList' ] ?? '');

            if (!is_array($afscs)) {
                $afscs = [];
            }

            $questions = unserialize($row[ 'questionList' ] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $afscs_flipped = array_flip($afscs);

            $afscs_fetch = array_diff_key($afscs_flipped, $afsc_cache);

            if ($afscs_fetch) {
                $afsc_cache = array_merge($afsc_cache, $this->afscs->fetchArray(array_flip($afscs_fetch)));
            }

            $tgt_afscs = array_intersect_key($afsc_cache, $afscs_flipped);

            $questions = array_replace(array_flip($questions),
                                       $this->questions->fetchArrayMixed($questions));

            $timeStarted = null;
            if ($row[ 'timeStarted' ] !== null) {
                $timeStarted = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'timeStarted' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $timeStarted->setTimezone(DateTimeHelpers::user_tz());
            }

            $timeCompleted = null;
            if ($row[ 'timeCompleted' ] !== null) {
                $timeCompleted = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'timeCompleted' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $timeCompleted->setTimezone(DateTimeHelpers::user_tz());
            }

            $lastUpdated = null;
            if ($row[ 'lastUpdated' ] !== null) {
                $lastUpdated = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'lastUpdated' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $lastUpdated->setTimezone(DateTimeHelpers::user_tz());
            }

            $test = new Test();
            $test->setUuid($row[ 'uuid' ]);
            $test->setUserUuid($row[ 'userUuid' ]);
            $test->setAfscs($tgt_afscs);
            $test->setTimeStarted($timeStarted ?? null);
            $test->setTimeCompleted($timeCompleted ?? null);
            $test->setLastUpdated($lastUpdated ?? null);
            $test->setQuestions($questions);
            $test->setCurrentQuestion((int)($row[ 'curQuestion' ] ?? 0));
            $test->setNumAnswered((int)($row[ 'numAnswered' ] ?? 0));
            $test->setNumMissed((int)($row[ 'numMissed' ] ?? 0));
            $test->setScore((float)($row[ 'score' ] ?? 0.00));
            $test->setArchived((bool)($row[ 'archived' ] ?? false));
            $test->setType((int)($row[ 'testType' ] ?? Test::TYPE_NORMAL));
            $out[ $row[ 'uuid' ] ] = $test;
        }

        return $out;
    }

    public function countArchivable(): int
    {
        $tgt_date = (new DateTime())->modify(XML_ARCHIVE_CUTOFF)->format(DateTimeHelpers::D_FMT_SHORT);

        $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE archived = 0
  AND timeCompleted IS NOT NULL
  AND DATE(timeCompleted) < '{$tgt_date}'
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

    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM testCollection
WHERE uuid = '{$uuid}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    /**
     * @param string[] $uuids
     */
    public function deleteArray(array $uuids): void
    {
        if (!$uuids) {
            return;
        }

        $uuids = array_map([$this->db, 'real_escape_string'],
                           $uuids);

        $uuids_str = implode("','", $uuids);

        $qry = <<<SQL
DELETE FROM testCollection
WHERE uuid IN ('{$uuids_str}')
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    public function deleteAllByUser(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $userUuid = $this->db->real_escape_string($userUuid);

        $qry = <<<SQL
DELETE FROM testCollection
WHERE userUuid = '{$userUuid}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    public function fetch(string $uuid): ?Test
    {
        if (!$uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE uuid = ?
SQL;

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
            $userUuid,
            $afscList,
            $timeStarted,
            $timeCompleted,
            $lastUpdated,
            $questionList,
            $curQuestion,
            $numAnswered,
            $numMissed,
            $score,
            $archived,
            $testType
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($_uuid)) {
            return null;
        }

        $row = [
            'uuid' => $_uuid,
            'userUuid' => $userUuid,
            'afscList' => $afscList,
            'timeStarted' => $timeStarted,
            'timeCompleted' => $timeCompleted,
            'lastUpdated' => $lastUpdated,
            'questionList' => $questionList,
            'curQuestion' => $curQuestion,
            'numAnswered' => $numAnswered,
            'numMissed' => $numMissed,
            'score' => $score,
            'archived' => $archived,
            'testType' => $testType,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    /**
     * @param User $user
     * @param array $columnOrders
     * @return array
     */
    public function fetchAllByUser(User $user, array $columnOrders = []): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $uuid = $user->getUuid();

        $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE userUuid = ?
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

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
            $userUuid,
            $afscList,
            $timeStarted,
            $timeCompleted,
            $lastUpdated,
            $questionList,
            $curQuestion,
            $numAnswered,
            $numMissed,
            $score,
            $archived,
            $testType
        );

        $data = [];
        while ($stmt->fetch()) {
            $data[] = [
                'uuid' => $_uuid,
                'userUuid' => $userUuid,
                'afscList' => $afscList,
                'timeStarted' => $timeStarted,
                'timeCompleted' => $timeCompleted,
                'lastUpdated' => $lastUpdated,
                'questionList' => $questionList,
                'curQuestion' => $curQuestion,
                'numAnswered' => $numAnswered,
                'numMissed' => $numMissed,
                'score' => $score,
                'archived' => $archived,
                'testType' => $testType,
            ];
        }

        $stmt->close();

        if (!$data) {
            return [];
        }

        return $this->create_objects($data);
    }

    /**
     * @param int $type
     * @param array $order
     * @param int|null $start
     * @param int|null $limit
     * @return array|Test[]
     */
    public function fetchAll(int $type, array $order = [], ?int $start = null, ?int $limit = null): array
    {
        $limit_str = null;
        if ($start !== null && $limit !== null) {
            $limit_str = "LIMIT {$start}, {$limit}";
        }

        $order_str = self::generateOrderSuffix($order);

        switch ($type) {
            case Test::STATE_COMPLETE:
                $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE timeCompleted IS NOT NULL
{$order_str}
{$limit_str}
SQL;
                break;
            case Test::STATE_INCOMPLETE:
                $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE timeCompleted IS NULL
{$order_str}
{$limit_str}
SQL;
                break;
            default:
                throw new RuntimeException("invalid test type: {$type}");
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

        if (!$rows) {
            return [];
        }

        return $this->create_objects($rows);
    }

    /**
     * @param int $type
     * @param Base $base
     * @param array $order
     * @param int|null $start
     * @param int|null $limit
     * @return array|Test[]
     */
    public function fetchAllByBase(
        int $type,
        Base $base,
        array $order = [],
        ?int $start = null,
        ?int $limit = null
    ): array {
        $limit_str = null;
        if ($start !== null && $limit !== null) {
            $limit_str = "LIMIT {$start}, {$limit}";
        }

        $order_str = self::generateOrderSuffix($order);

        switch ($type) {
            case Test::STATE_COMPLETE:
                $qry = <<<SQL
SELECT
  testCollection.uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
LEFT JOIN userData uD on testCollection.userUuid = uD.uuid
WHERE timeCompleted IS NOT NULL
  AND uD.userBase = '{$base->getUuid()}'
{$order_str}
{$limit_str}
SQL;
                break;
            case Test::STATE_INCOMPLETE:
                $qry = <<<SQL
SELECT
  testCollection.uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
LEFT JOIN userData uD on testCollection.userUuid = uD.uuid
WHERE timeCompleted IS NULL
  AND uD.userBase = '{$base->getUuid()}'
{$order_str}
{$limit_str}
SQL;
                break;
            default:
                throw new RuntimeException("invalid test type: {$type}");
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

        if (!$rows) {
            return [];
        }

        return $this->create_objects($rows);
    }

    /**
     * @param Question[] $questions
     * @return Test[]
     *  Find all tests that used any of the target questions.
     *  This will not perform well, but it is fine for async offline scripts.
     */
    public function fetchAllByQuestions(
        array $questions
    ): array {
        $q_uuids = array_map(static function (Question $v): string { return $v->getUuid(); }, $questions);

        if (!$q_uuids) {
            return [];
        }

        $t_uuids = [];
        foreach (array_chunk($q_uuids, self::Q_SEARCH_CHUNK_SIZE) as $q_uuids_chunk) {
            $q_uuids_str = implode("','", $q_uuids_chunk);

            $qry = <<<SQL
SELECT
  DISTINCT(testUUID)
FROM testData
WHERE questionUUID IN ('{$q_uuids_str}')
SQL;

            $res = $this->db->query($qry);

            if ($res === false) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
                return [];
            }

            while ($row = $res->fetch_assoc()) {
                $t_uuids[] = $row[ 'testUUID' ];
            }

            $res->free();
        }

        if (!$t_uuids) {
            return [];
        }

        return $this->fetchArray(array_unique($t_uuids));
    }

    public function fetchArchivable(int $limit = 1000): array
    {
        $tgt_date = (new DateTime())->modify(XML_ARCHIVE_CUTOFF)->format(DateTimeHelpers::D_FMT_SHORT);

        $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE archived = 0
  AND timeCompleted IS NOT NULL
  AND DATE(timeCompleted) < '{$tgt_date}'
ORDER BY timeCompleted DESC
LIMIT {$limit}
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $data[] = $row;
        }

        $res->free();

        return $this->create_objects($data);
    }

    public function fetchExpiredIncomplete(): array
    {
        $tgt_date = (new DateTime())->modify(INCOMPLETE_TEST_MAX_AGE)->format(DateTimeHelpers::D_FMT_SHORT);

        $qry = <<<SQL
SELECT
  uuid,
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE timeCompleted IS NULL
  AND DATE(lastUpdated) < '{$tgt_date}'
ORDER BY timeCompleted DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $data[] = $row;
        }

        $res->free();

        return $this->create_objects($data);
    }

    /**
     * @param array $uuidList
     * @param array $columnOrders
     * @return array
     */
    public function fetchArray(array $uuidList, array $columnOrders = []): array
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
  userUuid,
  afscList,
  timeStarted,
  timeCompleted,
  lastUpdated,
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  archived,
  testType
FROM testCollection
WHERE uuid IN ('{$uuidListString}')
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $data[] = $row;
        }

        $res->free();

        return $this->create_objects($data);
    }

    /**
     * @param Test $test
     */
    public function save(Test $test): void
    {
        if (!$test->getUuid()) {
            return;
        }

        $uuid = $test->getUuid();
        $userUuid = $test->getUserUuid();
        $afscList = serialize(AfscHelpers::listUuid($test->getAfscs()));
        $timeStarted = $test->getTimeStarted() === null
            ? null
            : $test->getTimeStarted()
                   ->setTimezone(DateTimeHelpers::utc_tz())
                   ->format(DateTimeHelpers::DT_FMT_DB);
        $timeCompleted = $test->getTimeCompleted() === null
            ? null
            : $test->getTimeCompleted()
                   ->setTimezone(DateTimeHelpers::utc_tz())
                   ->format(DateTimeHelpers::DT_FMT_DB);
        $lastUpdated = $test->getLastUpdated() === null
            ? null
            : $test->getLastUpdated()
                   ->setTimezone(DateTimeHelpers::utc_tz())
                   ->format(DateTimeHelpers::DT_FMT_DB);
        $questionList = serialize(QuestionHelpers::listUuid($test->getQuestions()));
        $curQuestion = $test->getCurrentQuestion();
        $numAnswered = $test->getNumAnswered();
        $numMissed = $test->getNumMissed();
        $score = round(
            $test->getScore(),
            Test::SCORE_PRECISION
        );
        $archived = (int)$test->isArchived();
        $type = $test->getType();

        $qry = <<<SQL
INSERT INTO testCollection
  (
    uuid,
    userUuid,
    afscList,
    timeStarted,
    timeCompleted,
    lastUpdated,
    questionList,
    curQuestion,
    numAnswered,
    numMissed,
    score,
    archived,
    testType
  )
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE 
    uuid=VALUES(uuid),
    userUuid=VALUES(userUuid),
    afscList=VALUES(afscList),
    timeStarted=VALUES(timeStarted),
    timeCompleted=VALUES(timeCompleted),
    lastUpdated=VALUES(lastUpdated),
    questionList=VALUES(questionList),
    curQuestion=VALUES(curQuestion),
    numAnswered=VALUES(numAnswered),
    numMissed=VALUES(numMissed),
    score=VALUES(score),
    archived=VALUES(archived),
    testType=VALUES(testType)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('sssssssiiidii',
                               $uuid,
                               $userUuid,
                               $afscList,
                               $timeStarted,
                               $timeCompleted,
                               $lastUpdated,
                               $questionList,
                               $curQuestion,
                               $numAnswered,
                               $numMissed,
                               $score,
                               $archived,
                               $type) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param array $tests
     */
    public function saveArray(array $tests): void
    {
        if (empty($tests)) {
            return;
        }

        foreach ($tests as $test) {
            if (!$test instanceof Test) {
                continue;
            }

            $this->save($test);
        }
    }
}
