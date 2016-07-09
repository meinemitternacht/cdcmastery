<?php

namespace CDCMastery;
use mysqli;

class TestGenerator extends CDCMastery
{
	public $db;
	protected $log;
	protected $afsc;
	protected $answer;
	protected $question;
	
	public $error;
	public $message;
	
	/*
	 * testGeneratorData Table
	 */
	public $uuid; //binary(36)
	public $afscUUID; //binary(36)
	public $questionList; //mediumtext
	public $totalQuestions; //int(5)
	public $userUUID; //binary(36)
	public $dateCreated; //timestamp (default UTC_TIMESTAMP)
	
	public function __construct(mysqli $db, SystemLog $log, AFSCManager $afsc){
		$this->db = $db;
		$this->log = $log;
		$this->afsc = $afsc;
		$this->answer = new AnswerManager($this->db, $this->log);
		$this->question = new QuestionManager($this->db, $this->log, $this->afsc, $this->answer);
	}

	public function listGeneratedTests($userUUID = false){
		if($userUUID){
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, totalQuestions, userUUID, dateCreated
										FROM testGeneratorData
										WHERE userUUID = ?
										ORDER BY dateCreated DESC");
			$stmt->bind_param("s",$userUUID);
		}
		else{
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, totalQuestions, userUUID, dateCreated
										FROM testGeneratorData
										ORDER BY dateCreated DESC");
		}
		if($stmt->execute()){
			$stmt->bind_result($uuid, $afscUUID, $totalQuestions, $userUUID, $dateCreated);

			while($stmt->fetch()){
				$resultArray[$uuid]['afscUUID'] = $afscUUID;
				$resultArray[$uuid]['totalQuestions'] = $totalQuestions;
				$resultArray[$uuid]['userUUID'] = $userUUID;
				$resultArray[$uuid]['dateCreated'] = $dateCreated;
			}

			$stmt->close();

			if(!empty($resultArray) && is_array($resultArray)){
				return $resultArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GENERATED_TEST_LIST");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			if($userUUID) $this->log->setDetail("User UUID",$userUUID);

			$this->log->saveEntry();
			return false;
		}
	}

	public function loadGeneratedTest($generatedTestUUID){
		$stmt = $this->db->prepare("SELECT	uuid,
											afscUUID,
											questionList,
											totalQuestions,
											userUUID,
											dateCreated
										FROM testGeneratorData
										WHERE uuid = ?");
		$stmt->bind_param("s",$generatedTestUUID);

		if($stmt->execute()){
			$stmt->bind_result($uuid, $afscUUID, $questionList, $totalQuestions, $userUUID, $dateCreated);
			$stmt->fetch();
			$stmt->close();

			$this->uuid = $uuid;
			$this->afscUUID = $afscUUID;
			$serializedQuestionList = $questionList;
			$this->totalQuestions = $totalQuestions;
			$this->userUUID = $userUUID;
			$this->dateCreated = $dateCreated;

			if(!empty($this->uuid)) {
				$this->questionList = unserialize($serializedQuestionList);

				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GENERATED_TEST_LOAD");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->setDetail("Generated Test UUID",$this->uuid);
			$this->log->saveEntry();

			return false;
		}
	}

	public function saveGeneratedTest(){
		$serializedQuestionList = serialize($this->questionList);

		$stmt = $this->db->prepare("INSERT INTO testGeneratorData (	uuid,
																afscUUID,
																questionList,
																totalQuestions,
																userUUID,
																dateCreated)
													VALUES (?,?,?,?,?,?)
													ON DUPLICATE KEY UPDATE
																uuid=VALUES(uuid),
																afscUUID=VALUES(afscUUID),
																questionList=VALUES(questionList),
																totalQuestions=VALUES(totalQuestions),
																userUUID=VALUES(userUUID)");
		$stmt->bind_param("sssiss",	$this->uuid,
									$this->afscUUID,
									$serializedQuestionList,
									$this->totalQuestions,
									$this->userUUID,
									$this->dateCreated);

		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GENERATED_TEST_SAVE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->setDetail("Generated Test UUID",$this->uuid);
			$this->log->saveEntry();

			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}

	public function generateTest($numQuestionsWanted=100){
		if(empty($this->afscUUID)){
			$this->error = "You must specify an AFSC to create a test for.";
			return false;
		}

		if($numQuestionsWanted <= 0){
			$this->error = "Number of questions cannot be equal to or less than zero.";
			return false;
		}

		$this->uuid = parent::genUUID();

		$this->question->setAFSCUUID($this->afscUUID);
		$afscQuestionList = $this->question->listQuestionsForAFSC($numQuestionsWanted,true);

		foreach($afscQuestionList as $questionUUID){
			$this->addQuestion($questionUUID);
		}

		$this->userUUID = $_SESSION['userUUID'];

		if($this->saveGeneratedTest()){
			$this->log->setAction("GENERATED_TEST_CREATE");
			$this->log->setDetail("Generated Test UUID",$this->uuid);
			$this->log->setDetail("AFSC UUID",$this->afscUUID);
			$this->log->setDetail("Total Questions",$this->totalQuestions);
			$this->log->saveEntry();
			return true;
		}
		else{
			return false;
		}
	}

	public function addQuestion($questionUUID){
		$this->questionList[] = $questionUUID;
		$this->totalQuestions++;
		return true;
	}

	public function removeQuestion($questionUUID){
		if(($key = array_search($questionUUID, $this->questionList)) !== false) {
			unset($this->questionList[$key]);
			$this->totalQuestions--;
			return true;
		}
		else{
			return false;
		}
	}
	
	public function getUUID(){
		return $this->uuid;
	}

	public function getAfscUUID(){
		return $this->afscUUID;
	}

	public function getQuestionList(){
		return $this->questionList;
	}

	public function getTotalQuestions(){
		return $this->totalQuestions;
	}

	public function getUserUUID(){
		return $this->userUUID;
	}

	public function getDateCreated(){
		return $this->dateCreated;
	}

	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}

	public function setAfscUUID($afscUUID){
		$this->afscUUID = $afscUUID;
		return true;
	}

	public function setQuestionList($questionList){
		$this->questionList = $questionList;
		return true;
	}

	public function setTotalQuestions($totalQuestions){
		$this->totalQuestions = $totalQuestions;
		return true;
	}

	public function setUserUUID($userUUID){
		$this->userUUID = $userUUID;
		return true;
	}

	public function setDateCreated($dateCreated){
		$this->dateCreated = $dateCreated;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}