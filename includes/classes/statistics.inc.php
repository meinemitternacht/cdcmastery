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

    public $totalOfficeSymbols;

    public function __construct(mysqli $db, log $log, emailQueue $emailQueue){
        $this->db = $db;
        $this->log = $log;
        $this->emailQueue = $emailQueue;
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