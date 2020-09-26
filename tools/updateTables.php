<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 2/28/2019
 * Time: 8:30 PM
 */

use DI\Container;

/** @var Container $container */
$container = require "../src/CDCMastery/Bootstrap.php";

try {
    $db = $container->get(mysqli::class);
} catch (Throwable $e) {
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
CREATE INDEX testCollection_score_index
	ON testCollection (score);
-- SPLIT ;;
CREATE INDEX testCollection_timeCompleted_index
	ON testCollection (timeCompleted);
-- SPLIT ;;
CREATE INDEX testCollection_timeStarted_index
	ON testCollection (timeStarted);
-- SPLIT ;;
DROP TABLE testManager;
-- SPLIT ;;
DROP TABLE testHistory;
-- SPLIT ;;
CREATE INDEX testGeneratorData_dateCreated_index
	ON testGeneratorData (dateCreated);
-- SPLIT ;;
DELETE FROM testData
WHERE testUUID NOT IN (SELECT uuid FROM testCollection)
   OR questionUUID NOT IN (SELECT uuid FROM questionData)
   OR answerUUID NOT IN (SELECT uuid FROM answerData);
-- SPLIT ;;
ALTER TABLE testData
    ADD CONSTRAINT testData_questionData_uuid_fk
        FOREIGN KEY (questionUUID) REFERENCES questionData (uuid) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT testData_answerData_uuid_fk
        FOREIGN KEY (answerUUID) REFERENCES answerData (uuid) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT testData_testCollection_uuid_fk
        FOREIGN KEY (testUUID) REFERENCES testCollection (uuid) ON DELETE CASCADE ON UPDATE CASCADE;
-- SPLIT ;;
DELETE FROM questionData
WHERE afscUUID NOT IN
      (SELECT uuid FROM afscList ORDER BY uuid);
-- SPLIT ;;
ALTER TABLE questionData
	ADD disabled TINYINT(1) DEFAULT 0 NOT NULL,
    ADD CONSTRAINT questionData_afscList_uuid_fk
        FOREIGN KEY (afscUUID) REFERENCES afscList (uuid)
            ON UPDATE CASCADE ON DELETE CASCADE;
-- SPLIT ;;
DELETE FROM answerData
WHERE questionUUID NOT IN
      (SELECT uuid FROM questionData ORDER BY uuid);
-- SPLIT ;;
ALTER TABLE answerData
	ADD CONSTRAINT answerData_questionData_uuid_fk
		FOREIGN KEY (questionUUID) REFERENCES questionData (uuid)
			ON UPDATE CASCADE ON DELETE CASCADE;
-- SPLIT ;;
CREATE INDEX questionData_disabled_index
	ON questionData (disabled);
-- SPLIT ;;
DROP INDEX afscName ON afscList;
-- SPLIT ;;
DROP INDEX afscDescription ON afscList;
-- SPLIT ;;
ALTER TABLE afscList
    CHANGE afscName name VARCHAR(32) NOT NULL,
    CHANGE afscDescription description MEDIUMTEXT NULL,
    CHANGE afscVersion version VARCHAR(191) NULL,
    CHANGE afscFOUO fouo INT(1) NOT NULL DEFAULT 0,
    CHANGE afscHidden hidden INT(1) NOT NULL DEFAULT 0,
    ADD obsolete INT(1) DEFAULT 0 NOT NULL,
    DROP oldID,
    ADD editCode VARCHAR(191) DEFAULT NULL NULL AFTER version;
-- SPLIT ;;
CREATE INDEX supervisorUUID
	ON userSupervisorAssociations (supervisorUUID);
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations DROP FOREIGN KEY userSupervisorAssociations_ibfk_1;
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations DROP FOREIGN KEY userSupervisorAssociations_ibfk_2;
-- SPLIT ;;
DROP INDEX userSupervisor ON userSupervisorAssociations;
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations DROP PRIMARY KEY;
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations DROP COLUMN uuid;
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations
	ADD CONSTRAINT userSupervisor
		PRIMARY KEY (supervisorUUID, userUUID);
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations
	ADD CONSTRAINT userSupervisorAssociations_userData_uuid_fk
		FOREIGN KEY (supervisorUUID) REFERENCES userData (uuid)
			ON UPDATE CASCADE ON DELETE CASCADE;
-- SPLIT ;;
ALTER TABLE userSupervisorAssociations
	ADD CONSTRAINT userSupervisorAssociations_userData_uuid_fk_2
		FOREIGN KEY (userUUID) REFERENCES userData (uuid)
			ON UPDATE CASCADE ON DELETE CASCADE;
-- SPLIT ;;
ALTER TABLE userTrainingManagerAssociations DROP PRIMARY KEY;
-- SPLIT ;;
ALTER TABLE userTrainingManagerAssociations DROP COLUMN uuid;
-- SPLIT ;;
CREATE TEMPORARY TABLE user_tm_assoc_tmp SELECT * FROM userTrainingManagerAssociations LIMIT 0;
-- SPLIT ;;
INSERT INTO user_tm_assoc_tmp SELECT DISTINCT trainingManagerUUID, userUUID FROM userTrainingManagerAssociations;
-- SPLIT ;;
TRUNCATE userTrainingManagerAssociations;
-- SPLIT ;;
INSERT INTO userTrainingManagerAssociations SELECT * FROM user_tm_assoc_tmp;
-- SPLIT ;;
DROP TEMPORARY TABLE user_tm_assoc_tmp;
-- SPLIT ;;
ALTER TABLE userTrainingManagerAssociations
	ADD CONSTRAINT userTrainingManagerAssociations_pk
		PRIMARY KEY (trainingManagerUUID, userUUID);
-- SPLIT ;;
DELETE FROM userSupervisorAssociations WHERE supervisorUUID = userUUID;
-- SPLIT ;;
DELETE FROM userTrainingManagerAssociations WHERE trainingManagerUUID = userUUID;
-- SPLIT ;;
ALTER TABLE userAFSCAssociations DROP PRIMARY KEY;
-- SPLIT ;;
ALTER TABLE userAFSCAssociations DROP COLUMN uuid;
-- SPLIT ;;
ALTER TABLE userAFSCAssociations
	ADD CONSTRAINT userAFSCAssociations_pk
		PRIMARY KEY (userUUID, afscUUID);
-- SPLIT ;;
ALTER TABLE userAFSCAssociations DROP key user_afsc;
-- SPLIT ;;
DROP INDEX roleType ON roleList;
-- SPLIT ;;
UPDATE roleList SET roleType = 'super_admin' WHERE uuid = '63b44356-797f-476e-9777-07ca5d41f5e4';
-- SPLIT ;;
CREATE UNIQUE INDEX roleType
	on roleList (roleType);
-- SPLIT ;;
ALTER TABLE queueRoleAuthorization DROP PRIMARY KEY;
-- SPLIT ;;
ALTER TABLE queueRoleAuthorization DROP COLUMN uuid;
-- SPLIT ;;
ALTER TABLE queueRoleAuthorization
	ADD CONSTRAINT queueRoleAuthorization_pk
		PRIMARY KEY (userUUID);
-- SPLIT ;;
ALTER TABLE queueRoleAuthorization DROP key userUUID_2;
-- SPLIT ;;
ALTER TABLE flashCardCategories DROP COLUMN categoryPrivate;
-- SPLIT ;;
DROP TABLE flashCardSessions;
-- SPLIT ;;
INSERT INTO userData
    (uuid, userFirstName, userLastName, userHandle, userPassword, userLegacyPassword, userEmail, userRank, userDateRegistered, userLastLogin, userLastActive, userTimeZone, userRole, userOfficeSymbol, userBase, userDisabled, reminderSent) 
VALUES
    (0x39613037376235332D653433302D346334342D383362612D613636653365636361313137, 'System', 'User', 'system.user', null, null, 'root@localhost', 'SYS', '2012-02-14 00:00:01', '2012-02-14 00:00:01', '2012-02-14 00:00:01', 'America/New_York', 0x36336234343335362D373937662D343736652D393737372D303763613564343166356534, null, 0x34623734383362312D306334382D343930322D613335362D656136646164383563356261, 0, null);
-- SPLIT ;;
INSERT INTO baseList
    (uuid, baseName)
VALUES
    (0x38383639626232372D303864352D343163632D623431632D393966393461393361373531, 'Abston Air National Guard Station'),
    (0x66613134313934302D356665632D343930652D386432392D646364656536343965333132, 'Air Force Academy'),
    (0x36613337386463312D356335332D343264322D383939612D303164373763383364383362, 'Al Dhafra Air Base'),
    (0x62653730663636332D626662632D346633362D383430312D303131336465336566646639, 'Al Udeid Air Base'),
    (0x38666133383435342D643630382D343531652D383933632D356661646530613362643732, 'Ali Al Salem Air Base'),
    (0x30656432303230392D356364312D346165342D616432612D363633613232323466616562, 'Altus Air Force Base'),
    (0x38613331333566612D306136662D346661302D393935622D383337343738626130363564, 'Andersen Air Force Base'),
    (0x35383135616330632D366361362D343835382D383365322D623866626566333134303835, 'Arnold Air Force Base'),
    (0x36633037366336642D613037372D346635662D383337332D376631363339663162666633, 'Atlantic City Air National Guard Base'),
    (0x37393065343432302D326332372D343331372D393637332D653136613130626239333265, 'Aviano Air Base'),
    (0x61386131333438362D626465662D346165392D393162632D343865653037663561663831, 'Azraq Air Base'),
    (0x38623133643732362D356166392D343364612D383361342D313837396237633762643230, 'Bagram Airfield'),
    (0x36343362636263382D386361622D346332332D623233652D313164376235633938316163, 'Bangor Air National Guard Base'),
    (0x38343636623561332D363662322D343862392D613530382D646239633739343865653036, 'Barksdale Air Force Base'),
    (0x32626138666361382D363065312D346132382D393963322D363133363036646533656461, 'Barnes Air National Guard Base'),
    (0x33313232393336352D383834612D343664612D393466332D663062623862663039623766, 'Beale Air Force Base'),
    (0x33303739306230342D323632312D346263312D616262392D313932353166666366323736, 'Beightler Armory'),
    (0x64623334613566382D663066302D346465322D383839662D396264636233373636383163, 'Berry Field Air National Guard Base'),
    (0x65636566393566322D656162662D343265652D623631342D613538363839633731393137, 'Birmingham Air National Guard Base'),
    (0x30356365363931312D633963612D346265332D613666382D366561623564663131643436, 'Bradley Air National Guard Base'),
    (0x64396130636538372D663636372D343939372D386433382D633937646261303836333035, 'Brooks City-Base'),
    (0x30353132313637302D316133382D343661612D396135662D636232313265633365336436, 'Büchel Air Base'),
    (0x35373132396166352D366639392D343837322D623866372D316365646138663536393138, 'Buckley Air Force Base'),
    (0x39623364396632312D336561302D343334322D386137362D336532393834376563663266, 'Burlington Air National Guard Base'),
    (0x39323137373063652D363135662D346638382D623063382D643732663334373538303234, 'Camp Mabry'),
    (0x33646536383831382D336165342D343966312D383137302D613338656665636236363137, 'Cannon Air Force Base'),
    (0x37623262633530362D396635372D346335662D623933332D343039396263393761633332, 'Cavalier Air Force Station'),
    (0x33656330346537632D363937652D343063342D626364322D316563633936303337396637, 'Channel Islands Air National Guard Station'),
    (0x39363430386233632D353339322D343562352D383963302D623036306230323761646137, 'Charleston Air National Guard Base'),
    (0x34363537363064632D353562342D346463642D383238302D373161613732383736666662, 'Charlotte Air National Guard Base'),
    (0x31323961363134312D613764362D346335642D393235382D373061353835393866336362, 'Cheyenne Air National Guard Base'),
    (0x39393535613932392D623463342D343530352D613334372D653966323036373366343861, 'Chievres Air Base'),
    (0x32353637306261342D333831632D346365642D616234622D363162316633643966383837, 'Clear Air Force Station'),
    (0x63393332666131362D386233342D343732392D626139342D643931663061656138323431, 'Columbus Air Force Base'),
    (0x64333866393166382D353631382D343732362D623164342D633033363861613035353732, 'Creech Air Force Base'),
    (0x64333533306565332D656364382D343734312D613431632D303163333738353838353062, 'Dannelly Field'),
    (0x34373632636434362D613031662D343434652D396231662D313261316536383264623366, 'Davis-Monthan Air Force Base'),
    (0x64323638313861652D623463352D346166362D623763632D666433326631663832353834, 'Des Moines Air National Guard Base'),
    (0x32643633646563392D303635612D346633662D393437612D346239376539383735653632, 'Dobbins Air Reserve Base'),
    (0x34626162633966662D633238392D343533362D613431392D633930306634323839376535, 'Dover Air Force Base'),
    (0x39343234346162652D323465372D343831632D383931332D653436353830303036393335, 'Duke Field'),
    (0x34396638393963662D613365652D346430372D623762332D393830363166306335363763, 'Duluth Air National Guard Base'),
    (0x66653261363037362D613735612D343537382D383861392D376238633337343265333339, 'Dyess Air Force Base'),
    (0x32306535323631312D393239352D346434302D613166392D626132383539303732313334, 'Edwards Air Force Base'),
    (0x62383833616532622D363563652D343366322D386439662D633330666236623561333763, 'Eglin Air Force Base'),
    (0x30326433666633622D306133622D346533332D613037302D393930643266396264623330, 'Eielson Air Force Base'),
    (0x35623031343433642D643232312D343765392D626266352D363339306235393361393436, 'Ellington Field Joint Reserve Base'),
    (0x61663861656230642D623861622D343963312D613436322D346563316139316165643839, 'Ellsworth Air Force Base'),
    (0x36363136376261632D383934612D343661322D393565352D666230306364633764656661, 'Elmendorf Air Force Base'),
    (0x31643533633064362D633631332D343765362D623962612D663230666635616233326138, 'Fairchild Air Force Base'),
    (0x33646263353465382D636334642D343130312D613432332D636336653735663637666438, 'Fargo Air National Guard Base'),
    (0x36343661373731392D306635652D346137372D623134322D346336643862613265626130, 'Forbes Field Air National Guard Base'),
    (0x38366436313037392D326431392D343864652D393261322D613761396435343836346338, 'Fort Jackson'),
    (0x30303632353636372D373335312D343031642D623164302D653135666362346130663965, 'Fort Meade'),
    (0x62363331333463382D656235662D343962302D393338332D353231306636333766383862, 'Fort Smith Air National Guard Station'),
    (0x31636639633133612D393231342D343061362D393066312D653431326333653234653861, 'Fort Wayne Air National Guard Station'),
    (0x63613661656338302D333232662D343034352D623036622D373834333065623039343463, 'Francis E. Warren Air Force Base'),
    (0x36316363336637662D303164362D343933382D613339372D326362616336363733333737, 'Francis S. Gabreski Air National Guard Base'),
    (0x66346665396135372D353261642D343966322D613966362D343637626530336239366234, 'Fresno Air National Guard Base'),
    (0x33303663306535612D663936362D346561352D623436392D613037356338303662313663, 'Garland Air National Guard Station'),
    (0x35636433383366392D383837372D343764372D396364382D663261656564366235333432, 'General Mitchell Air National Guard Base'),
    (0x34356332646363642D313830302D346537322D623235352D636163336164393033323364, 'Ghedi Air Base'),
    (0x35626261623236302D663733392D346636362D386366632D303864646265616333663464, 'Goodfellow Air Force Base'),
    (0x65316366363733322D323866362D346334312D383730612D383232623138366431373938, 'Gowen Field Air National Guard Base'),
    (0x33356434616231392D306531352D343639652D613362652D313932636435326266323536, 'Grand Forks Air Force Base'),
    (0x36323239666433342D616131622D343663342D386461332D656166393039626234383033, 'Great Falls Air National Guard Base'),
    (0x34666464623965322D643339382D346334322D623765312D313164373831666239366465, 'Grissom Joint Air Reserve Base'),
    (0x39646439353538622D356565302D346633322D393138372D636563353036636565633036, 'Hancock Field Air National Guard Base'),
    (0x31656533663364652D333161302D343363362D393137642D636336363730613936303237, 'Hanscom Air Force Base'),
    (0x63343166393930372D393137392D343635372D613333612D373935383835333637303361, 'Harrisburg Air National Guard Base'),
    (0x63303764633931312D306539302D343837332D393836312D636132353437663031633263, 'Hill Air Force Base'),
    (0x36356133306231612D366564632D346163342D393235622D303031316566333434313833, 'Holloman Air Force Base'),
    (0x64386264326337632D643061342D343832352D386236642D666163363935376165303961, 'Homestead Air Reserve Base'),
    (0x64353334376539652D363230322D343361622D613066372D333062663564386331346630, 'Hurlburt Field'),
    (0x38396661663930322D623239622D343666392D623032352D663838316530316434333565, 'Incirlik Air Base'),
    (0x39663065313531342D393639612D343564322D623930302D353263346461366138656633, 'Izmir Air Station'),
    (0x32656438373663322D353963612D346566302D383134612D313033336334633438383065, 'Jackson Air National Guard Base'),
    (0x34646630316535652D363337632D343566392D623032642D346133306132373130396330, 'Jacksonville Air National Guard Base'),
    (0x39333931393765362D346261392D343066332D623034622D323066353435656566373839, 'Joe Foss Field Air National Guard Station'),
    (0x62643537363432392D646131352D343038622D623731662D356235653264643134623436, 'Joint Base Anacostia-Bolling'),
    (0x31306630386165302D663734342D343237642D396138632D366162663230663539363966, 'Joint Base Andrews'),
    (0x32326536346138612D646461652D343135382D623533612D343830373135343834666461, 'Joint Base Charleston'),
    (0x38396163626432342D353137632D346433332D383630642D383865616138366465353934, 'Joint Base Elmendorf-Richardson'),
    (0x30343038396135392D343331362D343732652D613438352D666436333633613839366137, 'Joint Base Langley-Eustis'),
    (0x33323233303432362D643538342D346139382D613337632D636131303931323464383161, 'Joint Base Lewis-McChord'),
    (0x39376432653138652D386439632D346564392D623231362D316436383634363532313762, 'Joint Base McGuire-Dix-Lakehurst'),
    (0x61383439613037362D323063622D343964382D623439642D383933393466323434363766, 'Joint Base Pearl Harbor-Hickam'),
    (0x32333963383832372D333264322D343738312D383563392D323165396635353835396431, 'Joint Base San Antonio'),
    (0x62386532663937612D613664312D343133352D623966392D623832633031363064393664, 'Joint Region Marianas'),
    (0x62636238653164622D343461662D343035322D623566612D323365376266613861383666, 'Kabul International Airport'),
    (0x36313633356138662D663365322D346434372D383563352D653766333638373339343466, 'Kadena Air Base'),
    (0x62663162643236362D313565662D343262622D383338372D386532613361373466306435, 'Kandahar Airfield'),
    (0x62623833636339652D323538652D343433662D393331382D663962633237313466616463, 'Keesler Air Force Base'),
    (0x38636136323233642D303035652D346464632D616562362D376661336532303035633661, 'Kellogg Air National Guard Base'),
    (0x62646538613537352D323666312D343563662D613238642D383533653139366161613763, 'Key Field Air National Guard Base'),
    (0x35376263613833342D636436632D343935372D386630622D663361626332373463333337, 'Kingsley Field Air National Guard Base'),
    (0x31626361343765652D313436652D346239642D623362302D303762373431653164623562, 'Kirtland Air Force Base'),
    (0x31303534386565632D313330642D343334302D383164652D303834373732356632616161, 'Kleine Brogel Air Base'),
    (0x63376466383533622D656537382D343136312D383166352D653635306462333235653730, 'Kunsan Air Base'),
    (0x63646561363434632D383339662D346134392D396563352D326532666261313732333138, 'Lajes Field'),
    (0x64316263623934632D386632342D346533322D623462642D653864373136383664633332, 'Laughlin Air Force Base'),
    (0x37386162393265332D613932302D343833352D623534622D393130646438626131303265, 'Lincoln Air National Guard Base'),
    (0x38396238353465652D626366622D346138392D616232382D303437383565313362356434, 'Little Rock Air Force Base'),
    (0x61336133633766662D623463382D343331362D383035652D643864646233383966656664, 'Los Angeles Air Force Base'),
    (0x62623938613764302D313333382D346565382D393238382D323435396262333661626332, 'Louisville Air National Guard Base'),
    (0x38633933313736392D616332392D346438302D383162612D336364613133643363333462, 'Luke Air Force Base'),
    (0x61653962616632372D323339642D346635652D613336352D643162306334623862393837, 'MacDill Air Force Base'),
    (0x36366239373335302D343535652D346132332D383232632D613632376365366663383862, 'Malmstrom Air Force Base'),
    (0x38616436613665642D646231612D346563322D393430642D313962366531323862313038, 'Mansfield Lahm Air National Guard Base'),
    (0x33366437376164372D653164652D346637622D386638312D313861313833663533333435, 'March Joint Air Reserve Base'),
    (0x30343639333361612D643637612D346336622D393635652D643233393664396633623661, 'Maxwell Air Force Base'),
    (0x64326132643737632D363035382D343264362D383566312D376465393732656636343334, 'McConnell Air Force Base'),
    (0x61336632393636622D363761342D343462332D386232652D666363666237616234313838, 'McEntire Joint National Guard Base'),
    (0x38646630303031372D316131392D346639382D386662362D643964303965666566663934, 'McGhee Tyson Air National Guard Base'),
    (0x35313535653134302D656534652D346461342D393262322D396439376633633363326362, 'Memphis Air National Guard Base'),
    (0x62633536386566652D656261302D346564342D393862382D363861343163396661386133, 'Minneapolis-Saint Paul Joint Air Reserve Station'),
    (0x61323131313834662D386530652D343439652D386138352D336566343861373766383930, 'Minot Air Force Base'),
    (0x65383361623733622D643763372D346538622D393163642D386536333463333431613031, 'Misawa Air Base'),
    (0x34393237643933302D323236342D343566322D623531322D663664353737386163656631, 'Moffett Federal Airfield'),
    (0x34343737643635372D653638392D343138662D613963352D366562306434653862316432, 'Montgomery Air National Guard Base'),
    (0x35643835323530632D346638622D346430642D396135322D336434616163666233623561, 'Moody Air Force Base'),
    (0x62326465306262662D393261392D343434652D613835372D393932663366313235623565, 'Morón Air Base'),
    (0x35653938623330662D646531612D346366322D626235332D656433336637323835636531, 'Mountain Home Air Force Base'),
    (0x36643639373537362D626161342D343965642D386262612D383561396365306665373133, 'Muniz Air National Guard Base'),
    (0x37616462393530612D313632642D343166302D623334392D623832366538336363313331, 'NATO Air Base Geilenkirchen'),
    (0x37613033306562622D303035372D343832612D613938332D376432633130333366363533, 'Naval Air Station Joint Reserve Base Fort Worth'),
    (0x38363530323834372D656139632D343239322D616430642D386438643630343766623236, 'Naval Air Station Joint Reserve Base New Orleans'),
    (0x35363465313466652D623238662D343039652D613736662D623862626165396465333831, 'Naval Air Station Joint Reserve Base Willow Grove'),
    (0x63663339366663352D303339612D343864632D386361352D316162343634633433343633, 'Nellis Air Force Base'),
    (0x64323939343832652D383832392D346630662D623963662D626564303136663038363665, 'New Castle Air National Guard Base'),
    (0x30623561323236642D333266302D343036322D613435632D343639626139613135396634, 'Niagara Falls Air Reserve Station'),
    (0x37306436663730312D323364362D346130392D623865372D646661366636343233313165, 'North Highlands Air National Guard Station'),
    (0x30646234666234612D353537352D343863332D393030342D343933643030333530626335, 'Offutt Air Force Base'),
    (0x30306435636131302D643261322D346332652D386361302D306464313630356664326264, 'Osan Air Base'),
    (0x34623734383362312D306334382D343930322D613335362D656136646164383563356261, 'Other'),
    (0x38373531643639322D363862662D343433362D613662622D343863316333663561333330, 'Otis Air National Guard Base'),
    (0x30326366343934392D666134652D343066382D393437392D313135393330623339366539, 'Pápa Air Base'),
    (0x38636563636461392D366164352D343436662D383137312D643039383061316531663830, 'Patrick Air Force Base'),
    (0x38383832623139392D306532652D343837332D393166332D373133346335633531336535, 'Pease Air National Guard Base'),
    (0x64376363313566392D303536612D343336622D383433632D633230613635393639626136, 'Pentagon'),
    (0x64303332633061322D636336642D343130372D626335642D643730623262303332656636, 'Peoria Air National Guard Base'),
    (0x36366665396134332D353162322D343036312D383965612D343430643530373432623830, 'Peterson Air Force Base'),
    (0x62333861643664652D326337622D343135312D623064352D383739333739336631343639, 'Pittsburgh IAP Air Reserve Station'),
    (0x62663435356662342D656337392D343331352D393865372D343161666565623032343238, 'Pope Army Airfield'),
    (0x64323431346163312D303534382D343231332D393437332D663533343633643838346430, 'Portland Air National Guard Base'),
    (0x32393065623339302D353539392D343037632D626434362D646333643162663931376331, 'Quonset Point Air National Guard Station'),
    (0x66643761613534392D353437372D343233662D613665332D626633636238363938386262, 'RAF Alconbury'),
    (0x63666438326336632D313164372D346332362D383233362D303765633636636237313862, 'RAF Croughton'),
    (0x66393132383837612D623837362D343531652D613237302D643166373434346664373662, 'RAF Fairford'),
    (0x35363136623735652D333331392D346632372D626466332D623632666232383763383331, 'RAF Feltwell'),
    (0x61626237373235622D636162632D343166362D383265612D633433313562626366613431, 'RAF Fylingdales'),
    (0x38643064393937382D616634352D343766392D396561332D343335623361336533353333, 'RAF Lakenheath'),
    (0x63363535323964652D393237362D346565342D386463332D626236646365386638353433, 'RAF Menwith Hill'),
    (0x64653661653235372D646135302D346564642D623730662D643431656338666235316637, 'RAF Mildenhall'),
    (0x65393732303039302D663033372D343235662D386430632D643066383063303030363832, 'RAF Molesworth'),
    (0x37383362613134382D343737312D343730342D393035382D336435306661333362353265, 'RAF Welford'),
    (0x66663335366639392D666337642D343235312D383333302D616564336662326531653634, 'RAFO Thumrait'),
    (0x65346535326566662D346565392D346634392D396362352D303933616538653866653665, 'Ramstein Air Base'),
    (0x63336237613837642D636634372D343039322D383932662D323139613736316335393635, 'Reno Air National Guard Base'),
    (0x63656261653138322D383833312D343138612D613566632D643334626234633839663939, 'Rickenbacker Air National Guard Base'),
    (0x62366666613339392D663037342D346235392D383262312D383061313235393863646334, 'Robins Air Force Base'),
    (0x34376236363962302D663338362D343163312D393637332D623364353835373133303230, 'Rome Research Site'),
    (0x33633437373130302D333464342D343631642D623166312D303335613038646432363937, 'Rosecrans Air National Guard Base'),
    (0x63393832393531302D643931342D343463312D383134312D383463646132313930663734, 'Salt Lake City Air National Guard Base'),
    (0x65623861383932622D616432322D343762352D616563622D343336326333346531646137, 'Savannah Air National Guard Base'),
    (0x66643537613135632D666536372D343736332D383936342D336463333662303537653739, 'Schriever Air Force Base'),
    (0x38376164376536302D376439302D343134642D383031302D393131626562653039363631, 'Scott Air Force Base'),
    (0x33633934636532302D646666622D346136312D613931652D373438626330383037623333, 'Selfridge Air National Guard Base'),
    (0x34643632383463312D303435352D343866322D613233612D363036383337646332646537, 'Seymour Johnson Air Force Base'),
    (0x34613633323433382D333938392D343238612D396237332D336563303039393730636436, 'Shaw Air Force Base'),
    (0x64616433616132322D343061642D343733322D393933352D326566386137333335306136, 'Sheik Isa Air Base'),
    (0x30376538656538642D316634382D343563392D386261392D356662303933616566623164, 'Shepherd Field Air National Guard Base'),
    (0x32303362633037662D656366652D343264642D626532372D313234636166643364313638, 'Sheppard Air Force Base'),
    (0x37643533636666652D393932642D343238362D386231342D343236366262386135326332, 'Shindand Air Base'),
    (0x38356261636164332D636632642D343235652D383665622D623435366164356435353633, 'Shindand Air Base'),
    (0x63636139386432312D623366312D343939332D383462312D663938626165636538613033, 'Sioux City Air National Guard Base'),
    (0x62616530363366342D346236372D346539362D626430302D333739363761373839366534, 'Sky Harbor Air National Guard Base'),
    (0x32653935323065302D393230632D346265332D393463652D633538383763333464393733, 'Spangdahlem Air Base'),
    (0x61343039636338332D323933622D346339622D386339372D376634303362323965633761, 'Springfield Air National Guard Base'),
    (0x36373033386531382D393066652D346133622D396262632D376438373634356239643930, 'Stavanger Air Station'),
    (0x34636561333434372D333238392D346636342D616533342D316363663463353730373538, 'Stewart Air National Guard Base'),
    (0x31663366313531642D333763392D343134382D623433352D333137366266323364326134, 'Stratton Air National Guard Base'),
    (0x63333839323565302D663536332D346163352D393038342D383962383062316561343034, 'Terre Haute Air National Guard Base'),
    (0x65666132386163612D343839302D343734342D616630632D633630346463636432613939, 'Thule Air Base'),
    (0x34363735643338352D373738642D346466332D383738362D393666333763346466323034, 'Thumrait Air Base'),
    (0x62393239383461362D353233352D343738312D396633382D653331383539656566396236, 'Tinker Air Force Base'),
    (0x39653062383335302D623935622D343437332D613462352D313339626332623539623535, 'Toledo Air National Guard Base'),
    (0x32346135616661652D373563372D343436302D396438352D623961343735663937396633, 'Transit Center at Manas'),
    (0x33363962313938342D623036362D343066622D383733662D333939643331363233343039, 'Travis Air Force Base'),
    (0x62663766613034392D303565392D346632382D613032322D313165373336393765396637, 'Truax Field Air National Guard Base'),
    (0x61333030323064372D623762342D343866332D396466352D383639383635376131666336, 'Tucson Air National Guard Base'),
    (0x35356132643938612D366366652D346231352D393537362D333631376664323465386233, 'Tulsa Air National Guard Base'),
    (0x31613064353138612D303131642D346539302D613763642D363666316332313533623131, 'Tyndall Air Force Base'),
    (0x66343934386232372D333039372D343962612D396465662D623938323430333233346161, 'Vance Air Force Base'),
    (0x36636431323530302D323139642D343764362D616634372D333933313862343262396339, 'Vandenberg Air Force Base'),
    (0x38653263656334352D383236352D346131662D616364662D396631336533633835396338, 'Volk Field Air National Guard Base'),
    (0x64333665356538322D653066612D343833612D393436632D633762366334303862656430, 'Volkel Air Base'),
    (0x62633665363136332D666661342D343633312D383762632D636466633864646639623136, 'Warfield Air National Guard Base'),
    (0x31303837373463312D316538312D346132632D613037372D663538613766376562623865, 'Westover Air Reserve Base'),
    (0x32363665303337392D613836632D343137372D396637662D656461666461656465333665, 'Whiteman Air Force Base'),
    (0x34356464346537632D336133352D346138612D613936312D343662643465623935336330, 'Will Rogers Air National Guard Base'),
    (0x65366339623464382D353234632D343030332D396536632D613864366362356461616536, 'Wright-Patterson Air Force Base'),
    (0x33386134376164312D366233322D343534642D623264632D663465623032353632636166, 'Yokota Air Base'),
    (0x61613331396331372D393130372D346561342D613065382D303565316230323265326133, 'Youngstown Air Reserve Station')
ON DUPLICATE KEY UPDATE
    uuid=VALUES(uuid),
    baseName=VALUES(baseName);
-- SPLIT ;;
CREATE UNIQUE INDEX afscName
    ON afscList (name, editCode);
-- SPLIT ;;
UPDATE afscList SET afscList.hidden = 1 WHERE uuid IN (
    SELECT t1.uuid
    FROM (SELECT * FROM afscList) AS t1
        LEFT JOIN questionData on t1.uuid = questionData.afscUUID
    GROUP BY t1.uuid
    HAVING COUNT(questionData.uuid) < 10
);
-- SPLIT ;;
ALTER TABLE questionData DROP FOREIGN KEY questionData_ibfk_1;
-- SPLIT ;;
ALTER TABLE questionData DROP FOREIGN KEY questionData_ibfk_2;
-- SPLIT ;;
DROP INDEX setUUID ON questionData;
-- SPLIT ;;
DROP INDEX volumeUUID ON questionData;
-- SPLIT ;;
ALTER TABLE questionData DROP COLUMN volumeUUID;
-- SPLIT ;;
ALTER TABLE questionData DROP COLUMN setUUID;
-- SPLIT ;;
DROP TABLE volumeList;
-- SPLIT ;;
DROP TABLE setList;
-- SPLIT ;;
DROP TABLE systemLogData;
-- SPLIT ;;
DROP TABLE systemLog;
-- SPLIT ;;
DROP TABLE sessionData;
-- SPLIT ;;
DROP TABLE permissionData;
-- SPLIT ;;
DROP TABLE answerDataArchived;
-- SPLIT ;;
DROP TABLE questionDataArchived;
-- SPLIT ;;
SQL;

$queries = explode('-- SPLIT ;;', $queries);

if (!is_array($queries) || count($queries) === 0) {
    echo "no queries to execute\n";
    exit(1);
}

echo (new DateTime())->format('Y-m-d H:i:s') . "  starting execution\n";
foreach ($queries as $query) {
    if (trim($query) === '') {
        continue;
    }

    $db->begin_transaction();

    if (!$db->query($query)) {
        echo (new DateTime())->format('Y-m-d H:i:s') . "  ";
        echo "failed to execute query: {$query}\n\nerror: {$db->error}\n";
        $db->rollback();
        exit(1);
    }

    echo (new DateTime())->format('Y-m-d H:i:s') . "  ";
    echo "executed query: {$query}\n";
    $db->commit();
}

echo (new DateTime())->format('Y-m-d H:i:s') . "  finished execution\n";