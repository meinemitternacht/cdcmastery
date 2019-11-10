<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 1:27 PM
 */

namespace CDCMastery\Models\Statistics;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use DateTime;
use Monolog\Logger;
use mysqli;

class BaseStats
{
    private const PRECISION_AVG = 2;

    private const STAT_BASES_AVG_BETWEEN = 'bases_avg_between';
    private const STAT_BASES_AVG_BY_MONTH = 'bases_avg_by_month';
    private const STAT_BASES_AVG_BY_WEEK = 'bases_avg_by_week';
    private const STAT_BASES_AVG_BY_YEAR = 'bases_avg_by_year';
    private const STAT_BASES_AVG_LAST_SEVEN = 'bases_avg_last_seven';
    private const STAT_BASES_AVG_OVERALL = 'bases_avg_overall';

    private const STAT_BASES_COUNT_BETWEEN = 'bases_count_between';
    private const STAT_BASES_COUNT_BY_MONTH = 'bases_count_by_month';
    private const STAT_BASES_COUNT_BY_WEEK = 'bases_count_by_week';
    private const STAT_BASES_COUNT_BY_YEAR = 'bases_count_by_year';
    private const STAT_BASES_COUNT_LAST_SEVEN = 'bases_count_last_seven';
    private const STAT_BASES_COUNT_OVERALL = 'bases_count_overall';

    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var CacheHandler
     */
    protected $cache;

    /**
     * Tests constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param CacheHandler $cacheHandler
     */
    public function __construct(mysqli $mysqli, Logger $logger, CacheHandler $cacheHandler)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cache = $cacheHandler;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function averagesBetween(DateTime $start, DateTime $end): array
    {
        $tStart = $start->format(
            DateTimeHelpers::DT_FMT_DB_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::DT_FMT_DB_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_BASES_AVG_BETWEEN, [
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userBase AS tBase,
  AVG(score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.timeStarted BETWEEN ? AND ?
  AND testCollection.score > 0
GROUP BY tBase
HAVING tBase IS NOT NULL
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $tBase,
            $tAvg
        );

        $data = [];
        while ($stmt->fetch()) {
            if (!isset($tBase) || empty($tBase)) {
                continue;
            }

            $data[] = [
                'uuid' => $tBase,
                'avg' => round(
                    $tAvg,
                    self::PRECISION_AVG
                )
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $data,
            self::STAT_BASES_AVG_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd
           ]
        );

        return $data;
    }

    /**
     * @param string $type
     * @return array
     */
    private function averagesByTimeSegment(string $type): array
    {
        switch ($type) {
            case self::STAT_BASES_AVG_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  userData.userBase AS tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY tDate, tBase
ORDER BY tDate, tAvg DESC
SQL;
                break;
            case self::STAT_BASES_AVG_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  userData.userBase AS tBase,
  YEARWEEK(testCollection.timeStarted) AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY YEARWEEK(testCollection.timeStarted), tBase
ORDER BY YEARWEEK(testCollection.timeStarted), tAvg DESC
SQL;
                break;
            case self::STAT_BASES_AVG_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  userData.userBase AS tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY tDate, tBase
ORDER BY tDate, tAvg DESC
SQL;
                break;
            case self::STAT_BASES_AVG_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT
  userData.userBase AS tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m-%d') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
  AND testCollection.timeStarted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY tDate, tBase
ORDER BY tDate
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type
        );

        if ($cached !== false) {
            return $cached;
        }

        $res = $this->db->query($qry);

        $averages = [];
        while ($row = $res->fetch_assoc()) {
            $averages[$row['tBase']][] = [
                'tDate' => $row['tDate'] ?? '',
                'tAvg' => round(
                    $row['tAvg'] ?? 0.00,
                    self::PRECISION_AVG
                )
            ];
        }

        $res->free();

        $this->cache->hashAndSet(
            $averages,
            $type,
            $timeout
        );

        return $averages;
    }

    /**
     * @return array
     */
    public function averagesByMonth(): array
    {
        return $this->averagesByTimeSegment(self::STAT_BASES_AVG_BY_MONTH);
    }

    /**
     * @return array
     */
    public function averagesByWeek(): array
    {
        return $this->averagesByTimeSegment(self::STAT_BASES_AVG_BY_WEEK);
    }

    /**
     * @return array
     */
    public function averagesByYear(): array
    {
        return $this->averagesByTimeSegment(self::STAT_BASES_AVG_BY_YEAR);
    }

    /**
     * @return array
     */
    public function averagesLastSevenDays(): array
    {
        return $this->averagesByTimeSegment(self::STAT_BASES_AVG_LAST_SEVEN);
    }

    /**
     * @return array
     */
    public function averagesOverall(): array
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_BASES_AVG_OVERALL
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userBase AS tBase,
  AVG(score) AS tAvg
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
LEFT JOIN baseList ON userData.userBase = baseList.uuid
WHERE testCollection.score > 0
  AND testCollection.timeCompleted IS NOT NULL
GROUP BY baseList.baseName
ORDER BY baseList.baseName
SQL;

        $res = $this->db->query($qry);

        $averages = [];
        while ($row = $res->fetch_assoc()) {
            $averages[$row['tBase']] = round(
                $row['tAvg'] ?? 0.00,
                self::PRECISION_AVG
            );
        }

        $res->free();

        $this->cache->hashAndSet(
            $averages,
            self::STAT_BASES_AVG_OVERALL,
            CacheHandler::TTL_XLARGE
        );

        return $averages;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function countsBetween(DateTime $start, DateTime $end): array
    {
        $tStart = $start->format(
            DateTimeHelpers::DT_FMT_DB_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::DT_FMT_DB_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_BASES_COUNT_BETWEEN, [
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userBase AS tBase,
  COUNT(score) AS tCount 
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.timeStarted BETWEEN ? AND ?
  AND testCollection.score > 0
GROUP BY tBase
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $tBase,
            $tCount
        );

        $data = [];
        while ($stmt->fetch()) {
            $data[] = [
                'base' => $tBase,
                'count' => $tCount
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $data,
            self::STAT_BASES_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd
            ]
        );

        return $data;
    }

    /**
     * @param string $type
     * @return array
     */
    private function countsByTimeSegment(string $type): array
    {
        switch ($type) {
            case self::STAT_BASES_COUNT_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  userData.userBase as tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m') AS tDate,
  COUNT(*) AS tCount
FROM testCollection
  LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY tDate, tBase
ORDER BY tDate
SQL;
                break;
            case self::STAT_BASES_COUNT_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  userData.userBase as tBase,
  YEARWEEK(testCollection.timeStarted) AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
  LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY YEARWEEK(testCollection.timeStarted), tBase
ORDER BY YEARWEEK(testCollection.timeStarted)
SQL;
                break;
            case self::STAT_BASES_COUNT_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  userData.userBase as tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y') AS tDate,
  COUNT(*) AS tCount
FROM testCollection
  LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
GROUP BY tDate, tBase
ORDER BY tDate
SQL;
                break;
            case self::STAT_BASES_COUNT_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  userData.userBase as tBase,
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m-%d') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
  LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
    AND testCollection.timeStarted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
  AND testCollection.score > 0
GROUP BY DAY(timeStarted)
ORDER BY timeStarted
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type
        );

        if ($cached !== false) {
            return $cached;
        }

        $res = $this->db->query($qry);

        $counts = [];
        while ($row = $res->fetch_assoc()) {
            $counts[$row['tBase']][] = [
                'tDate' => $row['tDate'] ?? '',
                'tCount' => (int)($row['tCount'] ?? 0)
            ];
        }

        $res->free();

        $this->cache->hashAndSet(
            $counts,
            $type,
            $timeout
        );

        return $counts;
    }

    /**
     * @return array
     */
    public function countsByMonth(): array
    {
        return $this->countsByTimeSegment(self::STAT_BASES_COUNT_BY_MONTH);
    }

    /**
     * @return array
     */
    public function countsByWeek(): array
    {
        return $this->countsByTimeSegment(self::STAT_BASES_COUNT_BY_WEEK);
    }

    /**
     * @return array
     */
    public function countsByYear(): array
    {
        return $this->countsByTimeSegment(self::STAT_BASES_COUNT_BY_YEAR);
    }

    /**
     * @return array
     */
    public function countsLastSevenDays(): array
    {
        return $this->countsByTimeSegment(self::STAT_BASES_COUNT_LAST_SEVEN);
    }

    /**
     * @return array
     */
    public function countsOverall(): array
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_BASES_COUNT_OVERALL
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userBase AS tBase,
  COUNT(*) AS tCount
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
LEFT JOIN baseList ON userData.userBase = baseList.uuid
WHERE testCollection.score > 0
  AND testCollection.timeCompleted IS NOT NULL 
GROUP BY baseList.baseName
ORDER BY baseList.baseName
SQL;

        $res = $this->db->query($qry);

        $counts = [];
        while ($row = $res->fetch_assoc()) {
            $counts[$row['tBase']] = (int)($row['tCount'] ?? 0);
        }

        $res->free();

        $this->cache->hashAndSet(
            $counts,
            self::STAT_BASES_COUNT_OVERALL,
            CacheHandler::TTL_XLARGE
        );

        return $counts;
    }
}