<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 8:34 PM
 */

namespace CDCMastery\Models\Tests\Offline;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Users\User;
use Monolog\Logger;

class OfflineTestCollection
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
     * @var OfflineTest[]
     */
    private $tests = [];

    /**
     * OfflineTestCollection constructor.
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
DELETE FROM testGeneratorData
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
DELETE FROM testGeneratorData
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

        $userUuid = $this->db->real_escape_string(
            $user->getUuid()
        );

        $qry = <<<SQL
DELETE FROM testGeneratorData
WHERE userUUID = '{$userUuid}'
SQL;

        $this->db->query($qry);
    }

    /**
     * @param Afsc $afsc
     */
    public function deleteAllByAfsc(Afsc $afsc): void
    {
        if (empty($afsc->getUuid())) {
            return;
        }

        $afscUuid = $this->db->real_escape_string(
            $afsc->getUuid()
        );

        $qry = <<<SQL
DELETE FROM testGeneratorData
WHERE afscUUID = '{$afscUuid}'
SQL;

        $this->db->query($qry);
    }

    /**
     * @param string $uuid
     * @return OfflineTest
     */
    public function fetch(string $uuid): OfflineTest
    {
        if (empty($uuid)) {
            return new OfflineTest();
        }

        $qry = <<<SQL
SELECT 
  uuid,
  afscUUID,
  questionList,
  totalQuestions,
  userUUID,
  dateCreated
FROM testGeneratorData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new OfflineTest();
        }

        $stmt->bind_result(
            $_uuid,
            $afscUuid,
            $questionList,
            $totalQuestions,
            $userUuid,
            $dateCreated
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($_uuid) || is_null($_uuid)) {
            return new OfflineTest();
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $afsc = $afscCollection->fetch($afscUuid);

        $questions = unserialize($questionList ?? '');

        if (!is_array($questions)) {
            $questions = [];
        }

        $questionArr = $questionCollection->fetchArray(
            $afsc,
            $questions
        );

        $offlineTest = new OfflineTest();
        $offlineTest->setUuid($_uuid ?? '');
        $offlineTest->setUserUuid($userUuid ?? '');
        $offlineTest->setAfsc($afsc);
        $offlineTest->setQuestions($questionArr);
        $offlineTest->setDateCreated(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $dateCreated ?? ''
            )
        );

        $this->tests[$uuid] = $offlineTest;

        return $offlineTest;
    }

    /**
     * @param Afsc $afsc
     * @return array
     */
    public function fetchAllByAfsc(Afsc $afsc): array
    {
        if (empty($afsc->getUuid())) {
            return [];
        }

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
SELECT 
  uuid,
  afscUUID,
  questionList,
  totalQuestions,
  userUUID,
  dateCreated
FROM testGeneratorData
WHERE afscUUID = ?
ORDER BY uuid ASC
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $afscUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $uuid,
            $_afscUuid,
            $questionList,
            $totalQuestions,
            $userUuid,
            $dateCreated
        );

        $rows = [];
        while ($stmt->fetch()) {
            if (!isset($uuid) || is_null($uuid)) {
                continue;
            }

            $rows[] = [
                'uuid' => $uuid,
                'afscUuid' => $_afscUuid,
                'questionList' => $questionList,
                'totalQuestions' => $totalQuestions,
                'userUuid' => $userUuid,
                'dateCreated' => $dateCreated
            ];
        }

        if (empty($rows)) {
            return [];
        }

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $uuidList = [];
        $c = count($rows);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($rows[$i]) || !isset($rows[$i]['uuid']) || is_null($rows[$i]['uuid'])) {
                continue;
            }

            $questions = unserialize($rows[$i]['questionList'] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $questionArr = $questionCollection->fetchArray(
                $afsc,
                $questions
            );

            $offlineTest = new OfflineTest();
            $offlineTest->setUuid($rows[$i]['uuid'] ?? '');
            $offlineTest->setUserUuid($rows[$i]['userUuid'] ?? '');
            $offlineTest->setAfsc($afsc);
            $offlineTest->setQuestions($questionArr);
            $offlineTest->setDateCreated(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $rows[$i]['dateCreated'] ?? ''
                )
            );

            $this->tests[$rows[$i]['uuid']] = $offlineTest;
            $uuidList[] = $rows[$i]['uuid'];
        }

        $stmt->close();

        return array_intersect_key(
            $this->tests,
            array_flip($uuidList)
        );
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

        $userUuid = $user->getUuid();

        $qry = <<<SQL
SELECT 
  uuid,
  afscUUID,
  questionList,
  totalQuestions,
  userUUID,
  dateCreated
FROM testGeneratorData
WHERE userUUID = ?
ORDER BY uuid ASC
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $uuid,
            $afscUuid,
            $questionList,
            $totalQuestions,
            $_userUuid,
            $dateCreated
        );

        $rows = [];
        while ($stmt->fetch()) {
            if (!isset($uuid) || is_null($uuid)) {
                continue;
            }

            $rows[] = [
                'uuid' => $uuid,
                'afscUuid' => $afscUuid,
                'questionList' => $questionList,
                'totalQuestions' => $totalQuestions,
                'userUuid' => $_userUuid,
                'dateCreated' => $dateCreated
            ];
        }

        if (empty($rows)) {
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

        $uuidList = [];
        $c = count($rows);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($rows[$i])) {
                continue;
            }

            $afsc = $afscCollection->fetch($rows[$i]['afscUuid'] ?? '');

            $questions = unserialize($rows[$i]['questionList'] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $questionArr = $questionCollection->fetchArray(
                $afsc,
                $questions
            );

            $offlineTest = new OfflineTest();
            $offlineTest->setUuid($rows[$i]['uuid'] ?? '');
            $offlineTest->setUserUuid($rows[$i]['userUuid'] ?? '');
            $offlineTest->setAfsc($afsc);
            $offlineTest->setQuestions($questionArr);
            $offlineTest->setDateCreated(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $rows[$i]['dateCreated'] ?? ''
                )
            );

            $this->tests[$uuid] = $offlineTest;
            $uuidList[] = $uuid;
        }

        $stmt->close();

        return array_intersect_key(
            $this->tests,
            array_flip($uuidList)
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
  afscUUID,
  questionList,
  totalQuestions,
  userUUID,
  dateCreated
FROM testGeneratorData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;
        
        $res = $this->db->query($qry);
        
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $rows[] = $row;
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

        $uuidList = [];
        $c = count($rows);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($rows[$i]) || !isset($rows[$i]['uuid']) || is_null($rows[$i]['uuid'])) {
                continue;
            }

            $afsc = $afscCollection->fetch($rows[$i]['afscUuid'] ?? '');
            
            $questions = unserialize($rows[$i]['questionList'] ?? '');

            if (!is_array($questions)) {
                $questions = [];
            }

            $questionArr = $questionCollection->fetchArray(
                $afsc,
                $questions
            );

            $offlineTest = new OfflineTest();
            $offlineTest->setUuid($rows[$i]['uuid'] ?? '');
            $offlineTest->setUserUuid($rows[$i]['userUuid'] ?? '');
            $offlineTest->setAfsc($afsc);
            $offlineTest->setQuestions($questionArr);
            $offlineTest->setDateCreated(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $rows[$i]['dateCreated'] ?? ''
                )
            );

            $this->tests[$rows[$i]['uuid']] = $offlineTest;
            $uuidList[] = $rows[$i]['uuid'];
        }

        return array_intersect_key(
            $this->tests,
            array_flip($uuidList)
        );
    }

    /**
     * @return OfflineTestCollection
     */
    public function reset(): self
    {
        $this->tests = [];

        return $this;
    }

    /**
     * @param OfflineTest $offlineTest
     */
    public function save(OfflineTest $offlineTest): void
    {
        if (empty($offlineTest->getUuid())) {
            return;
        }
        
        $uuid = $offlineTest->getUuid();
        $userUuid = $offlineTest->getUserUuid();
        $afscUuid = $offlineTest->getAfsc()->getUuid();
        $questionList = serialize(
            QuestionHelpers::listUuid(
                $offlineTest->getQuestions()
            )
        );
        $numQuestions = $offlineTest->getNumQuestions();
        $dateCreated = $offlineTest->getDateCreated()->format(
            DateTimeHelpers::FMT_DATABASE
        );
        
        $qry = <<<SQL
INSERT INTO testGeneratorData
  (
    uuid, 
    afscUUID, 
    questionList, 
    totalQuestions, 
    userUUID, 
    dateCreated
  )
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid), 
  afscUUID=VALUES(afscUUID), 
  questionList=VALUES(questionList), 
  totalQuestions=VALUES(totalQuestions), 
  userUUID=VALUES(userUUID), 
  dateCreated=VALUES(dateCreated)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sssiss',
            $uuid,
            $afscUuid,
            $questionList,
            $numQuestions,
            $userUuid,
            $dateCreated
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->tests[$uuid] = $offlineTest;
    }

    /**
     * @param OfflineTest[] $offlineTests
     */
    public function saveArray(array $offlineTests): void
    {
        if (empty($offlineTests)) {
            return;
        }

        $c = count($offlineTests);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($offlineTests[$i])) {
                continue;
            }

            if (!$offlineTests[$i] instanceof OfflineTest) {
                continue;
            }

            $this->save($offlineTests[$i]);
        }
    }
}