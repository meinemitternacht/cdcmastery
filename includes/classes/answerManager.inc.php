<?php
class answer extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;	
	public $fouo;
	
	public $uuid;
	public $answerText;
	public $answerCorrect;
	public $questionUUID;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function listAnswers(){
		$res = $this->db->query("SELECT uuid, answerText, answerCorrect, questionUUID FROM answerData");
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$answerArray[$row['uuid']]['answerText'] = $row['answerText'];
				$answerArray[$row['uuid']]['answerCorrect'] = $row['answerCorrect'];
				$answerArray[$row['uuid']]['questionUUID'] = $row['questionUUID'];
			}
			
			$res->close();
			return $answerArray;
		}
		else{
			$this->error[] = "There are no answers in the database.";
			$res->close();
			return false;
		}
	}
	
	public function listAnswersByQuestion(){
		if($this->questionUUID){
			if($this->fouo){
				$stmt = $this->db->prepare("SELECT uuid, AES_DECRYPT(answerText,'".$this->getEncryptionKey()."') AS answerText, answerCorrect FROM answerData WHERE questionUUID = ?");
			}
			else{
				$stmt = $this->db->prepare("SELECT uuid, answerText, answerCorrect FROM answerData WHERE questionUUID = ?");
			}
			
			$stmt->bind_param("s",$this->questionUUID);
			
			if($stmt->execute()){
				$stmt->bind_result($uuid, $answerText, $answerCorrect);
				while($stmt->fetch()){
					$answerArray[$uuid]['answerText'] = $answerText;
					$answerArray[$uuid]['answerCorrect'] = $answerCorrect;
				}

				$stmt->close();
				return $answerArray;
			}
			else{
				$this->log->setAction("MYSQL_ERROR");
				$this->log->setDetail("CALLING FUNCTION","answer->listAnswersByQuestion()");
				$this->log->setDetail("ERROR",$stmt->error);
				$this->log->saveEntry();
				
				$this->error[] = "Sorry, we could not retrieve the answers from the database.";
				$stmt->close();
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	public function loadAnswer($uuid){
		if($this->fouo){
			$stmt = $this->db->prepare("SELECT uuid, AES_DECRYPT(answerText,'".$this->getEncryptionKey()."') AS answerText, answerCorrect, questionUUID FROM answerData WHERE uuid = ?");
		}
		else{
			$stmt = $this->db->prepare("SELECT uuid, answerText, answerCorrect, questionUUID FROM answerData WHERE uuid = ?");
		}
		
		$stmt->bind_param("s",$uuid);
			
		if($stmt->execute()){
			$stmt->bind_result($uuid, $answerText, $answerCorrect, $questionUUID);
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->answerText = $answerText;
				$this->answerCorrect = $answerCorrect;
				$this->questionUUID = $questionUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","answer->loadAnswer()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not retrieve the answer from the database.";
			$stmt->close();
			return false;
		}
	}
	
	public function saveAnswer(){
		if($this->fouo){
			$stmt = $this->db->prepare("INSERT INTO answerData (uuid, answerText, answerCorrect, questionUUID) VALUES (?,AES_ENCRYPT(?,'".$this->getEncryptionKey()."'),?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	answerText=AES_ENCRYPT(VALUES(answerText),'".$this->getEncryptionKey()."'),
																	answerCorrect=VALUES(answerCorrect),
																	questionUUID=VALUES(questionUUID)");
		}
		else{
			$stmt = $this->db->prepare("INSERT INTO answerData (uuid, answerText, answerCorrect, questionUUID) VALUES (?,?,?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	answerText=VALUES(answerText),
																	answerCorrect=VALUES(answerCorrect),
																	questionUUID=VALUES(questionUUID)");
		}
		
		$stmt->bind_param("ssis",	$this->uuid,
									$this->answerText,
									$this->answerCorrect,
									$this->questionUUID);
		
		if($stmt->execute()){
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","answer->saveAnswer()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not save the answer data to the database.";
			$stmt->close();
			return false;
		}
	}
	
	public function getFOUO(){
		return $this->fouo;
	}
	
	public function getUUID(){
		return $this->uuid;
	}
	
	public function getAnswerText(){
		return $this->answerText;
	}
	
	public function getAnswerCorrect(){
		return $this->answerCorrect;
	}
	
	public function getQuestionUUID(){
		return $this->questionUUID;
	}
	
	public function setFOUO($fouo){
		$this->fouo = $fouo;
		return true;
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setAnswerText($answerText){
		$this->answerText = htmlspecialchars_decode($answerText);
		return true;
	}
	
	public function setAnswerCorrect($answerCorrect){
		$this->answerCorrect = $answerCorrect;
		return true;
	}
	
	public function setQuestionUUID($questionUUID){
		$this->questionUUID = $questionUUID;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}