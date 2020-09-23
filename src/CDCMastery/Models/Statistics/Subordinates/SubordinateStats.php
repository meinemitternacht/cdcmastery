<?php


namespace CDCMastery\Models\Statistics\Subordinates;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class SubordinateStats
{
    private const PRECISION_AVG = 2;

    private const STAT_TEST_COUNT_OVERALL = 'sub_test_count_overall';
    private const STAT_TEST_AVG_OVERALL = 'sub_test_avg_overall';
    private const STAT_TEST_COUNT_AVG = 'sub_test_count_avg';
    private const STAT_TEST_LATEST_SCORE = 'sub_test_latest_score';

    private mysqli $db;
    private Logger $log;
    private CacheHandler $cache;

    public function __construct(mysqli $mysqli, Logger $logger, CacheHandler $cache)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cache = $cache;
    }

    public function subordinate_tests_count_overall(User $manager): int
    {
        $mgr_uuid = $manager->getUuid();
        $cached = $this->cache->hashAndGet(
            self::STAT_TEST_COUNT_OVERALL,
            [$mgr_uuid]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT
    COUNT(*) AS tCount
FROM testCollection t1
WHERE t1.userUuid IN (
    SELECT DISTINCT (t2.userUUID) FROM (              
        SELECT userUUID
            FROM userTrainingManagerAssociations
            WHERE trainingManagerUUID = ?
        UNION
        SELECT userUUID
            FROM userSupervisorAssociations
            WHERE supervisorUUID = ?   
        ) t2
    )
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        if (!$stmt->bind_param('ss', $mgr_uuid, $mgr_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($t_count);
        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet($t_count ?? 0,
                                 self::STAT_TEST_COUNT_OVERALL,
                                 CacheHandler::TTL_SMALL,
                                 [$mgr_uuid]);
        return $t_count ?? 0;
    }

    public function subordinate_tests_avg_overall(User $manager): float
    {
        $mgr_uuid = $manager->getUuid();
        $cached = $this->cache->hashAndGet(
            self::STAT_TEST_AVG_OVERALL,
            [$mgr_uuid]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT
    AVG(t1.score) AS tAvg
FROM testCollection t1
WHERE t1.userUuid IN (
    SELECT DISTINCT (t2.userUUID) FROM (              
        SELECT userUUID
            FROM userTrainingManagerAssociations
            WHERE trainingManagerUUID = ?
        UNION
        SELECT userUUID
            FROM userSupervisorAssociations
            WHERE supervisorUUID = ?   
        ) t2
    )
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        if (!$stmt->bind_param('ss', $mgr_uuid, $mgr_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($t_avg);
        $stmt->fetch();
        $stmt->close();

        $data = round($t_avg ?? 0.00, self::PRECISION_AVG);
        $this->cache->hashAndSet($data,
                                 self::STAT_TEST_AVG_OVERALL,
                                 CacheHandler::TTL_SMALL,
                                 [$mgr_uuid]);
        return $data;
    }

    public function subordinate_tests_count_avg(User $manager): array
    {
        $mgr_uuid = $manager->getUuid();
        $cached = $this->cache->hashAndGet(
            self::STAT_TEST_COUNT_AVG,
            [$mgr_uuid]
        );

        if (is_array($cached)) {
            return $cached;
        }

        $qry = <<<SQL
SELECT
    userData.uuid,
    COUNT(*) AS tCount,
    AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.userUuid IN (
    SELECT DISTINCT (t3.userUUID) FROM (              
        SELECT userUUID
            FROM userTrainingManagerAssociations
            WHERE trainingManagerUUID = ?
        UNION
        SELECT userUUID
            FROM userSupervisorAssociations
            WHERE supervisorUUID = ?   
        ) t3
    )
GROUP BY userData.uuid
ORDER BY tAvg DESC, tCount DESC, userData.uuid
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('ss', $mgr_uuid, $mgr_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result($user_uuid, $t_count, $t_avg);

        $data = [];
        while ($stmt->fetch()) {
            $data[ $user_uuid ] = [
                'tCount' => $t_count,
                'tAvg' => round($t_avg, self::PRECISION_AVG),
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet($data,
                                 self::STAT_TEST_COUNT_AVG,
                                 CacheHandler::TTL_SMALL,
                                 [$mgr_uuid]);
        return $data;
    }

    public function subordinate_tests_latest_score(User $manager): array
    {
        $mgr_uuid = $manager->getUuid();
        $cached = $this->cache->hashAndGet(
            self::STAT_TEST_LATEST_SCORE,
            [$mgr_uuid]
        );

        if (is_array($cached)) {
            return $cached;
        }

        $qry = <<<SQL
SELECT
    t1.userUuid,
    t1.score,
    t1.timeCompleted
FROM testCollection t1
LEFT JOIN testCollection t2 ON (t1.userUuid = t2.userUuid AND t1.timeCompleted < t2.timeCompleted)
LEFT JOIN userData ON t1.userUuid = userData.uuid
WHERE t1.userUuid IN (
    SELECT DISTINCT (t3.userUUID) FROM (              
        SELECT userUUID
            FROM userTrainingManagerAssociations
            WHERE trainingManagerUUID = ?
        UNION
        SELECT userUUID
            FROM userSupervisorAssociations
            WHERE supervisorUUID = ?   
        ) t3
    )
  AND t1.timeCompleted IS NOT NULL
  AND t1.score > 0
  AND t2.timeCompleted IS NULL
GROUP BY userData.uuid
ORDER BY userData.uuid;
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('ss', $mgr_uuid, $mgr_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result($user_uuid, $score, $time_completed);

        $data = [];
        while ($stmt->fetch()) {
            $data[ $user_uuid ] = [
                'score' => round($score, self::PRECISION_AVG),
                'completed' => DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                          $time_completed),
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet($data,
                                 self::STAT_TEST_LATEST_SCORE,
                                 CacheHandler::TTL_SMALL,
                                 [$mgr_uuid]);
        return $data;
    }
}