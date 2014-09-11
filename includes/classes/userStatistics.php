<?php

class userStatistics extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;
	
	public $userUUID;
	
	public $averageScore;
	public $completedTests;
	public $incompleteTests;
	public $totalTests;
	
	public $logEntries;
	
	public $questionsAnswered;
	public $questionsMissed;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function queryCompletedTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testHistory WHERE userUUID = ?");
	}
	
	public function getUserUUID(){
		return $this->userUUID;
	}
	
	public function setUserUUID($userUUID){
		$this->userUUID = $userUUID;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}