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
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\CdcData;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\QuestionAnswers;
use CDCMastery\Models\CdcData\QuestionAnswersCollection;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class OfflineTestCollection
{
    protected mysqli $db;
    protected Logger $log;
    protected AfscCollection $afscs;
    protected CdcDataCollection $cdc_data;
    protected QuestionCollection $questions;
    protected AnswerCollection $answers;
    protected QuestionAnswersCollection $questions_answers;

    public function __construct(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        CdcDataCollection $cdc_data,
        QuestionCollection $questions,
        AnswerCollection $answers,
        QuestionAnswersCollection $questions_answers
    ) {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->afscs = $afscs;
        $this->cdc_data = $cdc_data;
        $this->questions = $questions;
        $this->answers = $answers;
        $this->questions_answers = $questions_answers;
    }

    /**
     * @param array $data
     * @return OfflineTest[]
     */
    private function create_objects(array $data): array
    {
        if (!$data) {
            return [];
        }

        $out = [];
        foreach ($data as $tdata) {
            if (!isset($tdata[ '_uuid' ])) {
                continue;
            }

            $test = new OfflineTest();
            $test->setUuid($tdata[ '_uuid' ]);
            $test->setUserUuid($tdata[ 'userUuid' ]);
            $test->setCdcData($tdata[ 'cdcData' ]);
            $test->setDateCreated(
                DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $tdata[ 'dateCreated' ]
                )
            );

            $out[ $tdata[ '_uuid' ] ] = $test;
        }

        return $out;
    }

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
     * @param string[] $uuids
     */
    public function deleteArray(array $uuids): void
    {
        if (empty($uuids)) {
            return;
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuids
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
DELETE FROM testGeneratorData
WHERE uuid IN ('{$uuidListString}')
SQL;

        $this->db->query($qry);
    }

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

    public function fetch(string $uuid): ?OfflineTest
    {
        if (empty($uuid)) {
            return null;
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

        if ($stmt === false) {
            return null;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return null;
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

        if (!isset($_uuid)) {
            return null;
        }

        $afsc = $this->afscs->fetch($afscUuid);

        $questions = unserialize($questionList ?? '');

        if (!is_array($questions)) {
            return null;
        }

        $questionArr = $this->questions->fetchArray(
            $afsc,
            $questions
        );

        $reordered = array_merge(array_flip($questions),
                                 $questionArr);

        $questionAnswers = $this->questions_answers->fetch($afsc, $reordered);

        $cdcData = new CdcData();
        $cdcData->setAfsc($afsc);
        $cdcData->setQuestionAnswerData($questionAnswers);

        $data = [
            '_uuid' => $_uuid,
            'cdcData' => $cdcData,
            'totalQuestions' => $totalQuestions,
            'userUuid' => $userUuid,
            'dateCreated' => $dateCreated,
        ];

        return $this->create_objects([$data])[ $_uuid ] ?? null;
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
ORDER BY dateCreated DESC
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        if (!$stmt->bind_param('s', $afscUuid) ||
            !$stmt->execute()) {
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
            if (!isset($uuid)) {
                continue;
            }

            $rows[] = [
                'uuid' => $uuid,
                'afscUuid' => $_afscUuid,
                'questionList' => $questionList,
                'totalQuestions' => $totalQuestions,
                'userUuid' => $userUuid,
                'dateCreated' => $dateCreated,
            ];
        }

        $stmt->close();

        if (!$rows) {
            return [];
        }

        $data = [];
        foreach ($rows as $row) {
            $questions = unserialize($row[ 'questionList' ] ?? '');

            if (!is_array($questions)) {
                continue;
            }

            $questionArr = $this->questions->fetchArray(
                $afsc,
                $questions
            );

            $reordered = array_merge(array_flip($questions),
                                     $questionArr);

            $questionAnswers = $this->questions_answers->fetch($afsc, $reordered);

            $cdcData = new CdcData();
            $cdcData->setAfsc($afsc);
            $cdcData->setQuestionAnswerData($questionAnswers);

            $data[] = [
                '_uuid' => $row[ 'uuid' ],
                'cdcData' => $cdcData,
                'totalQuestions' => $row[ 'totalQuestions' ],
                'userUuid' => $row[ 'userUuid' ],
                'dateCreated' => $row[ 'dateCreated' ],
            ];
        }

        return $this->create_objects($data);
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
ORDER BY dateCreated DESC
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        if (!$stmt->bind_param('s', $userUuid) ||
            !$stmt->execute()) {
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
            if (!isset($uuid)) {
                continue;
            }

            $rows[] = [
                'uuid' => $uuid,
                'afscUuid' => $afscUuid,
                'questionList' => $questionList,
                'totalQuestions' => $totalQuestions,
                'userUuid' => $_userUuid,
                'dateCreated' => $dateCreated,
            ];
        }

        $stmt->close();

        if (!$rows) {
            return [];
        }

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);

        $data = [];
        foreach ($rows as $row) {
            $questions = unserialize($row[ 'questionList' ] ?? '');

            $afsc = $afscs[ $row[ 'afscUuid' ] ];

            if ($afsc === null ||
                !is_array($questions)) {
                continue;
            }

            $questionArr = $this->questions->fetchArray(
                $afsc,
                $questions
            );

            $reordered = array_merge(array_flip($questions),
                                     $questionArr);

            $questionAnswers = $this->questions_answers->fetch($afsc, $reordered);

            $cdcData = new CdcData();
            $cdcData->setAfsc($afsc);
            $cdcData->setQuestionAnswerData($questionAnswers);

            $data[] = [
                '_uuid' => $row[ 'uuid' ],
                'cdcData' => $cdcData,
                'totalQuestions' => $row[ 'totalQuestions' ],
                'userUuid' => $row[ 'userUuid' ],
                'dateCreated' => $row[ 'dateCreated' ],
            ];
        }

        return $this->create_objects($data);
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
ORDER BY dateCreated DESC
SQL;

        $res = $this->db->query($qry);

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        if (!$rows) {
            return [];
        }

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);

        $data = [];
        foreach ($rows as $row) {
            $questions = unserialize($row[ 'questionList' ] ?? '');

            $afsc = $afscs[ $row[ 'afscUuid' ] ];

            if ($afsc === null ||
                !is_array($questions)) {
                continue;
            }

            $questionArr = $this->questions->fetchArray(
                $afsc,
                $questions
            );

            $reordered = array_merge(array_flip($questions),
                                     $questionArr);

            $questionAnswers = $this->questions_answers->fetch($afsc, $reordered);

            $cdcData = new CdcData();
            $cdcData->setAfsc($afsc);
            $cdcData->setQuestionAnswerData($questionAnswers);

            $data[] = [
                '_uuid' => $row[ 'uuid' ],
                'cdcData' => $cdcData,
                'totalQuestions' => $row[ 'totalQuestions' ],
                'userUuid' => $row[ 'userUuid' ],
                'dateCreated' => $row[ 'dateCreated' ],
            ];
        }

        return $this->create_objects($data);
    }

    /**
     * @param OfflineTest $offlineTest
     */
    public function save(OfflineTest $offlineTest): void
    {
        if (!$offlineTest->getUuid()) {
            return;
        }

        $uuid = $offlineTest->getUuid();
        $userUuid = $offlineTest->getUserUuid();
        $afscUuid = $offlineTest->getCdcData()->getAfsc()->getUuid();
        $questionList = serialize(
            array_map(static function (QuestionAnswers $v): string {
                return $v->getQuestion()->getUuid();
            }, $offlineTest->getCdcData()->getQuestionAnswerData())
        );
        $numQuestions = $offlineTest->getNumQuestions();
        $dateCreated = $offlineTest->getDateCreated()->format(
            DateTimeHelpers::DT_FMT_DB
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

        if ($stmt === false) {
            return;
        }

        if (!$stmt->bind_param('sssiss',
                               $uuid,
                               $afscUuid,
                               $questionList,
                               $numQuestions,
                               $userUuid,
                               $dateCreated) ||
            !$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param OfflineTest[] $offlineTests
     */
    public function saveArray(array $offlineTests): void
    {
        if (empty($offlineTests)) {
            return;
        }

        foreach ($offlineTests as $offlineTest) {
            if (!$offlineTest instanceof OfflineTest) {
                continue;
            }

            $this->save($offlineTest);
        }
    }
}