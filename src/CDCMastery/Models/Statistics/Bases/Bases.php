<?php
declare(strict_types=1);

namespace CDCMastery\Models\Statistics\Bases;

use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Cache\CacheHandler;
use DateTime;
use DateTimeInterface;
use Throwable;

class Bases implements IBaseStats
{
    use TBaseStats;

    private const PRECISION_AVG = 2;

    public function averageBetween(Base $base, DateTime $start, DateTime $end): ?float
    {
        $buuid = $base->getUuid();

        $tStart = $start->setTimezone(DateTimeHelpers::utc_tz())
                        ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $tEnd = $end->setTimezone(DateTimeHelpers::utc_tz())
                    ->format(DateTimeHelpers::DT_FMT_DB_DAY_END);

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
  AND userData.userBase = ?
  AND testCollection.testType = 0
SQL;

        try {
            $stmt = $this->prepare_and_bind($qry, 'sss', $tStart, $tEnd, $buuid);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return null;
        }

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
WHERE testCollection.timeCompleted IS NOT NULL
  AND userData.userBase = ?
  AND testCollection.testType = 0
SQL;

        try {
            $stmt = $this->prepare_and_bind($qry, 's', $buuid);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return null;
        }

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
  AND testCollection.testType = 0
GROUP BY userData.uuid
ORDER BY tAvg DESC, tCount DESC, userData.uuid
SQL;

        $cutoff_fmt = $cutoff->setTimezone(DateTimeHelpers::utc_tz())->format(DateTimeHelpers::D_FMT_SHORT);

        try {
            $stmt = $this->prepare_and_bind($qry, 'ss', $buuid, $cutoff_fmt);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return [];
        }

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
        $tStart = $start->setTimezone(DateTimeHelpers::utc_tz())
                        ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $tEnd = $end->setTimezone(DateTimeHelpers::utc_tz())
                    ->format(DateTimeHelpers::DT_FMT_DB_DAY_END);

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASE_COUNT_BETWEEN, [$tStart, $tEnd,]);

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
  AND testCollection.testType = 0
GROUP BY tBase
SQL;

        try {
            $stmt = $this->prepare_and_bind($qry, 'ss', $tStart, $tEnd);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return [];
        }

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
  AND testCollection.testType = 0
SQL;

        try {
            $stmt = $this->prepare_and_bind($qry, 's', $buuid);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return null;
        }

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
