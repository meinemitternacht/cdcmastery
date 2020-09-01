<?php

namespace CDCMastery\Models\Statistics\Bases;

use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Cache\CacheHandler;
use DateTime;
use DateTimeInterface;

class Bases implements IBaseStats
{
    use TBaseStats;

    private const PRECISION_AVG = 2;

    public function averageBetween(Base $base, DateTime $start, DateTime $end): ?float
    {
        $buuid = $base->getUuid();

        $tStart = $start->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $tEnd = $end->format(DateTimeHelpers::DT_FMT_DB_DAY_END);

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASE_AVG_BETWEEN,
                                           [$tStart, $tEnd, $buuid]);

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.timeStarted BETWEEN ? AND ?
  AND testCollection.score > 0
  AND userData.userBase = ?
SQL;

        $stmt = $this->prepare_and_bind($qry, 'sss', $tStart, $tEnd, $buuid);

        $stmt->bind_result($tAvg);
        $stmt->fetch();
        $stmt->close();

        $data = round($tAvg,
                      self::PRECISION_AVG);

        $this->cache->hashAndSet($data,
                                 IBaseStats::STAT_BASE_AVG_BETWEEN,
                                 CacheHandler::TTL_XLARGE,
                                 [$tStart, $tEnd, $buuid,]);

        return $data;
    }

    private function averageByTimeSegment(Base $base, string $type): array
    {
        $buuid = $base->getUuid();

        switch ($type) {
            case IBaseStats::STAT_BASE_AVG_BY_MONTH:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
  AND userData.userBase = ?
GROUP BY tDate
ORDER BY tDate, tAvg DESC
SQL;
                break;
            case IBaseStats::STAT_BASE_AVG_BY_WEEK:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  YEARWEEK(testCollection.timeStarted) AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
  AND userData.userBase = ?
GROUP BY YEARWEEK(testCollection.timeStarted)
ORDER BY YEARWEEK(testCollection.timeStarted), tAvg DESC
SQL;
                break;
            case IBaseStats::STAT_BASE_AVG_BY_YEAR:
                $timeout = CacheHandler::TTL_XLARGE;
                $qry = <<<SQL
SELECT
  DATE_FORMAT(testCollection.timeStarted, '%Y') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
  AND userData.userBase = ?
GROUP BY tDate
ORDER BY tDate, tAvg DESC
SQL;
                break;
            case IBaseStats::STAT_BASE_AVG_LAST_SEVEN:
                $timeout = CacheHandler::TTL_LARGE;
                $qry = <<<SQL
SELECT
  DATE_FORMAT(testCollection.timeStarted, '%Y-%m-%d') AS tDate,
  AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.score > 0
  AND testCollection.timeStarted BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()
  AND userData.userBase = ?
GROUP BY tDate
ORDER BY tDate DESC
SQL;
                break;
            default:
                return [];
        }

        $cached = $this->cache->hashAndGet(
            $type
        );

        if ($cached !== false) {
            return $cached;
        }

        $stmt = $this->prepare_and_bind($qry, 's', $buuid);

        $stmt->bind_result($tDate, $tAvg);

        $averages = [];
        while ($stmt->fetch()) {
            $averages[] = [
                'tDate' => $tDate,
                'tAvg' => round($tAvg,
                                self::PRECISION_AVG),
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet($averages,
                                 $type,
                                 $timeout,
                                 [$buuid,]);

        return $averages;
    }

    /**
     * @return array
     */
    public function averageByMonth(): array
    {
        return $this->averageByTimeSegment(IBaseStats::STAT_BASE_AVG_BY_MONTH);
    }

    /**
     * @return array
     */
    public function averageByWeek(): array
    {
        return $this->averageByTimeSegment(IBaseStats::STAT_BASE_AVG_BY_WEEK);
    }

    /**
     * @return array
     */
    public function averageByYear(): array
    {
        return $this->averageByTimeSegment(IBaseStats::STAT_BASE_AVG_BY_YEAR);
    }

    /**
     * @return array
     */
    public function averageLastSevenDays(): array
    {
        return $this->averageByTimeSegment(IBaseStats::STAT_BASE_AVG_LAST_SEVEN);
    }

    public function averageOverall(Base $base): ?float
    {
        $buuid = $base->getUuid();

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASE_AVG_OVERALL,
                                           [$buuid,]);

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  AVG(score) AS tAvg
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.score > 0
  AND testCollection.timeCompleted IS NOT NULL
  AND userData.userBase = ?
SQL;

        $stmt = $this->prepare_and_bind($qry, 's', $buuid);

        $stmt->bind_result($tAvg);
        $stmt->fetch();
        $stmt->close();

        $tAvg = round($tAvg,
                      self::PRECISION_AVG);

        $this->cache->hashAndSet($tAvg,
                                 IBaseStats::STAT_BASE_AVG_OVERALL,
                                 CacheHandler::TTL_XLARGE,
                                 [$buuid,]);

        return $tAvg;
    }

    public function averageCountOverallByUser(Base $base, ?DateTimeInterface $cutoff = null): array
    {
        if ($cutoff === null) {
            $cutoff = new DateTime();
            $cutoff->modify(IBaseStats::DEFAULT_CUTOFF);
        }

        $buuid = $base->getUuid();

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASE_AVG_COUNT_OVERALL_BY_USER,
                                           [$buuid, $cutoff->format(DATE_RFC3339_EXTENDED)]);

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT
    userData.uuid,
    COUNT(*) AS tCount,
    AVG(testCollection.score) AS tAvg
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE userData.userBase = ?
    AND userData.userLastActive > ?
GROUP BY userData.uuid
ORDER BY tAvg DESC, tCount DESC, userData.uuid
SQL;

        $cutoff_fmt = $cutoff->format(DateTimeHelpers::D_FMT_SHORT);
        $stmt = $this->prepare_and_bind($qry, 'ss', $buuid, $cutoff_fmt);

        $stmt->bind_result($user, $tCount, $tAvg);

        $data = [];
        while ($stmt->fetch()) {
            $data[ $user ] = [
                'tAvg' => round($tAvg,
                                self::PRECISION_AVG),
                'tCount' => $tCount,
            ];
        }
        $stmt->close();

        $this->cache->hashAndSet($data,
                                 IBaseStats::STAT_BASE_AVG_COUNT_OVERALL_BY_USER,
                                 CacheHandler::TTL_XLARGE,
                                 [$buuid, $cutoff->format(DATE_RFC3339_EXTENDED)]);

        return $data;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function countBetween(DateTime $start, DateTime $end): array
    {
        $tStart = $start->format(
            DateTimeHelpers::DT_FMT_DB_DAY_START
        );

        $tEnd = $end->format(
            DateTimeHelpers::DT_FMT_DB_DAY_END
        );

        $cached = $this->cache->hashAndGet(
            IBaseStats::STAT_BASE_COUNT_BETWEEN, [
                                                   $tStart,
                                                   $tEnd,
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
                'count' => $tCount,
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $data,
            IBaseStats::STAT_BASE_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd,
            ]
        );

        return $data;
    }

    /**
     * @param string $type
     * @return array
     */
    private function countByTimeSegment(string $type): array
    {
        switch ($type) {
            case IBaseStats::STAT_BASE_COUNT_BY_MONTH:
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
            case IBaseStats::STAT_BASE_COUNT_BY_WEEK:
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
            case IBaseStats::STAT_BASE_COUNT_BY_YEAR:
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
            case IBaseStats::STAT_BASE_COUNT_LAST_SEVEN:
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
            $counts[ $row[ 'tBase' ] ][] = [
                'tDate' => $row[ 'tDate' ] ?? '',
                'tCount' => (int)($row[ 'tCount' ] ?? 0),
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
    public function countByMonth(): array
    {
        return $this->countByTimeSegment(IBaseStats::STAT_BASE_COUNT_BY_MONTH);
    }

    /**
     * @return array
     */
    public function countByWeek(): array
    {
        return $this->countByTimeSegment(IBaseStats::STAT_BASE_COUNT_BY_WEEK);
    }

    /**
     * @return array
     */
    public function countByYear(): array
    {
        return $this->countByTimeSegment(IBaseStats::STAT_BASE_COUNT_BY_YEAR);
    }

    /**
     * @return array
     */
    public function countLastSevenDays(): array
    {
        return $this->countByTimeSegment(IBaseStats::STAT_BASE_COUNT_LAST_SEVEN);
    }

    public function countOverall(Base $base): ?int
    {
        $buuid = $base->getUuid();

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASE_COUNT_OVERALL,
                                           [$buuid,]);

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  COUNT(*) AS tCount
FROM testCollection 
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.score > 0
  AND testCollection.timeCompleted IS NOT NULL
  AND userData.userBase = ?
SQL;

        $stmt = $this->prepare_and_bind($qry, 's', $buuid);

        $stmt->bind_result($tCount);
        $stmt->fetch();
        $stmt->close();

        $this->cache->hashAndSet($tCount,
                                 IBaseStats::STAT_BASE_COUNT_OVERALL,
                                 CacheHandler::TTL_XLARGE,
                                 [$buuid,]);

        return $tCount;
    }
}