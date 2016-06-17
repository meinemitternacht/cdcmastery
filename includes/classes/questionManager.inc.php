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

    public $error;
	
	public function __construct(mysqli $db, log $log, afsc $afsc, answerManager $answer){
		$this->uuid = parent::genUUID();
		$this->db = $db;
		$this->log = $log;
		$this->afsc = $afsc;
		$this->answer = $answer;
	}
	
	public function listQuestions(){
		$res = $this->db->query("SELECT uuid, afscUUID, questionText FROM questionData");
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$questionArray[$row['uuid']]['afscUUID'] = $row['afscUUID'];
				$questionArray[$row['uuid']]['questionText'] = $row['questionText'];
			}
				
			$res->close();
            if(isset($questionArray)) {
                return $questionArray;
            }
            else{
                return false;
            }
		}
		else{
			$this->error = "There are no questions in the database.";
			$res->close();
			return false;
		}
	}

    public function listQuestionsForAFSC($limitRows=false,$randomOrder=false){
        if(!$this->afscUUID){
            if($this->afsc->getUUID()){
                $this->afscUUID = $this->afsc->getUUID();
            }
            else {
                return false;
            }
        }

        if($limitRows){
            if($randomOrder){
                $stmt = $this->db->prepare("SELECT uuid, RAND() AS rnd FROM questionData WHERE afscUUID = ? ORDER BY rnd ASC LIMIT 0, ?");
            }
            else{
                $stmt = $this->db->prepare("SELECT uuid FROM questionData WHERE afscUUID = ? ORDER BY questionText ASC LIMIT 0, ?");
            }
            $stmt->bind_param("si",$this->afscUUID, $limitRows);
        }
        else{
            if($randomOrder){
                $stmt = $this->db->prepare("SELECT uuid, RAND() AS rnd FROM questionData WHERE afscUUID = ? ORDER BY rnd ASC");
            }
            else{
                $stmt = $this->db->prepare("SELECT uuid FROM questionData WHERE afscUUID = ? ORDER BY questionText ASC");
            }

            $stmt->bind_param("s",$this->afscUUID);
        }

        if($stmt->execute()){
            if($randomOrder){
                $stmt->bind_result($uuid,$randomValue);
            }
            else{
                $stmt->bind_result($uuid);
            }

            $uuidList = Array();

            while($stmt->fetch()){
                $uuidList[] = $uuid;
            }

            $stmt->close();

            if(sizeof($uuidList) > 0){
                return $uuidList;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = "Sorry, we could not retrieve the question list from the database.";
            $sqlError = $stmt->error;
            $stmt->close();

            $this->log->setAction("ERROR_QUESTION_LIST");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("ERROR",$sqlError);
            $this->log->saveEntry();

            return false;
        }
    }
	
	public function loadQuestion($uuid){
		if($this->queryQuestionFOUO($uuid)){
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, AES_DECRYPT(questionText,'".$this->getEncryptionKey()."') AS questionText FROM questionData WHERE uuid = ?");
            $this->fouo = true;
		}
		else{
			$stmt = $this->db->prepare("SELECT uuid, afscUUID, questionText FROM questionData WHERE uuid = ?");
		}
		
		$stmt->bind_param("s",$uuid);
			
		if($stmt->execute()){
			$stmt->bind_result($uuid, $afscUUID, $questionText);
            $stmt->fetch();
            $stmt->close();

            $this->uuid = $uuid;
            $this->afscUUID = $afscUUID;
            $this->questionText = $questionText;

            if(empty($this->questionText) || empty($this->uuid)){
                return false;
            }
            else{
                return true;
            }
		}
		else{
            $this->error = "Sorry, we could not retrieve the question from the database.";
            $sqlError = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_QUESTION_LOAD");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$sqlError);
			$this->log->saveEntry();

			return false;
		}
	}

    public function getArchivedQuestionText($uuid){
        $stmt = $this->db->prepare("SELECT questionText FROM questionDataArchived WHERE uuid = ?");
        $stmt->bind_param("s",$uuid);

        if($stmt->execute()){
            $stmt->bind_result($questionText);
            $stmt->fetch();
            $stmt->close();

            if(!empty($questionText)){
                return $questionText;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = "Sorry, we could not retrieve the question from the archive database.";
            $sqlError = $stmt->error;
            $stmt->close();

            $this->log->setAction("ERROR_QUESTION_ARCHIVED_GET_TEXT");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("ERROR",$sqlError);
            $this->log->saveEntry();

            return false;
        }
    }
	
	public function saveQuestion(){
		if($this->fouo){
			$stmt = $this->db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText) VALUES (?,?,AES_ENCRYPT(?,'".$this->getEncryptionKey()."'))
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	afscUUID=VALUES(afscUUID),
																	questionText=VALUES(questionText)");
		}
		else{
			$stmt = $this->db->prepare("INSERT INTO questionData (uuid, afscUUID, questionText) VALUES (?,?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	afscUUID=VALUES(afscUUID),
																	questionText=VALUES(questionText)");
		}
		
		$stmt->bind_param("sss",$this->uuid,$this->afscUUID,$this->questionText);
		
		if($stmt->execute()){
			$stmt->close();
			return true;
		}
		else{
            $sqlError = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_QUESTION_SAVE");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$sqlError);
			$this->log->saveEntry();
		
			$this->error = "Sorry, we could not save the question data to the database.";
			return false;
		}
	}

    public function archiveQuestion($questionUUID){
        if(!$this->verifyQuestion($questionUUID)){
            $this->error = "That question does not exist.";
            return false;
        }
        else {
            $this->loadQuestion($questionUUID);
        }

        $stmt = $this->db->prepare("INSERT INTO questionDataArchived (uuid,afscUUID,questionText) VALUES (?,?,?)
                                    ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
                                                            afscUUID=VALUES(afscUUID),
                                                            questionText=VALUES(questionText)");
        $stmt->bind_param("sss",$this->getUUID(),$this->getAFSCUUID(),$this->questionText);

        if($stmt->execute()){
            $stmt->close();
            unset($stmt);

            $answerArray = $this->loadAssociatedAnswers();

            if(!empty($answerArray)){
                if(is_array($answerArray)){
                    $error = false;
                    foreach($answerArray as $answerUUID => $answerData){
                        $stmt = $this->db->prepare("INSERT INTO answerDataArchived (uuid,answerCorrect,answerText,questionUUID) VALUES (?,?,?,?)
                                                    ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
                                                                            answerCorrect=VALUES(answerCorrect),
                                                                            answerText=VALUES(answerText),
                                                                            questionUUID=VALUES(questionUUID)");
                        $stmt->bind_param("siss",$answerUUID,$answerData['answerCorrect'],$answerData['answerText'],$this->getUUID());

                        if(!$stmt->execute()){
                            $sqlError = $stmt->error;
                            $stmt->close();

                            $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                            $this->log->setDetail("Answer UUID",$answerUUID);
                            $this->log->setDetail("MySQL Error",$sqlError);
                            $this->log->setDetail("Question UUID",$this->getUUID());
                            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                            $this->log->saveEntry();

                            $error = true;
                        }

                        $stmt->close();
                        $answerUUIDList[] = $answerUUID;
                    }

                    if(!$error){
                        if(isset($answerUUIDList) && is_array($answerUUIDList)){
                            $adError = false;
                            foreach($answerUUIDList as $answerDelete){
                                if(!$this->answer->deleteAnswer($answerDelete)){
                                    $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                                    $this->log->setDetail("Error",$this->answer->error);
                                    $this->log->setDetail("Answer UUID",$answerDelete);
                                    $this->log->setDetail("Question UUID",$this->getUUID());
                                    $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                                    $this->log->saveEntry();

                                    $adError = true;
                                }
                            }

                            if(!$adError){
                                $stmt = $this->db->prepare("DELETE FROM questionData WHERE uuid = ?");
                                $stmt->bind_param("s",$this->getUUID());

                                if($stmt->execute()){
                                    $this->log->setAction("QUESTION_ARCHIVE_COMPLETE");
                                    $this->log->setDetail("Question UUID",$this->getUUID());
                                    $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                                    $this->log->saveEntry();

                                    return true;
                                }
                                else{
                                    $sqlError = $stmt->error;

                                    $stmt->close();
                                    $this->log->setAction("ERROR_QUESTION_ARCHIVE");
                                    $this->log->setDetail("Error","Could not delete question after archiving.");
                                    $this->log->setDetail("MySQL Error",$sqlError);
                                    $this->log->setDetail("Question UUID",$this->getUUID());
                                    $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                                    $this->log->saveEntry();

                                    return false;
                                }
                            }
                            else {
                                return false;
                            }
                        }
                        else{
                            $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                            $this->log->setDetail("Error","Answer UUID List was not an array.");
                            $this->log->setDetail("Question UUID",$this->getUUID());
                            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                            $this->log->saveEntry();

                            return false;
                        }
                    }
                    else{
                        return false;
                    }
                }
                else{
                    $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                    $this->log->setDetail("Error","Answer List was not an array.");
                    $this->log->setDetail("Question UUID",$this->getUUID());
                    $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                    $this->log->saveEntry();

                    return false;
                }
            }
            else{
                $this->log->setAction("ERROR_QUESTION_ARCHIVE");
                $this->log->setDetail("Error","Could not load answer data.");
                $this->log->setDetail("Answer Class Error",$this->answer->error);
                $this->log->setDetail("Question UUID",$this->getUUID());
                $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->saveEntry();

                return false;
            }
        }
        else{
            $sqlError = $stmt->error;
            $stmt->close();

            $this->log->setAction("ERROR_QUESTION_ARCHIVE");
            $this->log->setDetail("MySQL Error",$sqlError);
            $this->log->setDetail("Question UUID",$this->getUUID());
            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->saveEntry();

            return false;
        }
    }

    public function verifyQuestion($questionUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS resultCount FROM questionData WHERE uuid = ?");
        $stmt->bind_param("s",$questionUUID);

        if($stmt->execute()){
            $stmt->bind_result($resultCount);
            $stmt->fetch();
            $stmt->close();

            if($resultCount > 0){
                return true;
            }
            else{
                $this->error = "That question does not exist.";
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            $stmt->close();

            $this->log->setAction("ERROR_QUESTION_VERIFY");
            $this->log->setDetail("MySQL Error",$this->error);
            $this->log->setDetail("Question UUID",$questionUUID);
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->saveEntry();

            return false;
        }
    }
	
	public function loadAssociatedAnswers($questionUUID=false,$sortAnswers=false){
		if($questionUUID){
			$this->answer->setQuestionUUID($questionUUID);

            if($sortAnswers) {
                $this->answer->sortAnswers = true;
            }

            $answerArray = $this->answer->listAnswersByQuestion();
			
			if($answerArray){
				return $answerArray;
			}
			else{
				return false;
			}
		}
        elseif($this->uuid){
            $this->answer->setQuestionUUID($this->uuid);

            if($sortAnswers) {
                $this->answer->sortAnswers = true;
            }

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
			$stmt->fetch();
			$stmt->close();

            if($fouoStatus){
                return true;
            }
            else{
                return false;
            }
		}
		else{
            $sqlError = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_QUESTION_QUERY_FOUO");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$sqlError);
			$this->log->setDetail("QUESTION UUID",$questionUUID);
			$this->log->saveEntry();

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
		return $this->questionText;
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
		$this->questionText = $questionText;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}