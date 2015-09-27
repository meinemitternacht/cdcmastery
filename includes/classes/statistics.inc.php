<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/23/2015
 * Time: 8:10 PM
 */

class statistics extends CDCMastery {

    protected $db;
    protected $log;
    protected $emailQueue;

    public $error;

    public $afscPassRateArray;
    public $testAFSCCount;
    public $testsAverageScoreArrayLastSeven;
    public $testsAverageScoreByTimespan;

    public $baseActionsCount;
    public $totalTestsByBase;
    public $averageScoreByBase;

    public $totalTests;
    public $totalCompletedTests;
    public $totalIncompleteTests;
    public $totalQuestionsAnswered;

    public $totalAFSCCategories;
    public $totalFOUOAFSCCategories;

    public $totalQuestions;
    public $totalQuestionsArchived;
    public $totalQuestionsFOUO;

    public $totalAnswers;
    public $totalAnswersArchived;
    public $totalAnswersFOUO;

    public $totalUsers;
    public $totalRoleUser;
    public $totalRoleTrainingManager;
    public $totalRoleSupervisor;
    public $totalRoleAdministrator;
    public $totalRoleSuperAdministrator;
    public $totalRoleEditor;

    public $totalLogEntries;
    public $totalLogDetails;
    public $totalLoginErrors;

    public $logActionCount;

    public $totalOfficeSymbols;

    public function __construct(mysqli $db, log $log, emailQueue $emailQueue){
        $this->db = $db;
        $this->log = $log;
        $this->emailQueue = $emailQueue;
    }

    public function getAFSCPassRates($afscUUIDList){
        if(!$this->queryAFSCPassRates($afscUUIDList)){
            return false;
        }
        else{
            return $this->afscPassRateArray;
        }
    }

    public function queryAFSCPassRates($afscUUIDList){
        if(is_array($afscUUIDList) && count($afscUUIDList) > 0) {
            foreach($afscUUIDList as $afscUUID) {
                $query = "SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE '%a:1%' AND afscList LIKE '%".$afscUUID."%' AND testScore > " . $this->getPassingScore();
                $res = $this->db->query($query);

                if($res->num_rows > 0){
                    while($row = $res->fetch_assoc()) {
                        $this->afscPassRateArray[$afscUUID]['passingTests'] = $row['count'];
                    }
                    $res->close();
                }
            }

            foreach($afscUUIDList as $afscUUID){
                $res = $this->db->query("SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE '%a:1%' AND afscList LIKE '%".$afscUUID."%'");

                if($res->num_rows > 0){
                    while($row = $res->fetch_assoc()) {
                        $this->afscPassRateArray[$afscUUID]['totalTests'] = $row['count'];
                        if (($this->afscPassRateArray[$afscUUID]['passingTests'] > 0) && ($this->afscPassRateArray[$afscUUID]['totalTests'] > 0)) {
                            $this->afscPassRateArray[$afscUUID]['passRate'] = round(($this->afscPassRateArray[$afscUUID]['passingTests'] / $this->afscPassRateArray[$afscUUID]['totalTests']) * 100, 2);
                        } else {
                            $this->afscPassRateArray[$afscUUID]['passRate'] = 0;
                        }
                    }

                    $res->close();
                }
            }

            if(isset($this->afscPassRateArray) && is_array($this->afscPassRateArray) && count($this->afscPassRateArray) > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestAFSCCount($afscUUIDList){
        if(!$this->queryTestAFSCCount($afscUUIDList)){
            return false;
        }
        else{
            return $this->testAFSCCount;
        }
    }

    public function queryTestAFSCCount($afscUUIDList){
        if(is_array($afscUUIDList) && count($afscUUIDList) > 0) {
            foreach($afscUUIDList as $afscUUID) {
                $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE ?");
                $afscUUIDParam = "%".$afscUUID."%";
                $stmt->bind_param("s",$afscUUIDParam);

                if($stmt->execute()){
                    $stmt->bind_result($count);

                    while($stmt->fetch()){
                        $this->testAFSCCount[$afscUUID] = $count;
                    }

                    $stmt->close();
                }
                else{
                    $this->error = $stmt->error;
                    $this->log->setAction("MYSQL_ERROR");
                    $this->log->setDetail("CALLING FUNCTION","statistics->queryTestAFSCCount()");
                    $this->log->setDetail("MYSQL ERROR",$this->error);
                    $this->log->saveEntry();
                    $stmt->close();

                    return false;
                }
            }

            if(isset($this->testAFSCCount) && is_array($this->testAFSCCount) && count($this->testAFSCCount) > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestAverageLastSeven(){
        if(!$this->queryTestAverageLastSeven()){
            return false;
        }
        else{
            return $this->testsAverageScoreArrayLastSeven;
        }
    }

    public function queryTestAverageLastSeven(){
        $j=0;
        for($i=7;$i>0;$i--) {
            $dateObj = new DateTime('now');
            $dateObj->modify('-'.$i.' days');

            $startDateTime = $dateObj->format('Y-m-d 00:00:00');
            $endDateTime = $dateObj->format('Y-m-d 23:59:59');

            $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

            $stmt->bind_param("ss",$startDateTime,$endDateTime);

            if($stmt->execute()){
                $stmt->bind_result($averageScore);

                while($stmt->fetch()){
                    if(!$averageScore){
                        $averageScore = "0";
                    }
                    $this->testsAverageScoreArrayLastSeven[$j]['averageScore'] = round($averageScore,2);
                    $this->testsAverageScoreArrayLastSeven[$j]['dateTime'] = $dateObj->format('j-M-Y');
                }

                $stmt->close();
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("CALLING FUNCTION","statistics->queryTestAverageLastSeven()");
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
            $j++;
        }

        if(isset($this->testsAverageScoreArrayLastSeven) && is_array($this->testsAverageScoreArrayLastSeven) && count($this->testsAverageScoreArrayLastSeven) > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestAverageByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        if(!$this->queryTestAverageByTimespan($dateTimeStart, $dateTimeEnd)){
            return false;
        }
        else{
            return $this->testsAverageScoreByTimespan;
        }
    }

    public function queryTestAverageByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        $startDateTime = $dateTimeStart->format('Y-m-d 00:00:00');
        $endDateTime = $dateTimeEnd->format('Y-m-d 23:59:59');

        $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

        $stmt->bind_param("ss",$startDateTime,$endDateTime);

        if($stmt->execute()){
            $stmt->bind_result($averageScore);

            while($stmt->fetch()){
                if(!$averageScore){
                    $averageScore = "0";
                }
                $this->testsAverageScoreByTimespan = round($averageScore,2);
            }

            $stmt->close();
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTestAverageByTimespan()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }

        if(isset($this->testsAverageScoreByTimespan)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestCountByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        if(!$this->queryTestCountByTimespan($dateTimeStart, $dateTimeEnd)){
            return false;
        }
        else{
            return $this->testCountByTimespan;
        }
    }

    public function queryTestCountByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        $startDateTime = $dateTimeStart->format('Y-m-d 00:00:00');
        $endDateTime = $dateTimeEnd->format('Y-m-d 23:59:59');

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

        $stmt->bind_param("ss",$startDateTime,$endDateTime);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                if(!$count){
                    $count = "0";
                }
                $this->testCountByTimespan = $count;
            }

            $stmt->close();
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTestCountByTimespan()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }

        if(isset($this->testCountByTimespan)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTotalTestsByBase($baseUUID){
        if(!$this->queryTotalTestsByBase($baseUUID)){
            return false;
        }
        else{
            return $this->totalTestsByBase;
        }
    }

    public function queryTotalTestsByBase($baseUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory`
                                        LEFT JOIN `userData` ON `userData`.`uuid` = `testHistory`.`userUUID`
                                        WHERE `userData`.`userBase` = ?");

        $stmt->bind_param("s",$baseUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalTestsByBase = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalTestsByBase()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getAverageScoreByBase($baseUUID){
        if(!$this->queryAverageScoreByBase($baseUUID)){
            return false;
        }
        else{
            return round($this->averageScoreByBase,2);
        }
    }

    public function queryAverageScoreByBase($baseUUID){
        $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory`
                                        LEFT JOIN `userData` ON `userData`.`uuid` = `testHistory`.`userUUID`
                                        WHERE `userData`.`userBase` = ?");

        $stmt->bind_param("s",$baseUUID);

        if($stmt->execute()){
            $stmt->bind_result($averageScore);

            while($stmt->fetch()){
                $this->averageScoreByBase = $averageScore;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryAverageScoreByBase()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalTests(){
        if(!$this->queryTotalCompletedTests()){
            return false;
        }
        elseif(!$this->queryTotalIncompleteTests()){
            return false;
        }
        else{
            $this->totalTests = $this->totalCompletedTests + $this->totalIncompleteTests;
            return $this->totalTests;
        }
    }

    public function getTotalIncompleteTests(){
        if(!$this->queryTotalIncompleteTests()){
            return false;
        }
        else{
            return $this->totalIncompleteTests;
        }
    }

    public function queryTotalIncompleteTests(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testManager`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalIncompleteTests = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalIncompleteTests()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalCompletedTests(){
        if(!$this->queryTotalCompletedTests()){
            return false;
        }
        else{
            return $this->totalCompletedTests;
        }
    }

    public function queryTotalCompletedTests(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalCompletedTests = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalCompletedTests()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsAnswered(){
        if(!$this->queryTotalQuestionsAnswered()){
            return false;
        }
        else{
            return $this->totalQuestionsAnswered;
        }
    }

    public function queryTotalQuestionsAnswered(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testData`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestionsAnswered = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalQuestionsAnswered()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAFSCCategories(){
        if(!$this->queryTotalAFSCCategories()){
            return false;
        }
        else{
            return $this->totalAFSCCategories;
        }
    }

    public function queryTotalAFSCCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `afscList`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAFSCCategories = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalAFSCCategories()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalFOUOAFSCCategories(){
        if(!$this->queryTotalFOUOAFSCCategories()){
            return false;
        }
        else{
            return $this->totalFOUOAFSCCategories;
        }
    }

    public function queryTotalFOUOAFSCCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `afscList` WHERE afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalFOUOAFSCCategories = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalFOUOAFSCCategories()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestions(){
        if(!$this->queryTotalQuestions()){
            return false;
        }
        else{
            return $this->totalQuestions;
        }
    }

    public function queryTotalQuestions(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionData`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestions = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalQuestions()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsArchived(){
        if(!$this->queryTotalQuestionsArchived()){
            return false;
        }
        else{
            return $this->totalQuestionsArchived;
        }
    }

    public function queryTotalQuestionsArchived(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionDataArchived`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestionsArchived = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalQuestionsArchived()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsFOUO(){
        if(!$this->queryTotalQuestionsFOUO()){
            return false;
        }
        else{
            return $this->totalQuestionsFOUO;
        }
    }

    public function queryTotalQuestionsFOUO(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionData` LEFT JOIN afscList ON questionData.afscUUID = afscList.uuid WHERE afscList.afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestionsFOUO = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalQuestionsFOUO()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswers(){
        if(!$this->queryTotalAnswers()){
            return false;
        }
        else{
            return $this->totalAnswers;
        }
    }

    public function queryTotalAnswers(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerData`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswers = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalAnswers()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswersArchived(){
        if(!$this->queryTotalAnswersArchived()){
            return false;
        }
        else{
            return $this->totalAnswersArchived;
        }
    }

    public function queryTotalAnswersArchived(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerDataArchived`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswersArchived = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalAnswersArchived()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswersFOUO(){
        if(!$this->queryTotalAnswersFOUO()){
            return false;
        }
        else{
            return $this->totalAnswersFOUO;
        }
    }

    public function queryTotalAnswersFOUO(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerData` LEFT JOIN questionData ON questionData.uuid = answerData.questionUUID LEFT JOIN afscList ON questionData.afscUUID = afscList.uuid WHERE afscList.afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswersFOUO = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalAnswersFOUO()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalUsers(){
        if(!$this->queryTotalUsers()){
            return false;
        }
        else{
            return $this->totalUsers;
        }
    }

    public function queryTotalUsers(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalUsers = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalUsers()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleUser(){
        if(!$this->queryTotalRoleUser()){
            return false;
        }
        else{
            return $this->totalRoleUser;
        }
    }

    public function queryTotalRoleUser(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Users");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleUser = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleUser()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleTrainingManager(){
        if(!$this->queryTotalRoleTrainingManager()){
            return false;
        }
        else{
            return $this->totalRoleTrainingManager;
        }
    }

    public function queryTotalRoleTrainingManager(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Training Managers");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleTrainingManager = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleTrainingManager()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleSupervisor(){
        if(!$this->queryTotalRoleSupervisor()){
            return false;
        }
        else{
            return $this->totalRoleSupervisor;
        }
    }

    public function queryTotalRoleSupervisor(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Supervisors");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleSupervisor = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleSupervisor()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleAdministrator(){
        if(!$this->queryTotalRoleAdministrator()){
            return false;
        }
        else{
            return $this->totalRoleAdministrator;
        }
    }

    public function queryTotalRoleAdministrator(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Administrators");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleAdministrator = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleAdministrator()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleSuperAdministrator(){
        if(!$this->queryTotalRoleSuperAdministrator()){
            return false;
        }
        else{
            return $this->totalRoleSuperAdministrator;
        }
    }

    public function queryTotalRoleSuperAdministrator(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Super Administrators");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleSuperAdministrator = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleSuperAdministrator()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleEditor(){
        if(!$this->queryTotalRoleEditor()){
            return false;
        }
        else{
            return $this->totalRoleEditor;
        }
    }

    public function queryTotalRoleEditor(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Question Editors");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleEditor = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalRoleEditor()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLogEntries(){
        if(!$this->queryTotalLogEntries()){
            return false;
        }
        else{
            return $this->totalLogEntries;
        }
    }

    public function queryTotalLogEntries(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLogEntries = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryLogEntries()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLogDetails(){
    if(!$this->queryTotalLogDetails()){
        return false;
    }
    else{
        return $this->totalLogDetails;
    }
}

    public function queryTotalLogDetails(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLogData");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLogDetails = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryLogDetails()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLoginErrors(){
        if(!$this->queryTotalLoginErrors()){
            return false;
        }
        else{
            return $this->totalLoginErrors;
        }
    }

    public function queryTotalLoginErrors(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE action LIKE '%ERROR_LOGIN%' OR action LIKE '%LOGIN_ERROR%'");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLoginErrors = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryLoginErrors()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getLogCountByAction($logAction){
        if(!$this->queryLogCountByAction($logAction)){
            return false;
        }
        else{
            return $this->logActionCount[$logAction];
        }
    }

    public function queryLogCountByAction($logAction){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE action LIKE ?");
        $stmt->bind_param("s",$logAction);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->logActionCount[$logAction] = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryLogCountByAction()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalOfficeSymbols(){
        if(!$this->queryTotalOfficeSymbols()){
            return false;
        }
        else{
            return $this->totalOfficeSymbols;
        }
    }

    public function queryTotalOfficeSymbols(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM officeSymbolList");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalOfficeSymbols = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","statistics->queryTotalOfficeSymbols()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function __destruct(){
        parent::__destruct();
    }
}