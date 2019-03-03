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
use Monolog\Logger;

class TestCollection
{
    const TABLE_NAME = 'testCollection';

    const COL_AFSC_LIST = 'afscList';
    const COL_TIME_STARTED = 'timeStarted';
    const COL_TIME_COMPLETED = 'timeCompleted';
    const COL_CUR_QUESTION = 'curQuestion';
    const COL_NUM_ANSWERED = 'numAnswered';
    const COL_NUM_MISSED = 'numMissed';
    const COL_SCORE = 'score';
    const COL_IS_COMBINED = 'combined';
    const COL_IS_ARCHIVED = 'archived';

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    const DEFAULT_COL = self::COL_TIME_STARTED;
    const DEFAULT_ORDER = self::ORDER_DESC;

    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Test[]
     */
    private $tests = [];

    /**
     * TestCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param array $columnOrders
     * @return string
     */
    private static function generateOrderSuffix(array $columnOrders): string
    {
        if (empty($columnOrders)) {
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
                    $str = '';
                    continue;
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
     * @param string $uuid
     */
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
     * @param string[] $uuidList
     */
    public function deleteArray(array $uuidList): void
    {
        if (empty($uuidList)) {
            return;
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
DELETE FROM testCollection
WHERE uuid IN ('{$uuidListString}')
SQL;

        $this->db->query($qry);
    }

    /**
     * @param User $user
     */
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

    /**
     * @param string $uuid
     * @return Test
     */
    public function fetch(string $uuid): Test
    {
        if (empty($uuid)) {
            return new Test();
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
            return new Test();
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

        if (!isset($_uuid) || $_uuid === null) {
            return new Test();
        }

        $afscs = unserialize($afscList ?? '');

        if (!is_array($afscs)) {
            $afscs = [];
        }

        $questions = unserialize($questionList ?? '');

        if (!is_array($questions)) {
            $questions = [];
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $afscArr = $afscCollection->fetchArray($afscs);
        $questionArr = $questionCollection->fetchArrayMixed($questions);

        if ($timeStarted !== null) {
            $timeStarted = \DateTime::createFromFormat(
                DateTimeHelpers::DT_FMT_DB,
                $timeStarted ?? ''
            );
        }

        if ($timeCompleted !== null) {
            $timeCompleted = \DateTime::createFromFormat(
                DateTimeHelpers::DT_FMT_DB,
                $timeCompleted ?? ''
            );
        }

        $test = new Test();
        $test->setUuid($_uuid ?? '');
        $test->setUserUuid($userUuid ?? '');
        $test->setAfscs($afscArr);
        $test->setTimeStarted($timeStarted);
        $test->setTimeCompleted($timeCompleted);
        $test->setQuestions($questionArr);
        $test->setCurrentQuestion($curQuestion ?? 0);
        $test->setNumAnswered($numAnswered ?? 0);
        $test->setScore((float)$score ?? 0.00);
        $test->setCombined((bool)($combined ?? false));
        $test->setArchived((bool)($archived ?? false));

        $this->tests[$_uuid] = $test;

        return $test;
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
                'archived' => $archived
            ];
        }

        $stmt->close();

        if (empty($data)) {
            return [];
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $userTestList = [];
        foreach ($data as $datum) {
            $afscs = unserialize($datum['afscList'] ?? '');

            if (!is_array($afscs)) {
                $afscs = [];
            }

            $questions = unserialize($datum['questionList'] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $afscArr = $afscCollection->fetchArray($afscs);
            $questionArr = $questionCollection->fetchArrayMixed($questions);

            $tTimeStarted = \DateTime::createFromFormat(
                DateTimeHelpers::DT_FMT_DB,
                $datum['timeStarted'] ?? null
            );

            $tTimeCompleted = \DateTime::createFromFormat(
                DateTimeHelpers::DT_FMT_DB,
                $datum['timeCompleted'] ?? null
            );

            $test = new Test();
            $test->setUuid($datum['uuid'] ?? '');
            $test->setUserUuid($datum['userUuid'] ?? '');
            $test->setAfscs($afscArr);
            $test->setTimeStarted(
                $tTimeStarted
                    ? $tTimeStarted
                    : null
            );
            $test->setTimeCompleted(
                $tTimeCompleted
                    ? $tTimeCompleted
                    : null
            );
            $test->setQuestions($questionArr);
            $test->setCurrentQuestion($datum['curQuestion'] ?? 0);
            $test->setNumAnswered($datum['numAnswered'] ?? 0);
            $test->setScore((float)$datum['score'] ?? 0.00);
            $test->setCombined((bool)($datum['combined'] ?? false));
            $test->setArchived((bool)($datum['archived'] ?? false));

            $this->tests[$datum['uuid']] = $test;
            $userTestList[] = $datum['uuid'];
        }

        return array_intersect_key(
            $this->tests,
            array_flip($userTestList)
        );
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
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $data[] = $row;
        }

        $res->free();

        if (empty($data)) {
            return [];
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $testList = [];
        foreach ($data as $datum) {
            $afscs = unserialize($datum['afscList'] ?? '');

            if (!is_array($afscs)) {
                $afscs = [];
            }

            $questions = unserialize($datum['questionList'] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $afscArr = $afscCollection->fetchArray($afscs);
            $questionArr = $questionCollection->fetchArrayMixed($questions);

            $test = new Test();
            $test->setUuid($datum['uuid'] ?? '');
            $test->setUserUuid($datum['userUuid'] ?? '');
            $test->setAfscs($afscArr);

            if ($datum['timeStarted'] !== null) {
                $test->setTimeStarted(
                    \DateTime::createFromFormat(
                        DateTimeHelpers::DT_FMT_DB,
                        $datum['timeStarted'] ?? ''
                    )
                );
            }

            if ($datum['timeCompleted'] !== null) {
                $test->setTimeCompleted(
                    \DateTime::createFromFormat(
                        DateTimeHelpers::DT_FMT_DB,
                        $datum['timeCompleted'] ?? ''
                    )
                );
            }

            $test->setQuestions($questionArr);
            $test->setCurrentQuestion($datum['curQuestion'] ?? 0);
            $test->setNumAnswered($datum['numAnswered'] ?? 0);
            $test->setScore((float)$datum['score'] ?? 0.00);
            $test->setCombined((bool)($datum['combined'] ?? false));
            $test->setArchived((bool)($datum['archived'] ?? false));

            $this->tests[$datum['uuid']] = $test;
            $testList[] = $datum['uuid'];
        }

        return array_intersect_key(
            $this->tests,
            array_flip($testList)
        );
    }

    /**
     * @return TestCollection
     */
    public function reset(): self
    {
        $this->tests = [];

        return $this;
    }

    /**
     * @param Test $test
     */
    public function save(Test $test): void
    {
        if (empty($test->getUuid())) {
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

        $this->tests[$uuid] = $test;
    }

    /**
     * @param array $tests
     */
    public function saveArray(array $tests): void
    {
        if (empty($tests)) {
            return;
        }

        $c = count($tests);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($tests[$i])) {
                continue;
            }

            if (!$tests[$i] instanceof Test) {
                continue;
            }

            $this->save($tests[$i]);
        }
    }
}