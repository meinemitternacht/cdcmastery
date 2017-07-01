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

        if (!isset($_uuid) || is_null($_uuid)) {
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

        $test = new Test();
        $test->setUuid($_uuid ?? '');
        $test->setUserUuid($userUuid ?? '');
        $test->setAfscs($afscArr);
        $test->setTimeStarted(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $timeStarted ?? ''
            )
        );
        $test->setTimeCompleted(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $timeCompleted ?? ''
            )
        );
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
     * @return array
     */
    public function fetchAllByUser(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
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
WHERE userUuid = ?
ORDER BY uuid ASC
SQL;

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

            $test = new Test();
            $test->setUuid($datum['uuid'] ?? '');
            $test->setUserUuid($datum['userUuid'] ?? '');
            $test->setAfscs($afscArr);
            $test->setTimeStarted(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $datum['timeStarted'] ?? ''
                )
            );
            $test->setTimeCompleted(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $datum['timeCompleted'] ?? ''
                )
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
     * @return array
     */
    public function fetchArray(array $uuidList): array
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
WHERE userUuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
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

            $test = new Test();
            $test->setUuid($datum['uuid'] ?? '');
            $test->setUserUuid($datum['userUuid'] ?? '');
            $test->setAfscs($afscArr);

            if (!is_null($datum['timeStarted'])) {
                $test->setTimeStarted(
                    \DateTime::createFromFormat(
                        DateTimeHelpers::FMT_DATABASE,
                        $datum['timeStarted'] ?? ''
                    )
                );
            }

            if (!is_null($datum['timeCompleted'])) {
                $test->setTimeCompleted(
                    \DateTime::createFromFormat(
                        DateTimeHelpers::FMT_DATABASE,
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
            $userTestList[] = $datum['uuid'];
        }

        return array_intersect_key(
            $this->tests,
            array_flip($userTestList)
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
        $timeStarted = is_null($test->getTimeStarted())
            ? null
            : $test->getTimeStarted()->format(
                DateTimeHelpers::FMT_DATABASE
            );
        $timeCompleted = is_null($test->getTimeCompleted())
            ? null
            : $test->getTimeCompleted()->format(
                DateTimeHelpers::FMT_DATABASE
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