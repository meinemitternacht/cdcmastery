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
	
	public $answerData;
	
	public function __construct(mysqli $db, log $log, afsc $afsc, answerManager $answer){
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
		if($this->fouo){
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
	
	public function getFOUO(){
		return $this->fouo;
	}
	
	public function setFOUO($fouo){
		$this->fouo = $fouo;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}