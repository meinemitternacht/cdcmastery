<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/3/2017
 * Time: 10:27 PM
 */

namespace CDCMastery\Models\Statistics;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Users\User;
use Monolog\Logger;

class Tests
{
    const PRECISION_AVG = 2;

    const STAT_AVG_BETWEEN = 'avg_between';
    const STAT_AVG_BY_MONTH = 'avg_by_month';
    const STAT_AVG_BY_WEEK = 'avg_by_week';
    const STAT_AVG_BY_YEAR = 'avg_by_year';
    const STAT_AVG_LAST_SEVEN = 'avg_last_seven_days';
    const STAT_AVG_OVERALL = 'avg_overall';
    
    const STAT_BASE_AVG_BETWEEN = 'base_avg_between';
    const STAT_BASE_AVG_BY_MONTH = 'base_avg_by_month';
    const STAT_BASE_AVG_BY_WEEK = 'base_avg_by_week';
    const STAT_BASE_AVG_BY_YEAR = 'base_avg_by_year';
    const STAT_BASE_AVG_LAST_SEVEN = 'base_avg_last_seven_days';
    const STAT_BASE_AVG_OVERALL = 'base_avg_overall';

    const STAT_BASE_COUNT_BETWEEN = 'base_count_between';
    const STAT_BASE_COUNT_BY_MONTH = 'base_count_by_month';
    const STAT_BASE_COUNT_BY_WEEK = 'base_count_by_week';
    const STAT_BASE_COUNT_BY_YEAR = 'base_count_by_year';
    const STAT_BASE_COUNT_LAST_SEVEN = 'base_count_last_seven_days';
    const STAT_BASE_COUNT_OVERALL = 'base_count_overall';

    const STAT_COUNT_BETWEEN = 'count_between';
    const STAT_COUNT_BY_MONTH = 'count_by_month';
    const STAT_COUNT_BY_WEEK = 'count_by_week';
    const STAT_COUNT_BY_YEAR = 'count_by_year';
    const STAT_COUNT_LAST_SEVEN = 'count_last_seven_days';
    const STAT_COUNT_OVERALL = 'count_overall';

    const STAT_USER_AVG_BETWEEN = 'user_avg_between';
    const STAT_USER_AVG_BY_MONTH = 'user_avg_by_month';
    const STAT_USER_AVG_BY_WEEK = 'user_avg_by_week';
    const STAT_USER_AVG_BY_YEAR = 'user_avg_by_year';
    const STAT_USER_AVG_LAST_SEVEN = 'user_avg_last_seven_days';
    const STAT_USER_AVG_OVERALL = 'user_avg_overall';

    const STAT_USER_COUNT_BETWEEN = 'user_count_between';
    const STAT_USER_COUNT_BY_MONTH = 'user_count_by_month';
    const STAT_USER_COUNT_BY_WEEK = 'user_count_by_week';
    const STAT_USER_COUNT_BY_YEAR = 'user_count_by_year';
    const STAT_USER_COUNT_LAST_SEVEN = 'user_count_last_seven_days';
    const STAT_USER_COUNT_OVERALL = 'user_count_overall';

    /**
     * @var \mysqli
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
     * @param \mysqli $mysqli
     * @param Logger $logger
     * @param CacheHandler $cacheHandler
     */
    public function __construct(\mysqli $mysqli, Logger $logger, CacheHandler $cacheHandler)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cache = $cacheHandler;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return float
     */
    public function averageBetween(\DateTime $start, \DateTime $end): float
    {
        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_AVG_BETWEEN, [
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS avgScore 
FROM testCollection 
WHERE timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0.00;
        }

        $stmt->bind_result($avgScore);
        $stmt->fetch();
        $stmt->close();

        $avgScore = round(
            $avgScore,
            self::PRECISION_AVG
        );

        $this->cache->hashAndSet(
            $avgScore,
            self::STAT_AVG_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd
           ]
        );

        return $avgScore ?? 0.00;
    }

    /**
     * @param string $type
     * @return array
     */
    private function averageByTimeSegment(string $type): array
    {
        switch ($type) {
            case self::STAT_AVG_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_AVG_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_AVG_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_AVG_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate, 
  AVG(score) AS tAvg
FROM testCollection 
WHERE timeCompleted
  BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
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
            if (($row['tDate'] ?? false) === false) {
                continue;
            }

            if (($row['tAvg'] ?? false) === false) {
                continue;
            }

            $averages[$row['tDate']] = round(
                $row['tAvg'] ?? 0.00,
                self::PRECISION_AVG
            );
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
    public function averageByMonth(): array
    {
        return $this->averageByTimeSegment(self::STAT_AVG_BY_MONTH);
    }

    /**
     * @return array
     */
    public function averageByWeek(): array
    {
        return $this->averageByTimeSegment(self::STAT_AVG_BY_WEEK);
    }

    /**
     * @return array
     */
    public function averageByYear(): array
    {
        return $this->averageByTimeSegment(self::STAT_AVG_BY_YEAR);
    }

    /**
     * @return array
     */
    public function averageLastSevenDays(): array
    {
        return $this->averageByTimeSegment(self::STAT_AVG_LAST_SEVEN);
    }

    /**
     * @return float
     */
    public function averageOverall(): float
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_AVG_OVERALL
        );

        if ($cached !== false) {
            return $cached;
        }
        
        $qry = <<<SQL
SELECT 
  AVG(score) AS tAvg
FROM testCollection 
SQL;

        $res = $this->db->query($qry);
        $row = $res->fetch_assoc();
        
        $average = round(
            $row['tAvg'] ?? 0.00,
            self::PRECISION_AVG
        );

        $res->free();

        $this->cache->hashAndSet(
            $average,
            self::STAT_AVG_OVERALL,
            CacheHandler::TTL_XLARGE
        );
        
        return $average;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return int
     */
    public function countBetween(\DateTime $start, \DateTime $end): int
    {
        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_COUNT_BETWEEN, [
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(score) AS tCount 
FROM testCollection 
WHERE timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($tCount);
        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet(
            $tCount,
            self::STAT_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd
            ]
        );

        return $tCount ?? 0;
    }

    /**
     * @param string $type
     * @return array
     */
    private function countByTimeSegment(string $type): array
    {
        switch ($type) {
            case self::STAT_COUNT_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_COUNT_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_COUNT_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  COUNT(*) AS tCount
FROM testCollection
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_COUNT_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection 
WHERE timeCompleted
  BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
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
            if (($row['tDate'] ?? false) === false) {
                continue;
            }

            if (($row['tCount'] ?? false) === false) {
                continue;
            }

            $counts[$row['tDate']] = $row['tCount'] ?? 0;
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
    public function countByMonth(): array
    {
        return $this->countByTimeSegment(self::STAT_COUNT_BY_MONTH);
    }

    /**
     * @return array
     */
    public function countByWeek(): array
    {
        return $this->countByTimeSegment(self::STAT_COUNT_BY_WEEK);
    }

    /**
     * @return array
     */
    public function countByYear(): array
    {
        return $this->countByTimeSegment(self::STAT_COUNT_BY_YEAR);
    }

    /**
     * @return array
     */
    public function countLastSevenDays(): array
    {
        return $this->countByTimeSegment(self::STAT_COUNT_LAST_SEVEN);
    }

    /**
     * @return int
     */
    public function countOverall(): int
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_COUNT_OVERALL
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(*) AS tCount
FROM testCollection 
SQL;

        $res = $this->db->query($qry);
        $row = $res->fetch_assoc();

        $res->free();

        $this->cache->hashAndSet(
            $row['tCount'] ?? 0,
            self::STAT_COUNT_OVERALL,
            CacheHandler::TTL_XLARGE
        );

        return $row['tCount'] ?? 0;
    }

    /**
     * @param Base $base
     * @param \DateTime $start
     * @param \DateTime $end
     * @return float
     */
    public function baseAverageBetween(Base $base, \DateTime $start, \DateTime $end): float
    {
        if (empty($base->getUuid())) {
            return 0.00;
        }

        $baseUuid = $base->getUuid();

        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_BASE_AVG_BETWEEN, [
                $baseUuid,
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS avgScore 
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
  AND timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $baseUuid,
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0.00;
        }

        $stmt->bind_result($avgScore);
        $stmt->fetch();
        $stmt->close();

        $avgScore = round(
            $avgScore,
            self::PRECISION_AVG
        );

        $this->cache->hashAndSet(
            $avgScore,
            self::STAT_BASE_AVG_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $baseUuid,
                $tStart,
                $tEnd
            ]
        );

        return $avgScore ?? 0.00;
    }

    /**
     * @param Base $base
     * @param string $type
     * @return array
     */
    private function baseAverageByTimeSegment(Base $base, string $type): array
    {
        if (empty($base->getUuid())) {
            return [];
        }

        $baseUuid = $base->getUuid();

        switch ($type) {
            case self::STAT_BASE_AVG_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_BASE_AVG_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_BASE_AVG_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_BASE_AVG_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate, 
  AVG(score) AS tAvg
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
  AND timeCompleted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type, [
                $baseUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $baseUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $tDate,
            $tAvg
        );

        $averages = [];
        while ($stmt->fetch()) {
            if (($tDate ?? false) === false) {
                continue;
            }

            if (($tAvg ?? false) === false) {
                continue;
            }

            $averages[$tDate] = round(
                $tAvg ?? 0.00,
                self::PRECISION_AVG
            );
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $averages,
            $type,
            $timeout, [
                $baseUuid
            ]
        );

        return $averages;
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseAverageByMonth(Base $base): array
    {
        return $this->baseAverageByTimeSegment(
            $base,
            self::STAT_BASE_AVG_BY_MONTH
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseAverageByWeek(Base $base): array
    {
        return $this->baseAverageByTimeSegment(
            $base,
            self::STAT_BASE_AVG_BY_WEEK
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseAverageByYear(Base $base): array
    {
        return $this->baseAverageByTimeSegment(
            $base,
            self::STAT_BASE_AVG_BY_YEAR
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseAverageLastSevenDays(Base $base): array
    {
        return $this->baseAverageByTimeSegment(
            $base,
            self::STAT_BASE_AVG_LAST_SEVEN
        );
    }

    /**
     * @param Base $base
     * @return float
     */
    public function baseAverageOverall(Base $base): float
    {
        if (empty($base->getUuid())) {
            return 0.00;
        }
        
        $baseUuid = $base->getUuid();
        
        $cached = $this->cache->hashAndGet(
            self::STAT_BASE_AVG_OVERALL, [
                $baseUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS tAvg
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $baseUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0.00;
        }
        
        $stmt->bind_result(
            $tAvg
        );
        
        $stmt->fetch();
        $stmt->close();
        
        $average = round(
            $tAvg ?? 0.00,
            self::PRECISION_AVG
        );

        $this->cache->hashAndSet(
            $average,
            self::STAT_BASE_AVG_OVERALL,
            CacheHandler::TTL_XLARGE, [
                $baseUuid
            ]
        );

        return $average;
    }

    /**
     * @param Base $base
     * @param \DateTime $start
     * @param \DateTime $end
     * @return int
     */
    public function baseCountBetween(Base $base, \DateTime $start, \DateTime $end): int
    {
        if (empty($base->getUuid())) {
            return 0;
        }
        
        $baseUuid = $base->getUuid();
        
        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_BASE_COUNT_BETWEEN, [
                $baseUuid,
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(score) AS tCount 
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
  AND timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $baseUuid,
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($tCount);
        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet(
            $tCount,
            self::STAT_BASE_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $baseUuid,
                $tStart,
                $tEnd
            ]
        );

        return $tCount ?? 0;
    }

    /**
     * @param Base $base
     * @param string $type
     * @return array
     */
    private function baseCountByTimeSegment(Base $base, string $type): array
    {
        if (empty($base->getUuid())) {
            return [];
        }
        
        $baseUuid = $base->getUuid();
        
        switch ($type) {
            case self::STAT_BASE_COUNT_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_BASE_COUNT_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_BASE_COUNT_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  COUNT(*) AS tCount
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_BASE_COUNT_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
  AND timeCompleted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type, [
                $baseUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $baseUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }
        
        $stmt->bind_result(
            $tDate,
            $tCount
        );
        
        $counts = [];
        while ($stmt->fetch()) {
            if (($tDate ?? false) === false) {
                continue;
            }

            if (($tCount ?? false) === false) {
                continue;
            }

            $counts[$tDate] = $tCount ?? 0;
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $counts,
            $type,
            $timeout, [
                $baseUuid
            ]
        );

        return $counts;
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseCountByMonth(Base $base): array
    {
        return $this->baseCountByTimeSegment(
            $base,
            self::STAT_BASE_COUNT_BY_MONTH
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseCountByWeek(Base $base): array
    {
        return $this->baseCountByTimeSegment(
            $base,
            self::STAT_BASE_COUNT_BY_WEEK
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseCountByYear(Base $base): array
    {
        return $this->baseCountByTimeSegment(
            $base,
            self::STAT_BASE_COUNT_BY_YEAR
        );
    }

    /**
     * @param Base $base
     * @return array
     */
    public function baseCountLastSevenDays(Base $base): array
    {
        return $this->baseCountByTimeSegment(
            $base,
            self::STAT_BASE_COUNT_LAST_SEVEN
        );
    }

    /**
     * @param Base $base
     * @return int
     */
    public function baseCountOverall(Base $base): int
    {
        if (empty($base->getUuid())) {
            return 0;
        }

        $baseUuid = $base->getUuid();

        $cached = $this->cache->hashAndGet(
            self::STAT_BASE_COUNT_OVERALL, [
                $baseUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(*) AS tCount
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $baseUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result(
            $tCount
        );

        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet(
            $tCount ?? 0,
            self::STAT_BASE_COUNT_OVERALL,
            CacheHandler::TTL_XLARGE, [
                $baseUuid
            ]
        );

        return $tCount ?? 0;
    }

    /**
     * @param User $user
     * @param \DateTime $start
     * @param \DateTime $end
     * @return float
     */
    public function userAverageBetween(User $user, \DateTime $start, \DateTime $end): float
    {
        if (empty($user->getUuid())) {
            return 0.00;
        }

        $userUuid = $user->getUuid();

        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_USER_AVG_BETWEEN, [
                $userUuid,
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS avgScore 
FROM testCollection 
WHERE userUuid = ?
  AND timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $userUuid,
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0.00;
        }

        $stmt->bind_result($avgScore);
        $stmt->fetch();
        $stmt->close();

        $avgScore = round(
            $avgScore,
            self::PRECISION_AVG
        );

        $this->cache->hashAndSet(
            $avgScore,
            self::STAT_USER_AVG_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $userUuid,
                $tStart,
                $tEnd
            ]
        );

        return $avgScore ?? 0.00;
    }

    /**
     * @param User $user
     * @param string $type
     * @return array
     */
    private function userAverageByTimeSegment(User $user, string $type): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $userUuid = $user->getUuid();

        switch ($type) {
            case self::STAT_USER_AVG_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
WHERE userUuid = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_USER_AVG_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
WHERE userUuid = ?
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_USER_AVG_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  AVG(testCollection.score) AS tAvg
FROM testCollection
WHERE userUuid = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_USER_AVG_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate, 
  AVG(score) AS tAvg
FROM testCollection 
WHERE userUuid = ?
  AND timeCompleted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type, [
                $userUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $tDate,
            $tAvg
        );

        $averages = [];
        while ($stmt->fetch()) {
            if (($tDate ?? false) === false) {
                continue;
            }

            if (($tAvg ?? false) === false) {
                continue;
            }

            $averages[$tDate] = round(
                $tAvg ?? 0.00,
                self::PRECISION_AVG
            );
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $averages,
            $type,
            $timeout, [
                $userUuid
            ]
        );

        return $averages;
    }

    /**
     * @param User $user
     * @return array
     */
    public function userAverageByMonth(User $user): array
    {
        return $this->userAverageByTimeSegment(
            $user,
            self::STAT_USER_AVG_BY_MONTH
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userAverageByWeek(User $user): array
    {
        return $this->userAverageByTimeSegment(
            $user,
            self::STAT_USER_AVG_BY_WEEK
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userAverageByYear(User $user): array
    {
        return $this->userAverageByTimeSegment(
            $user,
            self::STAT_USER_AVG_BY_YEAR
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userAverageLastSevenDays(User $user): array
    {
        return $this->userAverageByTimeSegment(
            $user,
            self::STAT_USER_AVG_LAST_SEVEN
        );
    }

    /**
     * @param User $user
     * @return float
     */
    public function userAverageOverall(User $user): float
    {
        if (empty($user->getUuid())) {
            return 0.00;
        }

        $userUuid = $user->getUuid();

        $cached = $this->cache->hashAndGet(
            self::STAT_USER_AVG_OVERALL, [
                $userUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS tAvg
FROM testCollection 
WHERE userUuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0.00;
        }

        $stmt->bind_result(
            $tAvg
        );

        $stmt->fetch();
        $stmt->close();

        $average = round(
            $tAvg ?? 0.00,
            self::PRECISION_AVG
        );

        $this->cache->hashAndSet(
            $average,
            self::STAT_USER_AVG_OVERALL,
            CacheHandler::TTL_XLARGE, [
                $userUuid
            ]
        );

        return $average;
    }

    /**
     * @param User $user
     * @param \DateTime $start
     * @param \DateTime $end
     * @return int
     */
    public function userCountBetween(User $user, \DateTime $start, \DateTime $end): int
    {
        if (empty($user->getUuid())) {
            return 0;
        }

        $userUuid = $user->getUuid();

        $tStart = $start->format(
            DateTimeHelpers::FMT_DATABASE_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::FMT_DATABASE_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            self::STAT_USER_COUNT_BETWEEN, [
                $userUuid,
                $tStart,
                $tEnd
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(score) AS tCount 
FROM testCollection 
WHERE userUuid = ?
  AND timeCompleted BETWEEN ? AND ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sss',
            $userUuid,
            $tStart,
            $tEnd
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($tCount);
        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet(
            $tCount,
            self::STAT_USER_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $userUuid,
                $tStart,
                $tEnd
            ]
        );

        return $tCount ?? 0;
    }

    /**
     * @param User $user
     * @param string $type
     * @return array
     */
    private function userCountByTimeSegment(User $user, string $type): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $userUuid = $user->getUuid();

        switch ($type) {
            case self::STAT_USER_COUNT_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
WHERE userUuid = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_USER_COUNT_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  YEARWEEK(testCollection.timeCompleted) AS tDate,  
  COUNT(*) AS tCount
FROM testCollection
WHERE userUuid = ?
GROUP BY YEARWEEK(testCollection.timeCompleted)
ORDER BY YEARWEEK(testCollection.timeCompleted) ASC
SQL;
                break;
            case self::STAT_USER_COUNT_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y') AS tDate, 
  COUNT(*) AS tCount
FROM testCollection
WHERE userUuid = ?
GROUP BY tDate
ORDER BY tDate ASC
SQL;
                break;
            case self::STAT_USER_COUNT_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT 
  DATE_FORMAT(testCollection.timeCompleted, '%Y-%m-%d') AS tDate,  
  COUNT(*) AS tCount
FROM testCollection 
WHERE userUuid = ?
  AND timeCompleted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
GROUP BY DAY(timeCompleted)
ORDER BY timeCompleted ASC
SQL;
                break;
            default:
                return [];
                break;
        }

        $cached = $this->cache->hashAndGet(
            $type, [
                $userUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $tDate,
            $tCount
        );

        $counts = [];
        while ($stmt->fetch()) {
            if (($tDate ?? false) === false) {
                continue;
            }

            if (($tCount ?? false) === false) {
                continue;
            }

            $counts[$tDate] = $tCount ?? 0;
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $counts,
            $type,
            $timeout, [
                $userUuid
            ]
        );

        return $counts;
    }

    /**
     * @param User $user
     * @return array
     */
    public function userCountByMonth(User $user): array
    {
        return $this->userCountByTimeSegment(
            $user,
            self::STAT_USER_COUNT_BY_MONTH
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userCountByWeek(User $user): array
    {
        return $this->userCountByTimeSegment(
            $user,
            self::STAT_USER_COUNT_BY_WEEK
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userCountByYear(User $user): array
    {
        return $this->userCountByTimeSegment(
            $user,
            self::STAT_USER_COUNT_BY_YEAR
        );
    }

    /**
     * @param User $user
     * @return array
     */
    public function userCountLastSevenDays(User $user): array
    {
        return $this->userCountByTimeSegment(
            $user,
            self::STAT_USER_COUNT_LAST_SEVEN
        );
    }

    /**
     * @param User $user
     * @return int
     */
    public function userCountOverall(User $user): int
    {
        if (empty($user->getUuid())) {
            return 0;
        }

        $userUuid = $user->getUuid();

        $cached = $this->cache->hashAndGet(
            self::STAT_USER_COUNT_OVERALL, [
                $userUuid
            ]
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(*) AS tCount
FROM testCollection 
WHERE userUuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result(
            $tCount
        );

        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet(
            $tCount ?? 0,
            self::STAT_USER_COUNT_OVERALL,
            CacheHandler::TTL_XLARGE, [
                $userUuid
            ]
        );

        return $tCount ?? 0;
    }
}