<?php

class userStatistics extends CDCMastery
{
	protected $db;
	protected $log;
	protected $roles;
	
	public $error;
	
	public $userUUID;
	
	/*
	 * System Statistics
	 */
	public $logEntries;
	
	/*
	 * Testing Statistics
	 */
	public $averageScore;
	public $completedTests;
	public $incompleteTests;
	public $totalTests;
	public $questionsAnswered;
	public $questionsMissed;
	
	/*
	 * Subordinate Users
	 */
	public $supervisorAssociations;
	public $trainingManagerAssociations;
	
	/*
	 * Parent Users
	 */
	public $userSupervisors;
	public $userTrainingManagers;
	
	/*
	 * AFSC Associations
	 */
	public $afscAssociations;
	public $pendingAFSCAssociations;
	
	/*
	 * Row Counts
	 */
	public $countAFSCAssociations;
	public $countPendingAFSCAssociations;
	public $countSupervisorSubordinates;
	public $countTrainingManagerSubordinates;
	public $countUserSupervisors;
	public $countUserTrainingManagers;
	
	public function __construct(mysqli $db, log $log, roles $roles){
		$this->db = $db;
		$this->log = $log;
		$this->roles = $roles;
	}
	
	/*
	 * Entry Functions
	 */
	
	public function getLogEntries(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryLogEntries()){
				return false;
			}
			else{
				return $this->logEntries;
			}
		}
	}
	
	public function getAverageScore(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryAverageScore()){
				return false;
			}
			else{
				return $this->averageScore;
			}
		}
	}
	
	public function getCompletedTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCompletedTests()){
				return false;
			}
			else{
				return $this->completedTests;
			}
		}
	}
	
	public function getIncompleteTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryIncompleteTests()){
				return false;
			}
			else{
				return $this->incompleteTests;
			}
		}
	}
	
	public function getTotalTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->completedTests || !$this->incompleteTests){
				if(!$this->queryCompletedTests()){
					return false;
				}
				
				if(!$this->queryIncompleteTests()){
					return false;
				}
				
				$this->totalTests = $this->completedTests + $this->incompleteTests;
				return $this->totalTests;
			}
			else{
				$this->totalTests = $this->completedTests + $this->incompleteTests;
				return $this->totalTests;
			}
		}
	}
	
	public function getQuestionsAnswered(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryQuestionsAnswered()){
				return false;
			}
			else{
				return $this->questionsAnswered;
			}
		}
	}
	
	public function getQuestionsMissed(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryQuestionsMissed()){
				return false;
			}
			else{
				return $this->questionsMissed;
			}
		}
	}
	
	public function getSupervisorAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->roles->verifyUserRole($this->userUUID) == "supervisor"){
				if(!$this->querySupervisorAssociations()){
					return false;
				}
				else{
					return $this->supervisorAssociations;
				}
			}
			else{
				return false;
			}
		}
	}
	
	public function getTrainingManagerAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->roles->verifyUserRole($this->userUUID) == "trainingManager"){
				if(!$this->queryTrainingManagerAssociations()){
					return false;
				}
				else{
					return $this->trainingManagerAssociations;
				}
			}
			else{
				return false;
			}
		}
	}
	
	public function getUserSupervisors(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryUserSupervisors()){
				return false;
			}
			else{
				return $this->userSupervisors;
			}
		}
	}
	
	public function getUserTrainingManagers(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryUserTrainingManagers()){
				return false;
			}
			else{
				return $this->userTrainingManagers;
			}
		}
	}
	
	public function getAFSCAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryAFSCAssociations()){
				return false;
			}
			else{
				return $this->afscAssociations;
			}
		}
	}
	
	public function getPendingAFSCAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryPendingAFSCAssociations()){
				return false;
			}
			else{
				return $this->pendingAFSCAssociations;
			}
		}
	}
	
	public function getAFSCAssociationCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCountAFSCAssociations()){
				return false;
			}
			else{
				return $this->countAFSCAssociations;
			}
		}
	}
	
	public function getPendingAFSCAssociationCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCountPendingAFSCAssociations()){
				return false;
			}
			else{
				return $this->countPendingAFSCAssociations;
			}
		}
	}
	
	public function getSupervisorSubordinateCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCountSupervisorSubordinates()){
				return false;
			}
			else{
				return $this->countSupervisorSubordinates;
			}
		}
	}
	
	public function getTrainingManagerSubordinateCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCountTrainingManagerSubordinates()){
				return false;
			}
			else{
				return $this->countTrainingManagerSubordinates;
			}
		}
	}
	
	public function getUserSupervisorCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryUserSupervisors()){
				return false;
			}
			else{
				return $this->countUserSupervisors;
			}
		}
	}
	
	public function getUserTrainingManagerCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryUserTrainingManagers()){
				return false;
			}
			else{
				return $this->countUserTrainingManagers;
			}
		}
	}
	
	/*
	 * Queries
	 */
	
	public function queryLogEntries(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			
			while($stmt->fetch()){
				$this->logEntries = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryLogEntries()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryAverageScore(){
		$stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($averageScore);
		
			while($stmt->fetch()){
				$this->averageScore = $averageScore;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryAverageScore()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryCompletedTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			
			while($stmt->fetch()){
				$this->completedTests = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCompletedTests()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
			
			return false;
		}
	}
	
	public function queryIncompleteTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testManager WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->incompleteTests = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryIncompleteTests()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryQuestionsAnswered(){
		$stmt = $this->db->prepare("SELECT SUM(totalQuestions) AS questionsAnswered FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsAnswered);
		
			while($stmt->fetch()){
				$this->questionsAnswered = $questionsAnswered;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryQuestionsAnswered()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryQuestionsMissed(){
		$stmt = $this->db->prepare("SELECT SUM(questionsMissed) AS questionsMissed FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsMissed);
		
			while($stmt->fetch()){
				$this->questionsMissed = $questionsMissed;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryQuestionsMissed()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function querySupervisorAssociations(){
		$stmt = $this->db->prepare("SELECT userUUID FROM userSupervisorAssociations WHERE supervisorUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($userUUID);
			
			while($stmt->fetch()){
				$this->supervisorAssociations[] = $userUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->querySupervisorAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryTrainingManagerAssociations(){
		$stmt = $this->db->prepare("SELECT userUUID FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($userUUID);
		
			while($stmt->fetch()){
				$this->trainingManagerAssociations[] = $userUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryTrainingManagerAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
			
			return false;
		}
	}
	
	public function queryUserSupervisors(){
		$stmt = $this->db->prepare("SELECT supervisorUUID FROM userSupervisorAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($supervisorUUID);
		
			while($stmt->fetch()){
				$this->userSupervisors[] = $supervisorUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryUserSupervisors()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryUserTrainingManagers(){
		$stmt = $this->db->prepare("SELECT trainingManagerUUID FROM userTrainingManagerAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($trainingManagerUUID);
		
			while($stmt->fetch()){
				$this->userTrainingManagers[] = $trainingManagerUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryUserTrainingManagers()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT afscUUID FROM userAFSCAssociations WHERE userAuthorized = 1 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($afscUUID);
		
			while($stmt->fetch()){
				$this->afscAssociations[] = $afscUUID;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryAFSCAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryPendingAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT afscUUID FROM userAFSCAssociations WHERE userAuthorized = 0 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
	
		if($stmt->execute()){
			$stmt->bind_result($afscUUID);
	
			while($stmt->fetch()){
				$this->pendingAFSCAssociations[] = $afscUUID;
			}
	
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryPendingAFSCAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryCountAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 1 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countAFSCAssociations = $count;
			}
		
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountAFSCAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountPendingAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 0 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countPendingAFSCAssociations = $count;
			}
		
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountPendingAFSCAssociations()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountSupervisorSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE supervisorUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
				
			while($stmt->fetch()){
				$this->countSupervisorSubordinates = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountSupervisorSubordinates()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountTrainingManagerSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countTrainingManagerSubordinates = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountTrainingManagerSubordinates()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryCountUserSupervisors(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countUserSupervisors = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountUserSupervisors()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountUserTrainingManagers(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countUserTrainingManagers = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","userStatistics->queryCountUserTrainingManagers()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
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