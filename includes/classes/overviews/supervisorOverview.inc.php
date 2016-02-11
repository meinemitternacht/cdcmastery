<?php

class supervisorOverview extends CDCMastery
{
    public $error;
    public $supervisorUUID;

    protected $db;
    protected $log;
    protected $userStatistics;
    protected $user;
    protected $roles;

    protected $subordinateUserList = Array();
    protected $averageUserTestScore;
    protected $totalUserTests;

    public function __construct(mysqli $db, log $log, userStatistics $userStatistics, user $user, roles $roles)
    {
        $this->db = $db;
        $this->log = $log;
        $this->userStatistics = $userStatistics;
        $this->user = $user;
        $this->roles = $roles;
    }

    public function loadSupervisor($userUUID)
    {
        if(!empty($userUUID)) {
            $this->supervisorUUID = $userUUID;
            $this->loadSubordinateUsers($this->supervisorUUID);
            return true;
        }
        else{
            return false;
        }
    }

    public function loadSubordinateUsers($supervisorUUID)
    {
        $stmt = $this->db->prepare("SELECT userUUID FROM userSupervisorAssociations WHERE supervisorUUID = ?");
        $stmt->bind_param("s",$supervisorUUID);

        if($stmt->execute()) {
            $stmt->bind_result($userUUID);

            while($stmt->fetch()){
                $tempList[] = $userUUID;
            }

            $stmt->close();

            foreach($tempList as $userUUID) {
                    $this->subordinateUserList[] = $userUUID;
            }

            return true;
        }
        else{
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Error",$stmt->error);
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->saveEntry();

            return false;
        }
    }

    public function getUserAFSCAssociations(){
        if(is_array($this->subordinateUserList) && !empty($this->subordinateUserList)) {
            $userConstraint = "('" . implode("','", $this->subordinateUserList) . "')";
            $query = "SELECT DISTINCT(afscUUID) FROM userAFSCAssociations LEFT JOIN afscList ON userAFSCAssociations.afscUUID=afscList.uuid WHERE userUUID IN " . $userConstraint . " ORDER BY afscList.afscName ASC";
            $res = $this->db->query($query);

            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $afscArray[] = $row['afscUUID'];
                }

                if (is_array($afscArray) && !empty($afscArray)) {
                    return $afscArray;
                } else {
                    $this->error = "No results found.";
                    return false;
                }
            } else {
                $this->error = "No results found.";
                return false;
            }
        }
        else{
            $this->error = "No subordinate users.";
            return false;
        }
    }

    public function getQuestionsShownCountByAFSC($afscUUID,array $userList){
        if(count($userList) > 1){
            $userConstraint = "AND testHistory.userUUID IN ('".implode("','",$userList)."')";
        }
        else{
            $userConstraint = "AND testHistory.userUUID = '".$userList[0]."'";
        }

        $query = "SELECT COUNT(*) AS count, testData.questionUUID
                    FROM testData
                    LEFT JOIN answerData ON testData.answerUUID=answerData.uuid
                    LEFT JOIN questionData ON testData.questionUUID=questionData.uuid
                    LEFT JOIN testHistory ON testData.testUUID=testHistory.uuid
                      WHERE questionData.afscUUID = '".$afscUUID."'
                      ".$userConstraint."
                      GROUP BY testData.questionUUID";

        $res = $this->db->query($query);

        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $questionCountArray[$row['questionUUID']] = $row['count'];
            }

            if(count($questionCountArray) > 0){
                return $questionCountArray;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getQuestionsMissedOverviewByAFSC($afscUUID,array $userList){
        if(count($userList) > 1){
            $userConstraint = "AND testHistory.userUUID IN ('".implode("','",$userList)."')";
        }
        else{
            $userConstraint = "AND testHistory.userUUID = '".$userList[0]."'";
        }

        $query = "SELECT COUNT(*) AS count, testData.questionUUID
                    FROM testData
                    LEFT JOIN answerData ON testData.answerUUID=answerData.uuid
                    LEFT JOIN questionData ON testData.questionUUID=questionData.uuid
                    LEFT JOIN testHistory ON testData.testUUID=testHistory.uuid
                      WHERE questionData.afscUUID = '".$afscUUID."'
                      ".$userConstraint."
                      AND answerData.answerCorrect=0
                    GROUP BY testData.questionUUID";

        $res = $this->db->query($query);

        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $missedQuestionsArray[$row['questionUUID']] = $row['count'];
            }

            if(count($missedQuestionsArray) > 0){
                return $missedQuestionsArray;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTotalUserTests(){
        if(!empty($this->subordinateUserList)){
            foreach($this->subordinateUserList as $subordinateUser){
                if($this->userStatistics->setUserUUID($subordinateUser)) {
                    $this->totalUserTests += $this->userStatistics->getTotalTests();
                }
            }

            return $this->totalUserTests;
        }
        else{
            return false;
        }
    }

    public function getAverageUserTestScore(){
        if(!empty($this->subordinateUserList)){
            $runningAverage = 0;
            $runningDataPoints = 0;

            foreach($this->subordinateUserList as $subordinateUser){
                if($this->userStatistics->setUserUUID($subordinateUser)) {
                    $averageScore = $this->userStatistics->getAverageScore();

                    if($averageScore > 0) {
                        $runningAverage += $averageScore;
                        $runningDataPoints++;
                    }
                }
            }

            if(($runningAverage > 0) && ($runningDataPoints > 0)) {
                $this->averageUserTestScore = round(($runningAverage / $runningDataPoints),2);
            }
            else{
                $this->averageUserTestScore = 0;
            }

            return $this->averageUserTestScore;
        }
        else{
            return false;
        }
    }

    public function getSubordinateUserList(){
        return $this->subordinateUserList;
    }

    public function __destruct()
    {
        parent::__destruct();
    }
}