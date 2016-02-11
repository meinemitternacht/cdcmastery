<?php

class associations extends CDCMastery
{
	protected $db;
	protected $log;
	protected $user;
	protected $afsc;
	protected $emailQueue;
	
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
	
	public function __construct(mysqli $db, log $log, user $user, afsc $afsc, emailQueue $emailQueue){
		$this->db = $db;
		$this->log = $log;
		$this->user = $user;
		$this->afsc = $afsc;
		$this->emailQueue = $emailQueue;
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
            $this->log->setAction("ERROR_ASSOCIATIONS_AFSC_LIST_PENDING");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

	public function listUserCountByAFSC($afscUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE afscUUID = ?");
		$stmt->bind_param("s",$afscUUID);

		if($stmt->execute()){
			$stmt->bind_result($userCount);
			$stmt->fetch();

			if($userCount > 0){
				return $userCount;
			}
			else{
				$this->error[] = "No associations.";
				return 0;
			}
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_ASSOCIATIONS_LIST_USERS_BY_AFSC");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL ERROR",$this->error);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			$stmt->close();

			return false;
		}
	}

	public function listUsersByAFSC($afscUUID){
		$stmt = $this->db->prepare("SELECT userUUID FROM userAFSCAssociations WHERE afscUUID = ?");
		$stmt->bind_param("s",$afscUUID);

		if($stmt->execute()){
			$stmt->bind_result($userUUID);

			while($stmt->fetch()){
				$userUUIDArray[] = $userUUID;
			}

			if(is_array($userUUIDArray) && count($userUUIDArray) > 0){
				return $userUUIDArray;
			}
			else{
				$this->error[] = "No associations.";
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_ASSOCIATIONS_LIST_USERS_BY_AFSC");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL ERROR",$this->error);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			$stmt->close();

			return false;
		}
	}
	
	public function addAFSCAssociation($userUUID, $afscUUID, $userAuthorized=true, $logSuccess=true){
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
			if($logSuccess) {
				if ($userAuthorized) {
					$this->log->setAction("USER_ADD_AFSC_ASSOCIATION");
				} else {
					$this->notifyPendingAFSCAssociation();
					$this->log->setAction("USER_ADD_PENDING_AFSC_ASSOCIATION");
				}
				$this->log->setDetail("User UUID", $userUUID);
				$this->log->setDetail("AFSC UUID", $afscUUID);
				$this->log->saveEntry();
			}

			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_ASSOCIATIONS_AFSC_ADD");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
	
	public function deleteAFSCAssociation($userUUID, $afscUUID, $logSuccess=true){
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
			if($logSuccess) {
				$this->log->setAction("USER_DELETE_AFSC_ASSOCIATION");
				$this->log->setDetail("User UUID", $userUUID);
				$this->log->setDetail("AFSC UUID", $afscUUID);
				$this->log->saveEntry();
			}

			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_ASSOCIATIONS_AFSC_DELETE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->saveEntry();

            $this->error[] = $stmt->error;
            $stmt->close();
            return false;
        }
    }

	public function migrateAFSCAssociations($sourceAFSC,$destinationAFSC,$removeOld=false){
		if(!$this->afsc->verifyAFSC($sourceAFSC) || !$this->afsc->verifyAFSC($destinationAFSC)){
			$this->error[] = "Could not verify source and destination AFSC's";

			$this->log->setAction("ERROR_MIGRATE_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("Source AFSC",$sourceAFSC);
			$this->log->setDetail("Destination AFSC",$destinationAFSC);
			$this->log->setDetail("Remove Previous Associations",$removeOld);
			$this->log->setDetail("Error","Could not verify source and destination AFSC's");
			$this->log->saveEntry();

			return false;
		}


		$userUUIDArray = $this->listUsersByAFSC($sourceAFSC);

		if(is_array($userUUIDArray) && !empty($userUUIDArray)){
			foreach($userUUIDArray as $userUUID){
				if($removeOld) {
					$this->deleteAFSCAssociation($userUUID, $sourceAFSC, false);
				}
				$this->addAFSCAssociation($userUUID,$destinationAFSC,true,false);
			}

			$this->log->setAction("MIGRATE_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Source AFSC",$sourceAFSC);
			$this->log->setDetail("Source AFSC Name",$this->afsc->getAFSCName($sourceAFSC));
			$this->log->setDetail("Destination AFSC",$destinationAFSC);
			$this->log->setDetail("Destination AFSC Name",$this->afsc->getAFSCName($destinationAFSC));
			$this->log->setDetail("Remove Previous Associations",$removeOld);
			$this->log->setDetail("Affected Users",count($userUUIDArray));
			$this->log->saveEntry();

			return true;
		}
		else{
			$this->error[] = "There are no user associations for that AFSC";

			$this->log->setAction("ERROR_MIGRATE_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("Source AFSC",$sourceAFSC);
			$this->log->setDetail("Destination AFSC",$destinationAFSC);
			$this->log->setDetail("Remove Previous Associations",$removeOld);
			$this->log->setDetail("Error","There are no user associations for that AFSC");
			$this->log->saveEntry();

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

	/**
	 * Notifies administrator of a pending AFSC association
	 * @param $userUUID
	 * @return bool
	 */
	public function notifyPendingAFSCAssociation(){
		$this->user->loadUser("7bf2aaac-fa5e-4223-9139-cb95b1ecc8ac");

		$emailSender = "support@cdcmastery.com";
		$emailRecipient = $this->user->getUserEmail();
		$emailSubject = "Pending AFSC Association";

		$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
		$emailBodyHTML .= $this->user->getFullName().",";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "There is a pending AFSC association awaiting your approval.";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "https://cdcmastery.com/admin/afsc-pending";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "Regards,";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "CDCMastery.com";
		$emailBodyHTML .= "</body></html>";

		$emailBodyText = $this->user->getFullName().",";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "There is a pending AFSC association awaiting your approval.";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "https://cdcmastery.com/admin/afsc-pending";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Regards,";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "CDCMastery.com";

		$queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

		if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
			$this->log->setAction("NOTIFY_PENDING_AFSC_ASSOCIATION");
			$this->log->setUserUUID("SYSTEM");
			$this->log->saveEntry();
			return true;
		}
		else{
			$this->log->setAction("ERROR_NOTIFY_PENDING_AFSC_ASSOCIATION");
			$this->log->setUserUUID("SYSTEM");
			$this->log->saveEntry();
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

			if($this->notifyPendingAFSCApproval($userUUID,$afscUUID)){
				return true;
			}
			else{
				$this->error = "Could not send notification e-mail.";
				return false;
			}
		}
		else{
			$this->log->setAction("ERROR_USER_APPROVE_PENDING_AFSC_ASSOCIATION");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error[] = $stmt->error;
			$stmt->close();
			return false;
		}
	}

	/**
	 * Notifies the user when their pending AFSC association was approved
	 * @param $userUUID
	 * @param $afscUUID
	 * @return bool
	 */
	public function notifyPendingAFSCApproval($userUUID,$afscUUID){
		if(!$this->afsc->verifyAFSC($afscUUID)){
			$this->error = "Not sure why, but that AFSC does not exist.";
			$this->log->setAction("ERROR_NOTIFY_AFSC_ASSOCIATION_APPROVAL");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->setDetail("Error","AFSC does not exist.");
			$this->log->saveEntry();

			return false;
		}

		if($this->user->verifyUser($userUUID)){
			$this->user->loadUser($userUUID);

			$emailSender = "support@cdcmastery.com";
			$emailRecipient = $this->user->getUserEmail();
			$emailSubject = "AFSC Association Approved";

			$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
			$emailBodyHTML .= $this->user->getFullName().",";
			$emailBodyHTML .= "<br /><br />";
			$emailBodyHTML .= "An administrator or training manager at CDCMastery has approved your pending AFSC association. ";
			$emailBodyHTML .= "<br /><br />";
			$emailBodyHTML .= "AFSC: ".$this->afsc->getAFSCName($afscUUID);
			$emailBodyHTML .= "<br /><br />";
			$emailBodyHTML .= "If you have any questions about this process, please contact the CDCMastery Help Desk: http://helpdesk.cdcmastery.com/ ";
			$emailBodyHTML .= "<br /><br />";
			$emailBodyHTML .= "Regards,";
			$emailBodyHTML .= "<br /><br />";
			$emailBodyHTML .= "CDCMastery.com";
			$emailBodyHTML .= "</body></html>";

			$emailBodyText = $this->user->getFullName().",";
			$emailBodyText .= "\r\n\r\n";
			$emailBodyText .= "An administrator or training manager at CDCMastery has approved your pending AFSC association. ";
			$emailBodyText .= "\r\n\r\n";
			$emailBodyText .= "AFSC: ".$this->afsc->getAFSCName($afscUUID);
			$emailBodyText .= "\r\n\r\n";
			$emailBodyText .= "If you have any questions about this process, please contact the CDCMastery Help Desk: http://helpdesk.cdcmastery.com/ ";
			$emailBodyText .= "\r\n\r\n";
			$emailBodyText .= "Regards,";
			$emailBodyText .= "\r\n\r\n";
			$emailBodyText .= "CDCMastery.com";

			$queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

			if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
				$this->log->setAction("NOTIFY_AFSC_ASSOCIATION_APPROVAL");
				$this->log->setUserUUID($userUUID);
				$this->log->setDetail("User UUID",$userUUID);
				$this->log->setDetail("AFSC UUID",$afscUUID);
				$this->log->saveEntry();
				return true;
			}
			else{
				$this->log->setAction("ERROR_NOTIFY_AFSC_ASSOCIATION_APPROVAL");
				$this->log->setUserUUID($userUUID);
				$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
				$this->log->setDetail("Child function","emailQueue->queueEmail()");
				$this->log->setDetail("User UUID",$userUUID);
				$this->log->setDetail("AFSC UUID",$afscUUID);
				$this->log->saveEntry();
				return false;
			}
		}
		else{
			$this->error = "That user does not exist.";
			$this->log->setAction("ERROR_NOTIFY_AFSC_ASSOCIATION_APPROVAL");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("System Error",$this->error);
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("AFSC UUID",$afscUUID);
			$this->log->saveEntry();
			return false;
		}
	}

	/**
	 * @param $supervisorUUID
	 * @param $userUUID
	 * @return bool
	 */
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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