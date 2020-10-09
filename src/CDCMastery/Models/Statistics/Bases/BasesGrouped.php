<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 1:27 PM
 */

namespace CDCMastery\Models\Statistics\Bases;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Cache\CacheHandler;
use DateTime;
use Throwable;

class BasesGrouped implements IBaseStats
{
    use TBaseStats;

    private const PRECISION_AVG = 2;

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function averagesBetween(DateTime $start, DateTime $end): array
    {
        $tStart = $start->setTimezone(DateTimeHelpers::utc_tz())
                        ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $tEnd = $end->setTimezone(DateTimeHelpers::utc_tz())
                    ->format(DateTimeHelpers::DT_FMT_DB_DAY_END);

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASES_AVG_BETWEEN, [$tStart, $tEnd,]);

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
  AND userData.userBase IS NOT NULL
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
            $tAvg
        );

        $data = [];
        while ($stmt->fetch()) {
            if (!isset($tBase)) {
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
            IBaseStats::STAT_BASES_AVG_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd,
            ]
        );

        return $data;
    }

    /**
     * @return array
     */
    public function averagesOverall(): array
    {
        $cached = $this->cache->hashAndGet(
            IBaseStats::STAT_BASES_AVG_OVERALL
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
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.testType = 0
GROUP BY baseList.baseName
ORDER BY baseList.baseName
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $averages = [];
        while ($row = $res->fetch_assoc()) {
            $averages[ $row[ 'tBase' ] ] = round(
                $row[ 'tAvg' ] ?? 0.00,
                self::PRECISION_AVG
            );
        }

        $res->free();

        $this->cache->hashAndSet(
            $averages,
            IBaseStats::STAT_BASES_AVG_OVERALL,
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
        $tStart = $start->setTimezone(DateTimeHelpers::utc_tz())
                        ->format(DateTimeHelpers::DT_FMT_DB_DAY_START);
        $tEnd = $end->setTimezone(DateTimeHelpers::utc_tz())
                    ->format(DateTimeHelpers::DT_FMT_DB_DAY_END);

        $cached = $this->cache->hashAndGet(IBaseStats::STAT_BASES_COUNT_BETWEEN, [$tStart, $tEnd,]);

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
                'count' => $tCount
            ];
        }

        $stmt->close();

        $this->cache->hashAndSet(
            $data,
            IBaseStats::STAT_BASES_COUNT_BETWEEN,
            CacheHandler::TTL_XLARGE, [
                $tStart,
                $tEnd,
            ]
        );

        return $data;
    }

    /**
     * @return array
     */
    public function countsOverall(): array
    {
        $cached = $this->cache->hashAndGet(
            IBaseStats::STAT_BASES_COUNT_OVERALL
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
WHERE testCollection.timeCompleted IS NOT NULL
  AND testCollection.testType = 0
GROUP BY baseList.baseName
ORDER BY baseList.baseName
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $counts = [];
        while ($row = $res->fetch_assoc()) {
            $counts[ $row[ 'tBase' ] ] = (int)($row[ 'tCount' ] ?? 0);
        }

        $res->free();

        $this->cache->hashAndSet(
            $counts,
            IBaseStats::STAT_BASES_COUNT_OVERALL,
            CacheHandler::TTL_XLARGE
        );

        return $counts;
    }
}