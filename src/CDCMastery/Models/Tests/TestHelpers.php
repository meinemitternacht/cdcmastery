<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 3:12 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Users\User;
use Monolog\Logger;
use mysqli;

class TestHelpers
{
    private const COUNT_COMPLETE = 0;
    private const COUNT_INCOMPLETE = 1;
    private const COUNT_ARCHIVED = 2;
    private const COUNT_COMBINED_COMPLETE = 3;
    private const COUNT_COMBINED_INCOMPLETE = 4;
    private const COUNT_PASSED = 5;
    private const COUNT_FAILED = 6;

    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Config
     */
    protected $config;

    /**
     * TestHelpers constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(mysqli $mysqli, Logger $logger, Config $config)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->config = $config;
    }

    /**
     * @param int $type
     * @param User $user
     * @return int
     */
    private function count(int $type, User $user): int
    {
        if (empty($user->getUuid())) {
            return 0;
        }

        $userUuid = $user->getUuid();
        $passingScore = $this->config->get(['testing', 'passingScore']) ?? 0;

        switch ($type) {
            case self::COUNT_COMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND score > 0
AND timeCompleted IS NOT NULL
SQL;
                break;
            case self::COUNT_INCOMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND score < 1
AND timeCompleted IS NULL
SQL;
                break;
            case self::COUNT_ARCHIVED:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND archived = 1
SQL;
                break;
            case self::COUNT_COMBINED_COMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND combined = 1
AND score < 1
AND timeCompleted IS NULL
SQL;
                break;
            case self::COUNT_COMBINED_INCOMPLETE:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND combined = 1
AND score > 0
AND timeCompleted IS NOT NULL
SQL;
                break;
            case self::COUNT_PASSED:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND score >= {$passingScore}
AND timeCompleted IS NULL
SQL;
                break;
            case self::COUNT_FAILED:
                $qry = <<<SQL
SELECT
  COUNT(*) AS count
FROM testCollection
WHERE userUuid = ?
AND score < {$passingScore}
AND timeCompleted IS NULL
SQL;
                break;
            default:
                return 0;
        }

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return $count ?? 0;
    }

    /**
     * @param User $user
     * @return int
     */
    public function countArchived(User $user): int
    {
        return $this->count(self::COUNT_ARCHIVED, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countCombinedComplete(User $user): int
    {
        return $this->count(self::COUNT_COMBINED_COMPLETE, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countCombinedIncomplete(User $user): int
    {
        return $this->count(self::COUNT_COMBINED_INCOMPLETE, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countComplete(User $user): int
    {
        return $this->count(self::COUNT_COMPLETE, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countFailed(User $user): int
    {
        return $this->count(self::COUNT_FAILED, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countIncomplete(User $user): int
    {
        return $this->count(self::COUNT_INCOMPLETE, $user);
    }

    /**
     * @param User $user
     * @return int
     */
    public function countPassed(User $user): int
    {
        return $this->count(self::COUNT_PASSED, $user);
    }

    /**
     * @param Test[] $tests
     * @return array
     */
    public static function formatHtml(array $tests): array
    {
        if (empty($tests)) {
            return [];
        }

        $newTests = [];
        /** @var Test $test */
        foreach ($tests as $test) {
            if (empty($test->getUuid())) {
                continue;
            }

            $started = $test->getTimeStarted();
            $completed = $test->getTimeCompleted();

            $newTests[] = [
                'uuid' => $test->getUuid(),
                'score' => $test->getScore(),
                'afsc' => implode(
                    ', ',
                    AfscHelpers::listNames($test->getAfscs())
                ),
                'answered' => $test->getNumAnswered(),
                'questions' => $test->getNumQuestions(),
                'time' => [
                    'started' => ($started !== null)
                        ? $started->format(DateTimeHelpers::DT_FMT_SHORT)
                        : '',
                    'completed' => ($completed !== null)
                        ? $completed->format(DateTimeHelpers::DT_FMT_SHORT)
                        : ''
                ]
            ];
        }

        return $newTests;
    }

    /**
     * @param Test[] $tests
     * @return string[]
     */
    public static function listUuid(array $tests): array
    {
        $uuids = [];
        foreach ($tests as $test) {
            if (!$test instanceof Test) {
                continue;
            }

            $uuids[] = $test->getUuid();
        }

        return $uuids;
    }
}