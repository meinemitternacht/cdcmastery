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
            $this->log->setDetail("Calling Function","supervisorOverview->loadSubordinateUsers()");
            $this->log->saveEntry();

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