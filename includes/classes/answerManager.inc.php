<?php

class answerManager extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;	
	public $fouo;
	
	public $uuid;
	public $answerText;
	public $answerCorrect;
	public $questionUUID;

	public $sortAnswers;
	
	public function __construct(mysqli $db, log $log){
		$this->uuid = parent::genUUID();
		$this->db = $db;
		$this->log = $log;
	}

	public function newAnswer(){
		$this->uuid = $this->genUUID();
		$this->error = false;
		$this->fouo = false;
		$this->answerText = false;
		$this->answerCorrect = false;
		$this->questionUUID = false;
		return true;
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
			if(isset($answerArray) && is_array($answerArray) && !empty($answerArray)){
				return $answerArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error[] = "There are no answers in the database.";
			$res->close();
			return false;
		}
	}
	
	public function listAnswersByQuestion(){
		if($this->questionUUID){
			if($this->sortAnswers){
				if($this->fouo){
					$query = "SELECT uuid, AES_DECRYPT(answerText,'".$this->getEncryptionKey()."') AS answerText, answerCorrect, RAND() AS rnd FROM answerData WHERE questionUUID = ? ORDER BY answerText ASC";
				}
				else{
					$query = "SELECT uuid, answerText, answerCorrect, RAND() AS rnd FROM answerData WHERE questionUUID = ? ORDER BY answerText ASC";
				}
			}
			else{
				if($this->fouo){
					$query = "SELECT uuid, AES_DECRYPT(answerText,'".$this->getEncryptionKey()."') AS answerText, answerCorrect, RAND() AS rnd FROM answerData WHERE questionUUID = ? ORDER BY rnd";
				}
				else{
					$query = "SELECT uuid, answerText, answerCorrect, RAND() AS rnd FROM answerData WHERE questionUUID = ? ORDER BY rnd";
				}
			}

			$stmt = $this->db->prepare($query);
			$stmt->bind_param("s",$this->questionUUID);
			
			if($stmt->execute()){
				$stmt->bind_result($uuid, $answerText, $answerCorrect, $randomNumber);
				while($stmt->fetch()){
					$answerArray[$uuid]['answerText'] = $answerText;
					$answerArray[$uuid]['answerCorrect'] = $answerCorrect;
				}

				$stmt->close();

				if(empty($answerArray)){
					return false;
				}
				else {
					return $answerArray;
				}
			}
			else{
				$this->log->setAction("ERROR_ANSWERS_LIST");
				$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
				$this->log->setDetail("ERROR",$stmt->error);
				$this->log->setDetail("Question UUID",$this->questionUUID);
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
	
	public function loadAnswer($answerUUID){
		if($this->fouo){
			$stmt = $this->db->prepare("SELECT uuid, AES_DECRYPT(answerText,'".$this->getEncryptionKey()."') AS answerText, answerCorrect, questionUUID FROM answerData WHERE uuid = ?");
		}
		else{
			$stmt = $this->db->prepare("SELECT uuid, answerText, answerCorrect, questionUUID FROM answerData WHERE uuid = ?");
		}
		
		$stmt->bind_param("s",$answerUUID);

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
			$this->log->setAction("ERROR_ANSWERS_LOAD");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("UUID",$answerUUID);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not retrieve the answer from the database.";
			$stmt->close();
			return false;
		}
	}

	public function loadArchivedAnswer($answerUUID){
		$stmt = $this->db->prepare("SELECT uuid, answerText, answerCorrect, questionUUID FROM answerDataArchived WHERE uuid = ?");

		$stmt->bind_param("s",$answerUUID);

		if($stmt->execute()){
			$stmt->bind_result($uuid, $answerText, $answerCorrect, $questionUUID);
			$stmt->fetch();

            $this->uuid = $uuid;
            $this->answerText = $answerText;
            $this->answerCorrect = $answerCorrect;
            $this->questionUUID = $questionUUID;

			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_ANSWERS_LOAD");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("UUID",$answerUUID);
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
																	answerText=VALUES(answerText),
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
			$this->log->setAction("ERROR_ANSWERS_SAVE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = "Sorry, we could not save the answer data to the database.";
			$stmt->close();
			return false;
		}
	}

    public function deleteAnswer($answerUUID){
        if(!$this->verifyAnswer($answerUUID)){
            $this->error = "That answer does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM answerData WHERE uuid = ?");
        $stmt->bind_param("s",$answerUUID);

        if($stmt->execute()){
            $stmt->close();
            return true;
        }
        else{
            $this->log->setAction("ERROR_ANSWER_DELETE");
            $this->log->setDetail("Answer UUID",$answerUUID);
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    public function verifyAnswer($answerUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS answerCount FROM answerData WHERE uuid = ?");
        $stmt->bind_param("s",$answerUUID);

        if($stmt->execute()){
            $stmt->bind_result($answerCount);
            $stmt->fetch();

            if($answerCount > 0){
                $stmt->close();
                return true;
            }
            else{
                $stmt->close();
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

	public function getCorrectAnswer($questionUUID){
		$stmt = $this->db->prepare("SELECT uuid FROM answerData WHERE answerCorrect = 1 AND questionUUID = ?");
		$stmt->bind_param("s",$questionUUID);

		if($stmt->execute()){
			$stmt->bind_result($answerUUID);
			$stmt->fetch();
			$stmt->close();

			if(!empty($answerUUID)){
				return $answerUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
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