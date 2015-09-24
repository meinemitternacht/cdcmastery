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

    public $error;
	
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

    public function listQuestionsForAFSC(){
        if(!$this->afscUUID){
            if($this->afsc->getUUID()){
                $this->afscUUID = $this->afsc->getUUID();
            }
            else {
                return false;
            }
        }

        $stmt = $this->db->prepare("SELECT uuid FROM questionData WHERE afscUUID = ? ORDER BY questionText ASC");
        $stmt->bind_param("s",$this->afscUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid);

            $uuidList = Array();

            while($stmt->fetch()){
                $uuidList[] = $uuid;
            }

            if(sizeof($uuidList) > 0){
                $stmt->close();
                return $uuidList;
            }
            else{
                $stmt->close();
                return false;
            }
        }
        else{
            $this->log->setAction("ERROR_QUESTION_LIST");
            $this->log->setDetail("CALLING FUNCTION","question->listQuestionsForAFSC()");
            $this->log->setDetail("ERROR",$stmt->error);
            $this->log->saveEntry();

            $this->error = "Sorry, we could not retrieve the question list from the database.";
            $stmt->close();
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
			$this->log->setAction("ERROR_QUESTION_LOAD");
			$this->log->setDetail("CALLING FUNCTION","question->loadQuestion()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error = "Sorry, we could not retrieve the question from the database.";
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
			$this->log->setAction("ERROR_QUESTION_SAVE");
			$this->log->setDetail("CALLING FUNCTION","question->saveQuestion()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->saveEntry();
		
			$this->error = "Sorry, we could not save the question data to the database.";
			$stmt->close();
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
                            $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                            $this->log->setDetail("Answer UUID",$answerUUID);
                            $this->log->setDetail("MySQL Error",$stmt->error);
                            $this->log->setDetail("Question UUID",$this->getUUID());
                            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                            $this->log->saveEntry();

                            if($error == false){
                                $error = true;
                            }
                            else{
                                $error = true;
                            }
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
                                    $this->log->saveEntry();

                                    if($adError == false){
                                        $adError = true;
                                    }
                                    else{
                                        $adError = true;
                                    }
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
                                    $this->log->setAction("ERROR_QUESTION_ARCHIVE");
                                    $this->log->setDetail("Error","Could not delete question after archiving.");
                                    $this->log->setDetail("MySQL Error",$stmt->error);
                                    $this->log->setDetail("Question UUID",$this->getUUID());
                                    $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                                    $this->log->saveEntry();

                                    return false;
                                }
                            }
                        }
                        else{
                            $this->log->setAction("ERROR_ANSWER_ARCHIVE");
                            $this->log->setDetail("Error","Answer UUID List was not an array.");
                            $this->log->setDetail("Question UUID",$this->getUUID());
                            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                            $this->log->saveEntry();
                        }
                    }
                }
            }
            else{
                $this->log->setAction("ERROR_QUESTION_ARCHIVE");
                $this->log->setDetail("Error","Could not load answer data.");
                $this->log->setDetail("Answer Class Error",$this->answer->error);
                $this->log->setDetail("Question UUID",$this->getUUID());
                $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
                $this->log->saveEntry();

                return false;
            }
        }
        else{
            $this->log->setAction("ERROR_QUESTION_ARCHIVE");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Question UUID",$this->getUUID());
            $this->log->setDetail("AFSC UUID",$this->getAFSCUUID());
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

            if($resultCount > 0){
                $stmt->close();
                return true;
            }
            else{
                $this->error = "That question does not exist.";
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
            if(isset($tempFOUO)) {
                return $tempFOUO;
            }
            else{
                return false;
            }
		}
		else{
			$this->log->setAction("ERROR_QUESTION_QUERY_FOUO");
			$this->log->setDetail("CALLING FUNCTION", "questionManager->queryQuestionFOUO");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("QUESTION UUID",$questionUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return false;
		}
	}

    public function addSet($setName,$parentAFSC){
        if(!$this->afsc->verifyAFSC($parentAFSC)){
            $this->error = "The parent AFSC for that set does not exist.";
            return false;
        }

        $uuid = parent::genUUID();

        $stmt = $this->db->prepare("INSERT INTO setList (uuid, setName, parentAFSCUUID)
                                    VALUES (?,?,?)
                                    ON DUPLICATE KEY UPDATE
                                      uuid=VALUES(uuid),
                                      setName=VALUES(setName),
                                      parentAFSCUUID=VALUES(parentAFSCUUID)");
        $stmt->bind_param("sss",$uuid,$setName,$parentAFSC);

        if($stmt->execute()){
            $this->log->setAction("SET_ADD");
            $this->log->setDetail("Set Name",$setName);
            $this->log->setDetail("Parent AFSC",$parentAFSC);
            $this->log->setDetail("UUID",$uuid);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($parentAFSC));
            $this->log->saveEntry();

            return true;
        }
        else{
            $this->log->setAction("ERROR_SET_ADD");
            $this->log->setDetail("Set Name",$setName);
            $this->log->setDetail("Parent AFSC",$parentAFSC);
            $this->log->setDetail("UUID",$uuid);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($parentAFSC));
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            return false;
        }
    }

    public function editSet($setUUID,$setName,$parentAFSC){
        if(!$this->verifySet($setUUID)){
            $this->error = "That set does not exist.";
            return false;
        }

        if(!$this->afsc->verifyAFSC($parentAFSC)){
            $this->error = "The parent AFSC for that set does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("UPDATE setList
                                    SET setName = ?,
                                        parentAFSCUUID = ?
                                    WHERE uuid = ?");
        $stmt->bind_param("sss",$setName,$parentAFSC,$setUUID);

        if($stmt->execute()){
            $this->log->setAction("SET_EDIT");
            $this->log->setDetail("Set Name",$setName);
            $this->log->setDetail("Parent AFSC",$parentAFSC);
            $this->log->setDetail("UUID",$setUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($parentAFSC));
            $this->log->saveEntry();

            $stmt->close();

            return true;
        }
        else{
            $this->log->setAction("ERROR_SET_EDIT");
            $this->log->setDetail("Set Name",$setName);
            $this->log->setDetail("Parent AFSC",$parentAFSC);
            $this->log->setDetail("UUID",$setUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($parentAFSC));
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error = $stmt->error;
            $stmt->close();

            return false;
        }
    }

    public function deleteSet($setUUID,$deleteVolumes=false,$deleteQuestions=false){
        if(!$this->verifySet($setUUID)){
            $this->error = "That set does not exist.";
            return false;
        }

        if($deleteQuestions){
            /*
             * If we want to log this, need to get a list of questions first.
             */
            $stmt = $this->db->prepare("SELECT uuid, afscUUID FROM questionData WHERE setUUID = ?");
            $stmt->bind_param("s",$setUUID);

            if($stmt->execute()){
                $stmt->bind_result($uuid,$afscUUID);
                while($stmt->fetch()){
                    $uuidList[] = $uuid;
                    $afscVal = $afscUUID;
                }

                if(!empty($uuidList) && !empty($afscVal)){
                    $this->log->setAction("QUESTION_DELETE_MULTIPLE");
                    $this->log->setDetail("Method","questionManager->deleteSet");
                    $this->log->setDetail("AFSC",$afscVal);
                    $this->log->setDetail("AFSC Name",$this->afsc->getAFSCName($afscVal));
                    $this->log->setDetail("# Questions",count($uuidList));
                    $this->log->setDetail("QuestionList",implode(",",$uuidList));
                    $this->log->saveEntry();
                }
                else{
                    /*
                     * There were no deleted questions to log.
                     */
                }
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("ERROR_QUESTION_DELETE_MULTIPLE");
                $this->log->setDetail("Method","questionManager->deleteSet");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->saveEntry();
            }

            $stmt->close();
            unset($stmt);

            $stmt = $this->db->prepare("DELETE FROM questionData WHERE setUUID = ?");
            $stmt->bind_param("s",$setUUID);

            if(!$stmt->execute()){
                $this->error = $stmt->error;
                $this->log->setAction("ERROR_QUESTION_DELETE");
                $this->log->setDetail("Method","questionManager->deleteSet");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->setDetail("Set UUID",$setUUID);
                $this->log->saveEntry();
            }

            $stmt->close();
            unset($stmt);
        }

        if($deleteVolumes){
            $volumeList = $this->listChildVolumes($setUUID);

            if(!empty($volumeList)){
                $delVolError = false;

                foreach($volumeList as $volumeUUID){
                    if(!$this->deleteVolume($volumeUUID)){
                        if($delVolError == false){
                            $delVolError = true;
                        }
                        else{
                            $delVolError = true;
                        }
                    }
                }
            }
        }

        $setAFSC = $this->getSetAFSC($setUUID);

        $stmt = $this->db->prepare("DELETE FROM setList WHERE uuid = ?");
        $stmt->bind_param("s",$setUUID);

        if($stmt->execute()){
            $this->log->setAction("SET_DELETE");
            $this->log->setDetail("UUID",$setUUID);
            $this->log->setDetail("Set AFSC",$setAFSC);
            if($deleteQuestions)
                $this->log->setDetail("Delete Questions","True");

            if($deleteVolumes)
                $this->log->setDetail("Delete Volumes","True");

            $this->log->saveEntry();
            $stmt->close();

            return true;
        }
        else{
            $this->log->setAction("ERROR_SET_DELETE");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("UUID",$setUUID);
            $this->log->setDetail("Set AFSC",$setAFSC);
            if($deleteQuestions)
                $this->log->setDetail("Delete Questions","True");

            if($deleteVolumes)
                $this->log->setDetail("Delete Volumes","True");

            $this->log->saveEntry();
            $this->error = $stmt->error;
            $stmt->close();

            return false;
        }
    }

    public function verifySet($setUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM setList WHERE uuid = ?");
        $stmt->bind_param("s",$setUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();

            if($count > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function getSetName($setUUID){
        $stmt = $this->db->prepare("SELECT setName FROM setList WHERE uuid = ?");
        $stmt->bind_param("s",$setUUID);

        if($stmt->execute()){
            $stmt->bind_result($setName);
            $stmt->fetch();

            if(!empty($setName)){
                return $setName;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function getSetAFSC($setUUID){
        $stmt = $this->db->prepare("SELECT parentAFSCUUID FROM setList WHERE uuid = ?");
        $stmt->bind_param("s",$setUUID);

        if($stmt->execute()){
            $stmt->bind_result($parentAFSCUUID);
            $stmt->fetch();

            if(!empty($parentAFSCUUID)){
                return $parentAFSCUUID;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function addVolume($volumeName,$volumeVersion,$afscUUID,$parentSetUUID){
        if(!$this->afsc->verifyAFSC($afscUUID)){
            $this->error = "The parent AFSC for that volume does not exist.";
            return false;
        }

        if(!$this->verifySet($parentSetUUID)){
            $this->error = "The parent set for that volume does not exist.";
            return false;
        }

        $uuid = parent::genUUID();

        $stmt = $this->db->prepare("INSERT INTO volumeList (uuid, volumeName, volumeVersion, afscUUID, parentSetUUID)
                                    VALUES (?,?,?,?,?)
                                    ON DUPLICATE KEY UPDATE
                                      uuid=VALUES(uuid),
                                      volumeName=VALUES(volumeName),
                                      volumeVersion=VALUES(volumeVersion),
                                      afscUUID=VALUES(afscUUID),
                                      parentSetUUID=VALUES(parentSetUUID)");
        $stmt->bind_param("sssss",$uuid,$volumeName,$volumeVersion,$afscUUID,$parentSetUUID);

        if($stmt->execute()){
            $this->log->setAction("VOLUME_ADD");
            $this->log->setDetail("UUID",$uuid);
            $this->log->setDetail("Volume Name",$volumeName);
            $this->log->setDetail("Volume Version",$volumeVersion);
            $this->log->setDetail("Parent AFSC",$afscUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($afscUUID));
            $this->log->setDetail("Parent Set",$parentSetUUID);
            $this->log->setDetail("Parent Set Name",$this->getSetName($parentSetUUID));
            $this->log->saveEntry();

            return true;
        }
        else{
            $this->log->setAction("ERROR_VOLUME_ADD");
            $this->log->setDetail("UUID",$uuid);
            $this->log->setDetail("Volume Name",$volumeName);
            $this->log->setDetail("Volume Version",$volumeVersion);
            $this->log->setDetail("Parent AFSC",$afscUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($afscUUID));
            $this->log->setDetail("Parent Set",$parentSetUUID);
            $this->log->setDetail("Parent Set Name",$this->getSetName($parentSetUUID));
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            return false;
        }
    }

    public function editVolume($volumeUUID,$volumeName,$volumeVersion,$afscUUID,$parentSetUUID){
        if(!$this->verifyVolume($volumeUUID)){
            $this->error = "That volume does not exist.";
            return false;
        }

        if(!$this->afsc->verifyAFSC($afscUUID)){
            $this->error = "The parent AFSC for the volume does not exist.";
            return false;
        }

        if(!$this->verifySet($parentSetUUID)){
            $this->error = "The parent set for the volume does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("UPDATE volumeList
                                    SET volumeName = ?,
                                        volumeVersion = ?,
                                        afscUUID = ?,
                                        parentSetUUID = ?
                                    WHERE uuid = ?");
        $stmt->bind_param("sssss",$volumeName,$volumeVersion,$afscUUID,$parentSetUUID,$volumeUUID);

        if($stmt->execute()){
            $this->log->setAction("VOLUME_EDIT");
            $this->log->setDetail("UUID",$volumeUUID);
            $this->log->setDetail("Volume Name",$volumeName);
            $this->log->setDetail("Volume Version",$volumeVersion);
            $this->log->setDetail("Parent AFSC",$afscUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($afscUUID));
            $this->log->setDetail("Parent Set",$parentSetUUID);
            $this->log->setDetail("Parent Set Name",$this->getSetName($parentSetUUID));
            $this->log->saveEntry();

            $stmt->close();

            return true;
        }
        else{
            $this->log->setAction("ERROR_VOLUME_EDIT");
            $this->log->setDetail("UUID",$volumeUUID);
            $this->log->setDetail("Volume Name",$volumeName);
            $this->log->setDetail("Volume Version",$volumeVersion);
            $this->log->setDetail("Parent AFSC",$afscUUID);
            $this->log->setDetail("Parent AFSC Name",$this->afsc->getAFSCName($afscUUID));
            $this->log->setDetail("Parent Set",$parentSetUUID);
            $this->log->setDetail("Parent Set Name",$this->getSetName($parentSetUUID));
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error = $stmt->error;
            $stmt->close();

            return false;
        }
    }

    public function deleteVolume($volumeUUID,$deleteQuestions=false){
        if(!$this->verifyVolume($volumeUUID)){
            $this->error = "That volume does not exist.";
            return false;
        }

        $volumeAFSC = $this->getVolumeAFSC($volumeUUID);

        if($deleteQuestions){
            /*
             * If we want to log this, need to get a list of questions first.
             */
            $stmt = $this->db->prepare("SELECT uuid, afscUUID FROM questionData WHERE volumeUUID = ?");
            $stmt->bind_param("s",$volumeUUID);

            if($stmt->execute()){
                $stmt->bind_result($uuid,$afscUUID);
                while($stmt->fetch()){
                    $uuidList[] = $uuid;
                    $afscVal = $afscUUID;
                }

                if(!empty($uuidList) && !empty($afscVal)){
                    $this->log->setAction("QUESTION_DELETE_MULTIPLE");
                    $this->log->setDetail("Method","questionManager->deleteVolume");
                    $this->log->setDetail("AFSC",$afscVal);
                    $this->log->setDetail("AFSC Name",$this->afsc->getAFSCName($afscVal));
                    $this->log->setDetail("# Questions",count($uuidList));
                    $this->log->setDetail("QuestionList",implode(",",$uuidList));
                    $this->log->saveEntry();
                }
                else{
                    /*
                     * There were no deleted questions to log.
                     */
                }
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("ERROR_QUESTION_DELETE_MULTIPLE");
                $this->log->setDetail("Method","questionManager->deleteVolume");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->saveEntry();
            }

            $stmt->close();
            unset($stmt);

            $stmt = $this->db->prepare("DELETE FROM questionData WHERE volumeUUID = ?");
            $stmt->bind_param("s",$volumeUUID);

            if(!$stmt->execute()){
                $this->error = $stmt->error;
                $this->log->setAction("ERROR_QUESTION_DELETE");
                $this->log->setDetail("Method","questionManager->deleteVolume");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->saveEntry();
            }

            $stmt->close();
            unset($stmt);
        }

        $stmt = $this->db->prepare("DELETE FROM volumeList WHERE uuid = ?");
        $stmt->bind_param("s",$volumeUUID);

        if($stmt->execute()){
            $this->log->setAction("VOLUME_DELETE");
            $this->log->setDetail("Volume UUID",$volumeUUID);
            $this->log->setDetail("Volume AFSC",$volumeAFSC);
            if($deleteQuestions)
                $this->log->setDetail("Delete Questions","True");

            $this->log->saveEntry();
            $stmt->close();

            return true;
        }
        else{
            $this->log->setAction("ERROR_VOLUME_DELETE");
            $this->log->setDetail("Volume UUID",$volumeUUID);
            $this->log->setDetail("Volume AFSC",$volumeAFSC);
            $this->log->setDetail("MySQL Error",$stmt->error);
            if($deleteQuestions)
                $this->log->setDetail("Delete Questions","True");

            $this->log->saveEntry();

            $this->error = $stmt->error;
            $stmt->close();

            return false;
        }
    }

    public function verifyVolume($volumeUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM volumeList WHERE uuid = ?");
        $stmt->bind_param("s",$volumeUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();

            if($count > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function getVolumeName($volumeUUID){
        $stmt = $this->db->prepare("SELECT volumeName FROM volumeList WHERE uuid = ?");
        $stmt->bind_param("s",$volumeUUID);

        if($stmt->execute()){
            $stmt->bind_result($volumeName);
            $stmt->fetch();

            if(!empty($volumeName)){
                return $volumeName;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function getVolumeAFSC($volumeUUID){
        $stmt = $this->db->prepare("SELECT afscUUID FROM volumeList WHERE uuid = ?");
        $stmt->bind_param("s",$volumeUUID);

        if($stmt->execute()){
            $stmt->bind_result($afscUUID);
            $stmt->fetch();

            if(!empty($afscUUID)){
                return $afscUUID;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function listChildSets($afscUUID){
        $stmt = $this->db->prepare("SELECT uuid FROM setList WHERE parentAFSCUUID = ? ORDER BY setName ASC");
        $stmt->bind_param("s",$afscUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid);

            while($stmt->fetch()){
                $uuidList[] = $uuid;
            }

            $stmt->close();

            if(!empty($uuidList)){
                return $uuidList;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function listChildVolumes($setUUID){
        $stmt = $this->db->prepare("SELECT uuid FROM volumeList WHERE parentSetUUID = ? ORDER BY volumeName ASC");
        $stmt->bind_param("s",$setUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid);

            while($stmt->fetch()){
                $uuidList[] = $uuid;
            }

            $stmt->close();

            if(!empty($uuidList)){
                return $uuidList;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            return false;
        }
    }

    public function listChildQuestions($volumeUUID){
        $stmt = $this->db->prepare("SELECT uuid FROM questionData WHERE volumeUUID = ?");
        $stmt->bind_param("s",$volumeUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid);

            while($stmt->fetch()){
                $uuidList[] = $uuid;
            }

            $stmt->close();

            if(!empty($uuidList)){
                return $uuidList;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
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