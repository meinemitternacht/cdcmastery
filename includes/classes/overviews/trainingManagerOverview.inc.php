<?php

class trainingManagerOverview extends CDCMastery
{
    public $error;
    public $trainingManagerUUID;

    public $totalUserTests;
    public $totalSupervisorTests;

    public $averageUserTestScore;
    public $averageSupervisorTestScore;

    protected $db;
    protected $log;
    protected $userStatistics;
    protected $user;
    protected $roles;

    protected $subordinateUserList = Array();
    protected $subordinateSupervisorList = Array();

    public function __construct(mysqli $db, log $log, userStatistics $userStatistics, user $user, roles $roles)
    {
        $this->db = $db;
        $this->log = $log;
        $this->userStatistics = $userStatistics;
        $this->user = $user;
        $this->roles = $roles;
    }

    public function loadTrainingManager($userUUID)
    {
        if(!empty($userUUID)) {
            $this->trainingManagerUUID = $userUUID;
            $this->loadSubordinateUsers($this->trainingManagerUUID);
            return true;
        }
        else{
            return false;
        }
    }

    public function loadSubordinateUsers($trainingManagerUUID)
    {
        $stmt = $this->db->prepare("SELECT userUUID FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
        $stmt->bind_param("s",$trainingManagerUUID);

        if($stmt->execute()) {
            $stmt->bind_result($userUUID);

            while($stmt->fetch()){
                $tempList[] = $userUUID;
            }

            $stmt->close();

            foreach($tempList as $userUUID) {
                if ($this->roles->getRoleType($this->user->getUserRoleByUUID($userUUID)) == "user") {
                    $this->subordinateUserList[] = $userUUID;
                } elseif ($this->roles->getRoleType($this->user->getUserRoleByUUID($userUUID)) == "supervisor") {
                    $this->subordinateSupervisorList[] = $userUUID;
                }
            }

            return true;
        }
        else{
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Error",$stmt->error);
            $this->log->setDetail("Calling Function","trainingManagerOverview->loadSubordinateUsers()");
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

    public function getTotalSupervisorTests(){
        if(!empty($this->subordinateSupervisorList)){
            foreach($this->subordinateSupervisorList as $subordinateSupervisor){
                if($this->userStatistics->setUserUUID($subordinateSupervisor)) {
                    $this->totalSupervisorTests += $this->userStatistics->getTotalTests();
                }
            }

            return $this->totalSupervisorTests;
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

    public function getAverageSupervisorTestScore(){
        if(!empty($this->subordinateSupervisorList)){
            $runningAverage = 0;
            $runningDataPoints = 0;

            foreach($this->subordinateSupervisorList as $subordinateSupervisor){
                if($this->userStatistics->setUserUUID($subordinateSupervisor)) {
                    $averageScore = $this->userStatistics->getAverageScore();

                    if($averageScore > 0) {
                        $runningAverage += $averageScore;
                        $runningDataPoints++;
                    }
                }
            }

            if($runningAverage > 0 && $runningDataPoints > 0) {
                $this->averageSupervisorTestScore = round(($runningAverage / $runningDataPoints),2);
            }
            else{
                $this->averageSupervisorTestScore = 0;
            }

            return $this->averageSupervisorTestScore;
        }
        else{
            return false;
        }
    }

    public function getSubordinateUserList(){
        return $this->subordinateUserList;
    }

    public function getSubordinateSupervisorList(){
        return $this->subordinateSupervisorList;
    }

    public function __destruct()
    {
        parent::__destruct();
    }
}