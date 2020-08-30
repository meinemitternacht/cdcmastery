<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 2/28/2019
 * Time: 8:30 PM
 */

/** @var \DI\Container $container */
$container = require "../src/CDCMastery/Bootstrap.php";

try {
    $db = $container->get(\mysqli::class);
} catch (\Throwable $e) {
    echo "could not open database\n";
    exit(1);
}

$queries = <<<SQL
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
SELECT
  testManager.testUUID,
  testManager.userUUID,
  testManager.afscList,
  testManager.timeStarted,
  NULL,
  testManager.questionList,
  testManager.currentQuestion,
  testManager.questionsAnswered,
  0,
  0.00,
  testManager.combinedTest,
  0
FROM testManager;
-- SPLIT ;;
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
SELECT
  testHistory.uuid,
  testHistory.userUUID,
  testHistory.afscList,
  testHistory.testTimeStarted,
  testHistory.testTimeCompleted,
  NULL,
  0 AS curQuestion,
  testHistory.totalQuestions AS numAnswered,
  testHistory.questionsMissed,
  CAST(testHistory.testScore AS DECIMAL(5,2)),
  testHistory.afscList LIKE 'a:1%',
  testHistory.testArchived IS NOT NULL
FROM testHistory;
-- SPLIT ;;
DELETE FROM testData WHERE testUUID NOT IN (
  SELECT uuid FROM testCollection
);
-- SPLIT ;;
DELETE FROM testData WHERE questionUUID NOT IN (
  SELECT uuid FROM questionData
);
-- SPLIT ;;
DELETE FROM testData WHERE answerUUID NOT IN (
  SELECT uuid FROM answerData
);
-- SPLIT ;;
ALTER TABLE cdcmastery_main.testData
ADD CONSTRAINT testData_questionData_uuid_fk
FOREIGN KEY (questionUUID) REFERENCES questionData (uuid) ON DELETE CASCADE ON UPDATE CASCADE;
-- SPLIT ;;
ALTER TABLE cdcmastery_main.testData
ADD CONSTRAINT testData_answerData_uuid_fk
FOREIGN KEY (answerUUID) REFERENCES answerData (uuid) ON DELETE CASCADE ON UPDATE CASCADE;
-- SPLIT ;;
ALTER TABLE cdcmastery_main.testData
ADD CONSTRAINT testData_testCollection_uuid_fk
FOREIGN KEY (testUUID) REFERENCES testCollection (uuid) ON DELETE CASCADE ON UPDATE CASCADE;
-- SPLIT ;;
ALTER TABLE cdcmastery_main.afscList CHANGE afscName name VARCHAR(32) NOT NULL;
-- SPLIT ;;
ALTER TABLE cdcmastery_main.afscList CHANGE afscDescription description VARCHAR(255);
-- SPLIT ;;
ALTER TABLE cdcmastery_main.afscList CHANGE afscVersion version VARCHAR(255);
-- SPLIT ;;
ALTER TABLE cdcmastery_main.afscList CHANGE afscFOUO fouo INT(1) NOT NULL DEFAULT 0;
-- SPLIT ;;
ALTER TABLE cdcmastery_main.afscList CHANGE afscHidden hidden INT(1) NOT NULL DEFAULT 0;
-- SPLIT ;;
alter table afscList add obsolete int(1) default 0 not null;
-- SPLIT ;;
alter table afscList DROP oldID;
-- SPLIT ;;
alter table questionData
	add disabled tinyint default 0 not null;
-- SPLIT ;;
create index questionData_disabled_index
	on questionData (disabled);
-- SPLIT ;;
alter table afscList modify version varchar(191) null;
-- SPLIT ;;
alter table afscList
	add editCode VARCHAR(191) default NULL null after version;
-- SPLIT ;;
drop index afscDescription on afscList;
-- SPLIT ;;
alter table afscList modify description MEDIUMTEXT null;
-- SPLIT ;;
drop index afscName on afscList;
-- SPLIT ;;
create unique index afscName
	on afscList (name, editCode);
SQL;

$queries = explode('-- SPLIT ;;', $queries);

if (!is_array($queries) || \count($queries) === 0) {
    echo "no queries to execute\n";
    exit(1);
}

echo (new DateTime())->format('Y-m-d H:i:s') . "  starting execution\n";
foreach ($queries as $query) {
    if (trim($query) === '') {
        continue;
    }

    if (!$db->query($query)) {
        echo (new DateTime())->format('Y-m-d H:i:s') . "  ";
        echo "failed to execute query: {$query}\n\nerror: {$db->error}\n";
        exit(1);
    }

    echo (new DateTime())->format('Y-m-d H:i:s') . "  ";
    echo "executed query: {$query}\n";
}

echo (new DateTime())->format('Y-m-d H:i:s') . "  finished execution\n";