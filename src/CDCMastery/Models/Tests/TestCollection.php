<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/28/2017
 * Time: 8:43 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class TestCollection
{
    public const TABLE_NAME = 'testCollection';

    public const COL_AFSC_LIST = 'afscList';
    public const COL_TIME_STARTED = 'timeStarted';
    public const COL_TIME_COMPLETED = 'timeCompleted';
    public const COL_CUR_QUESTION = 'curQuestion';
    public const COL_NUM_ANSWERED = 'numAnswered';
    public const COL_NUM_MISSED = 'numMissed';
    public const COL_SCORE = 'score';
    public const COL_IS_COMBINED = 'combined';
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
                case self::COL_CUR_QUESTION:
                case self::COL_NUM_ANSWERED:
                case self::COL_NUM_MISSED:
                case self::COL_SCORE:
                case self::COL_IS_COMBINED:
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
     * @param array $rows
     * @return Test[]
     */
    private function create_objects(array $rows): array
    {
        if (!$rows) {
            return [];
        }

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

            $afscs = $this->afscs->fetchArray($afscs);
            $questions = $this->questions->fetchArrayMixed($questions);

            if ($row[ 'timeStarted' ] !== null) {
                $timeStarted = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'timeStarted' ] ?? ''
                );
            }

            if ($row[ 'timeCompleted' ] !== null) {
                $timeCompleted = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'timeCompleted' ] ?? ''
                );
            }

            $test = new Test();
            $test->setUuid($row[ 'uuid' ]);
            $test->setUserUuid($row[ 'userUuid' ]);
            $test->setAfscs($afscs);
            $test->setTimeStarted($timeStarted ?? null);
            $test->setTimeCompleted($timeCompleted ?? null);
            $test->setQuestions($questions);
            $test->setCurrentQuestion($row[ 'curQuestion' ] ?? 0);
            $test->setNumAnswered($row[ 'numAnswered' ] ?? 0);
            $test->setNumMissed($row[ 'numMissed' ] ?? 0);
            $test->setScore((float)($row[ 'score' ] ?? 0.00));
            $test->setCombined((bool)($row[ 'combined' ] ?? false));
            $test->setArchived((bool)($row[ 'archived' ] ?? false));
            $out[ $row[ 'uuid' ] ] = $test;
        }

        return $out;
    }

    /** @noinspection UnusedFunctionResultInspection */
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

        $this->db->query($qry);
    }

    /**
     * @param string[] $uuids
     * @noinspection UnusedFunctionResultInspection
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

        $this->db->query($qry);
    }

    /** @noinspection UnusedFunctionResultInspection */
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

        $this->db->query($qry);
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
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  combined,
  archived
FROM testCollection
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result(
            $_uuid,
            $userUuid,
            $afscList,
            $timeStarted,
            $timeCompleted,
            $questionList,
            $curQuestion,
            $numAnswered,
            $numMissed,
            $score,
            $combined,
            $archived
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
            'questionList' => $questionList,
            'curQuestion' => $curQuestion,
            'numAnswered' => $numAnswered,
            'numMissed' => $numMissed,
            'score' => $score,
            'combined' => $combined,
            'archived' => $archived,
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
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  combined,
  archived
FROM testCollection
WHERE userUuid = ?
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $_uuid,
            $userUuid,
            $afscList,
            $timeStarted,
            $timeCompleted,
            $questionList,
            $curQuestion,
            $numAnswered,
            $numMissed,
            $score,
            $combined,
            $archived
        );

        $data = [];
        while ($stmt->fetch()) {
            $data[] = [
                'uuid' => $_uuid,
                'userUuid' => $userUuid,
                'afscList' => $afscList,
                'timeStarted' => $timeStarted,
                'timeCompleted' => $timeCompleted,
                'questionList' => $questionList,
                'curQuestion' => $curQuestion,
                'numAnswered' => $numAnswered,
                'numMissed' => $numMissed,
                'score' => $score,
                'combined' => $combined,
                'archived' => $archived,
            ];
        }

        $stmt->close();

        if (!$data) {
            return [];
        }

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
  questionList,
  curQuestion,
  numAnswered,
  numMissed,
  score,
  combined,
  archived
FROM testCollection
WHERE uuid IN ('{$uuidListString}')
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

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
            : $test->getTimeStarted()->format(
                DateTimeHelpers::DT_FMT_DB
            );
        $timeCompleted = $test->getTimeCompleted() === null
            ? null
            : $test->getTimeCompleted()->format(
                DateTimeHelpers::DT_FMT_DB
            );
        $questionList = serialize(QuestionHelpers::listUuid($test->getQuestions()));
        $curQuestion = $test->getCurrentQuestion();
        $numAnswered = $test->getNumAnswered();
        $numMissed = $test->getNumMissed();
        $score = round(
            $test->getScore(),
            Test::SCORE_PRECISION
        );
        $combined = (int)$test->isCombined();
        $archived = (int)$test->isArchived();

        $qry = <<<SQL
INSERT INTO testCollection
  (
    uuid, 
    userUuid, 
    afscList, 
    timeStarted, 
    timeCompleted, 
    questionList, 
    curQuestion, 
    numAnswered, 
    numMissed, 
    score, 
    combined, 
    archived
  )
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE 
    uuid=VALUES(uuid), 
    userUuid=VALUES(userUuid), 
    afscList=VALUES(afscList), 
    timeStarted=VALUES(timeStarted), 
    timeCompleted=VALUES(timeCompleted), 
    questionList=VALUES(questionList), 
    curQuestion=VALUES(curQuestion), 
    numAnswered=VALUES(numAnswered), 
    numMissed=VALUES(numMissed), 
    score=VALUES(score), 
    combined=VALUES(combined), 
    archived=VALUES(archived)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssssssiiidii',
            $uuid,
            $userUuid,
            $afscList,
            $timeStarted,
            $timeCompleted,
            $questionList,
            $curQuestion,
            $numAnswered,
            $numMissed,
            $score,
            $combined,
            $archived
        );

        if (!$stmt->execute()) {
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