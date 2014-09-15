<?php
class questionManager extends CDCMastery
{
	protected $db;
	protected $log;
	protected $afsc;
	protected $answer;
	
	public $fouo;
	
	public $uuid;
	public $afscUUID;
	public $questionText;
	public $volumeUUID;
	public $setUUID;
	
	public function __construct(mysqli $db, log $log, afsc $afsc, answerManager $answer){
		$this->uuid = parent::genUUID();
		$this->db = $db;
		$this->log = $log;
		$this->afsc = $afsc;
		$this->answer = $answer;
	}
	
	public function listQuestions(){
		$res = $this->db->query("SELECT uuid, afscUUID, questionText, volumeUUID, setUUID FROM questionData");
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$questionArray[$row['uuid']]['afscUUID'] = $row['afscUUID'];
				$questionArray[$row['uuid']]['questionText'] = $row['questionText'];
				$questionArray[$row['uuid']]['volumeUUID'] = $row['volumeUUID'];
				$questionArray[$row['uuid']]['setUUID'] = $row['setUUID'];
			}
				
			$res->close();
			return $questionArray;
		}
		else{
			$this->error[] = "There are no questions in the database.";
			$res->close();
			return false;
		}
	}
	
	public function loadQuestion($uuid){
		if($this->queryQuestionFOUO($uuid)){
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, AES_DECRYPT(questionText,'".$this->getEncryptionKey()."') AS questionText, volumeUUID, setUUID FROM questionData WHERE uuid = ?");
		}
		else{
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, questionText, volumeUUID, setUUID FROM questionData WHERE uuid = ?");
		}
		
		$stmt->bind_param("s",$uuid);
			
		if($stmt->execute()){
			$stmt->bind_result($uuid, $afscUUID, $questionText, $volumeUUID, $setUUID);
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->afscUUID = $afscUUID;
				$this->questionText = $questionText;
				$this->volumeUUID = $volumeUUID;
				$this->setUUID = $setUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","question->loadQuestion()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not retrieve the question from the database.";
			$stmt->close();
			return false;
		}
	}
	
	public function saveQuestion(){
		if($this->fouo){
			$stmt = $this->db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText, volumeUUID, setUUID) VALUES (?,?,AES_ENCRYPT(?,'".$this->getEncryptionKey()."'),?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	afscUUID=VALUES(afscUUID),
																	questionText=AES_ENCRYPT(VALUES(questionText),'".$this->getEncryptionKey()."'),
																	volumeUUID=VALUES(volumeUUID),
																	setUUID=VALUES(setUUID)");
		}
		else{
			$stmt = $this->db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText, volumeUUID, setUUID) VALUES (?,?,?,?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	afscUUID=VALUES(afscUUID),
																	questionText=VALUES(questionText),
																	volumeUUID=VALUES(volumeUUID),
																	setUUID=VALUES(setUUID)");
		}
		
		$stmt->bind_param("sssss",	$this->uuid,
									$this->afscUUID,
									$this->questionText,
									$this->volumeUUID,
									$this->setUUID);
		
		if($stmt->execute()){
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","question->saveQuestion()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not save the question data to the database.";
			$stmt->close();
			return false;
		}
	}
	
	public function loadAssociatedAnswers(){
		if($this->uuid){
			$this->answer->setQuestionUUID($this->uuid);
			
			$answerArray = $this->answer->listAnswersByQuestion();
			
			if($answerArray){
				return $answerArray;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	public function queryQuestionFOUO($questionUUID){
		$stmt = $this->db->prepare("SELECT `afscList`.`afscFOUO` FROM `questionData` LEFT JOIN `afscList` ON `afscList`.`uuid` = `questionData`.`afscUUID` WHERE `questionData`.`uuid` = ?");
		$stmt->bind_param("s",$questionUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($fouoStatus);
			
			while($stmt->fetch()){
				$tempFOUO = $fouoStatus;
			}
			
			$stmt->close();
			return $tempFOUO;
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION", "questionManager->queryQuestionFOUO");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("QUESTION UUID",$questionUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return false;
		}
	}
	
	public function getUUID(){
		return $this->uuid;
	}
	
	public function getFOUO(){
		return $this->fouo;
	}
	
	public function getAFSCUUID(){
		return $this->afscUUID;
	}
	
	public function getQuestionText(){
		return htmlspecialchars($this->questionText);
	}
	
	public function getVolumeUUID(){
		return $this->volumeUUID;
	}
	
	public function getSetUUID(){
		return $this->setUUID;
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setFOUO($fouo){
		$this->fouo = $fouo;
		return true;
	}
	
	public function setAFSCUUID($afscUUID){
		$this->afscUUID = $afscUUID;
		return true;
	}
	
	public function setQuestionText($questionText){
		$this->questionText = htmlspecialchars_decode($questionText);
		return true;
	}
	
	public function setVolumeUUID($volumeUUID){
		$this->volumeUUID = $volumeUUID;
		return true;
	}
	
	public function setSetUUID($setUUID){
		$this->setUUID = $setUUID;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}