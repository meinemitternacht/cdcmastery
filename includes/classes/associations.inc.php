<?php

class associations extends CDCMastery
{
	protected $db;
	protected $log;
	protected $user;
	protected $afsc;
	
	public $error;
	
	/*
	 * userAFSCAssociations
	 */
	public $uaaUUID;
	public $uaaUserUUID;
	public $afscUUID;
	public $userAuthorized;
	
	/*
	 * userSupervisorAssociations
	 */
	public $usaUUID;
	public $supervisorUUID;
	public $usaUserUUID;
	
	/*
	 * userTrainingManagerAssociations
	 */
	public $utmaUUID;
	public $trainingManagerUUID;
	public $utmaUserUUID;	
	
	public function __construct(mysqli $db, log $log, user $user, afsc $afsc){
		$this->db = $db;
		$this->log = $log;
		$this->user = $user;
		$this->afsc = $afsc;
	}

    public function listPendingAFSCAssociations(){
        $stmt = $this->db->prepare("SELECT `userAFSCAssociations`.`uuid`, userUUID, afscUUID, afscName
                                    FROM userAFSCAssociations
                                      LEFT JOIN afscList
                                      ON afscList.uuid = userAFSCAssociations.afscUUID
                                    WHERE userAuthorized = 0
                                    ORDER BY afscList.afscName ASC");

        if($stmt->execute()){
            $stmt->bind_result($assocUUID, $userUUID, $afscUUID,$afscName);

            while($stmt->fetch()){
                $pendingArray[$assocUUID]['userUUID'] = $userUUID;
                $pendingArray[$assocUUID]['afscUUID'] = $afscUUID;
                $pendingArray[$assocUUID]['afscName'] = $afscName;
            }

            $stmt->close();

            if(isset($pendingArray) && !empty($pendingArray)){
                return $pendingArray;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","userStatistics->listPendingAFSCAssociations()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }
	
	public function addAFSCAssociation($userUUID, $afscUUID, $userAuthorized=true){
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		if(!$this->afsc->verifyAFSC($afscUUID)){
			$this->error[] = "That AFSC does not exist.";
			return false;
		}
		
		$rowUUID = parent::genUUID();
		
		$stmt = $this->db->prepare("INSERT INTO userAFSCAssociations (uuid, userUUID, afscUUID, userAuthorized) VALUES (?,?,?,?)
										ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																userUUID=VALUES(userUUID),
																afscUUID=VALUES(afscUUID),
																userAuthorized=VALUES(userAuthorized)");
		
		$stmt->bind_param("sssi",$rowUUID,$userUUID,$afscUUID,$userAuthorized);
		
		if($stmt->execute()){
			if($userAuthorized) {
				$this->log->setAction("USER_ADD_AFSC_ASSOCIATION");
			}
			else{
				$this->log->setAction("USER_ADD_PENDING_AFSC_ASSOCIATION");
			}
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_ASSOCIATIONS_AFSC_ADD");
			$this->log->setDetail("Calling Function","associations->addUserAFSCAssociation()");
			$this->log->setDetail("userAuthorized",$userAuthorized);
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
				
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function deleteAFSCAssociation($userUUID, $afscUUID){
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		if(!$this->afsc->verifyAFSC($afscUUID)){
			$this->error[] = "That AFSC does not exist.";
			return false;
		}
		
		$stmt = $this->db->prepare("DELETE FROM userAFSCAssociations WHERE userUUID = ? AND afscUUID = ?");
		
		$stmt->bind_param("ss",$userUUID,$afscUUID);
		
		if($stmt->execute()){
			$this->log->setAction("USER_DELETE_AFSC_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_ASSOCIATIONS_AFSC_DELETE");
			$this->log->setDetail("Calling Function","associations->deleteAFSCAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
				
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}

    public function deleteUserAFSCAssociations($userUUID){
        if(!$this->user->verifyUser($userUUID)){
            $this->error[] = "That user does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM userAFSCAssociations WHERE userUUID = ?");

        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
            $this->log->setAction("USER_DELETE_AFSC_ASSOCIATIONS_ALL");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->saveEntry();

            $stmt->close();
            return true;
        }
        else{
            $this->log->setAction("ERROR_USER_DELETE_AFSC_ASSOCIATIONS_ALL");
            $this->log->setDetail("Calling Function","associations->deleteUserAFSCAssociations()");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error[] = $stmt->error;
            $stmt->close();
            return false;
        }
    }
	
	public function addPendingAFSCAssociation($userUUID, $afscUUID){
		if($this->addAFSCAssociation($userUUID, $afscUUID, false)){
			return true;
		}
		else{
			$this->error[] = "We could not add the pending AFSC association for that user.";
			return false;
		}
	}
	
	public function deletePendingAFSCAssociation($userUUID, $afscUUID){
		if($this->deleteAFSCAssociation($userUUID, $afscUUID)){
			return true;
		}
		else{
			$this->error[] = "We could not remove the pending AFSC association for that user.";
			return false;
		}
	}
	
	public function approvePendingAFSCAssociation($userUUID, $afscUUID){
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		if(!$this->afsc->verifyAFSC($afscUUID)){
			$this->error[] = "That AFSC does not exist.";
			return false;
		}
		
		$stmt = $this->db->prepare("UPDATE userAFSCAssociations SET userAuthorized=1 WHERE userUUID = ? AND afscUUID = ?");
		
		$stmt->bind_param("ss",$userUUID,$afscUUID);
		
		if($stmt->execute()){
			$this->log->setAction("USER_APPROVE_PENDING_AFSC_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_APPROVE_PENDING_AFSC_ASSOCIATION");
			$this->log->setDetail("Calling Function","associations->approvePendingAFSCAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function addSupervisorAssociation($supervisorUUID, $userUUID){
		if(!$this->user->verifyUser($supervisorUUID)){
			$this->error[] = "That supervisor does not exist.";
			return false;
		}
		
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		$rowUUID = parent::genUUID();
		
		$stmt = $this->db->prepare("INSERT INTO userSupervisorAssociations (uuid, supervisorUUID, userUUID) VALUES (?,?,?)
										ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																supervisorUUID=VALUES(supervisorUUID),
																userUUID=VALUES(userUUID)");
		
		$stmt->bind_param("sss",$rowUUID,$supervisorUUID,$userUUID);
		
		if($stmt->execute()){
			$this->log->setAction("USER_ADD_SUPERVISOR_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Supervisor UUID",$supervisorUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_ADD_SUPERVISOR_ASSOCIATION");
			$this->log->setDetail("Calling Function","associations->addSupervisorAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Supervisor UUID",$supervisorUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
				
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function deleteSupervisorAssociation($supervisorUUID, $userUUID){
		if(!$this->user->verifyUser($supervisorUUID)){
			$this->error[] = "That supervisor does not exist.";
			return false;
		}
		
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		$stmt = $this->db->prepare("DELETE FROM userSupervisorAssociations WHERE supervisorUUID = ? AND userUUID = ?");
		
		$stmt->bind_param("ss",$supervisorUUID,$userUUID);
		
		if($stmt->execute()){
			$this->log->setAction("USER_REMOVE_SUPERVISOR_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Supervisor UUID",$supervisorUUID);
			$this->log->saveEntry();
				
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_REMOVE_SUPERVISOR_ASSOCIATION");
			$this->log->setDetail("Calling Function","associations->deleteSupervisorAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Supervisor UUID",$supervisorUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}

    public function deleteUserSupervisorAssociations($userUUID){
        if(!$this->user->verifyUser($userUUID)){
            $this->error[] = "That user does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM userSupervisorAssociations WHERE userUUID = ?");

        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
            $this->log->setAction("USER_REMOVE_SUPERVISOR_ASSOCIATIONS_ALL");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->saveEntry();

            $stmt->close();
            return true;
        }
        else{
            $this->log->setAction("ERROR_USER_REMOVE_SUPERVISOR_ASSOCIATIONS_ALL");
            $this->log->setDetail("Calling Function","associations->deleteUserSupervisorAssociations()");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error[] = $stmt->error;
            $stmt->close();
            return false;
        }
    }

	public function addTrainingManagerAssociation($trainingManagerUUID, $userUUID){
		if(!$this->user->verifyUser($trainingManagerUUID)){
			$this->error[] = "That training manager does not exist.";
			return false;
		}
	
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
	
		$rowUUID = parent::genUUID();
	
		$stmt = $this->db->prepare("INSERT INTO userTrainingManagerAssociations (uuid, trainingManagerUUID, userUUID) VALUES (?,?,?)
										ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																trainingManagerUUID=VALUES(trainingManagerUUID),
																userUUID=VALUES(userUUID)");
	
		$stmt->bind_param("sss",$rowUUID,$trainingManagerUUID,$userUUID);
	
		if($stmt->execute()){
			$this->log->setAction("USER_ADD_TRAINING_MANAGER_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Training Manager UUID",$trainingManagerUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_ADD_TRAINING_MANAGER_ASSOCIATION");
			$this->log->setDetail("Calling Function","associations->addTrainingManagerAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Training Manager UUID",$trainingManagerUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
	
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function deleteTrainingManagerAssociation($trainingManagerUUID, $userUUID){
		if(!$this->user->verifyUser($trainingManagerUUID)){
			$this->error[] = "That training manager does not exist.";
			return false;
		}
		
		if(!$this->user->verifyUser($userUUID)){
			$this->error[] = "That user does not exist.";
			return false;
		}
		
		$stmt = $this->db->prepare("DELETE FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ? AND userUUID = ?");
		
		$stmt->bind_param("ss",$trainingManagerUUID,$userUUID);
		
		if($stmt->execute()){
			$this->log->setAction("USER_REMOVE_TRAINING_MANAGER_ASSOCIATION");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Training Manager UUID",$trainingManagerUUID);
			$this->log->saveEntry();
				
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_REMOVE_TRAINING_MANAGER_ASSOCIATION");
			$this->log->setDetail("Calling Function","associations->deleteTrainingManagerAssociation()");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Training Manager UUID",$trainingManagerUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}

    public function deleteUserTrainingManagerAssociations($userUUID){
        if(!$this->user->verifyUser($userUUID)){
            $this->error[] = "That user does not exist.";
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM userTrainingManagerAssociations WHERE userUUID = ?");

        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
            $this->log->setAction("USER_REMOVE_TRAINING_MANAGER_ASSOCIATIONS_ALL");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->saveEntry();

            $stmt->close();
            return true;
        }
        else{
            $this->log->setAction("ERROR_USER_REMOVE_TRAINING_MANAGER_ASSOCIATIONS_ALL");
            $this->log->setDetail("Calling Function","associations->deleteUserTrainingManagerAssociations()");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error[] = $stmt->error;
            $stmt->close();
            return false;
        }
    }
	
	public function __destruct(){
		parent::__destruct();
	}
}