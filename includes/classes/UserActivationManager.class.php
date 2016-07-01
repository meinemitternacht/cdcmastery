<?php

class UserActivationManager extends UserManager
{
	protected $db;
	protected $log;
	protected $emailQueue;
	
	public function __construct(mysqli $db, SystemLog $log, EmailQueueManager $emailQueue){
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
	
		parent::__construct($db, $log, $emailQueue);
	}
	
	public function __destruct(){
		parent::__destruct();
	}

    public function deleteUserActivationToken($userUUID){
        $stmt = $this->db->prepare("DELETE FROM queueUnactivatedUsers WHERE userUUID = ?");
        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("ERROR_DELETE_USER_ACTIVATION_TOKEN");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

	public function listUnactivatedUsers(){
		$stmt = $this->db->prepare("SELECT activationCode, userUUID, timeExpires
                                    FROM queueUnactivatedUsers
                                    ORDER BY timeExpires DESC");

		if($stmt->execute()){
			$stmt->bind_result($activationCode,$userUUID,$timeExpires);

			while($stmt->fetch()){
				$unactivatedUserArray[$activationCode]['userUUID'] = $userUUID;
				$unactivatedUserArray[$activationCode]['timeExpires'] = $timeExpires;
			}

			$stmt->close();

			if(isset($unactivatedUserArray) && !empty($unactivatedUserArray)){
				return $unactivatedUserArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_LIST_UNACTIVATED_USERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();

			return false;
		}
	}
	
	public function queueActivation($userUUID){
		if($this->verifyUser($userUUID)){
            $this->loadUser($userUUID);
			$activationCode = parent::genUUID();
			
			$dtObj = new DateTime();
			$dtObj->modify("+7 days");
			
			$timeExpires = $dtObj->format("Y-m-d H:i:s");
			
			$stmt = $this->db->prepare("INSERT INTO queueUnactivatedUsers
                                            (activationCode, userUUID, timeExpires)
                                            VALUES (?,?,?)
											ON DUPLICATE KEY UPDATE activationCode=VALUES(activationCode),
											                        userUUID=VALUES(userUUID),
											                        timeExpires=VALUES(timeExpires)");
			
			$stmt->bind_param("sss",$activationCode,$userUUID,$timeExpires);
			
			if($stmt->execute()){
				$emailSender = "support@cdcmastery.com";
				$emailRecipient = $this->getUserEmail();
				$emailSubject = "CDCMastery.com Account Activation";
				
				$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
				$emailBodyHTML .= $this->getFullName().",";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "Thank you for registering an account at CDCMastery!  ";
				$emailBodyHTML .= "Please click on the link at the bottom of this message to activate your account.  ";
				$emailBodyHTML .= "If you did not register an account or you are having issues, please contact us at support@cdcmastery.com.  ";
				$emailBodyHTML .= "If you cannot click on the link, copy and paste the address into your browser.";
				$emailBodyHTML .= "This link will be valid for 7 days, and expires on ".$timeExpires." UTC.";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "https://".$_SERVER['HTTP_HOST']."/auth/activate/".$activationCode;
				$emailBodyHTML .= "<br /><br />";
                $emailBodyHTML .= "If you encounter issues, go to https://".$_SERVER['HTTP_HOST']."/auth/activate and enter the following code:";
                $emailBodyHTML .= "<br /><br />";
                $emailBodyHTML .= $activationCode;
                $emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "Regards,";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "CDCMastery.com";
				$emailBodyHTML .= "</body></html>";
				
				$emailBodyText = $this->getFullName().",";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "Thank you for registering an account at CDCMastery!  ";
				$emailBodyText .= "Please click on the link at the bottom of this message to activate your account.  ";
				$emailBodyText .= "If you did not register an account or you are having issues, please contact us at support@cdcmastery.com.  ";
				$emailBodyText .= "If you cannot click on the link, copy and paste the address into your browser.";
				$emailBodyText .= "This link will be valid for 7 days, and expires on ".$timeExpires."UTC.";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "http://".$_SERVER['HTTP_HOST']."/auth/activate/".$activationCode;
				$emailBodyText .= "\r\n\r\n";
                $emailBodyText .= "If you encounter issues, go to https://".$_SERVER['HTTP_HOST']."/auth/activate and enter the following code:";
                $emailBodyText .= "\r\n\r\n";
                $emailBodyText .= $activationCode;
                $emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "Regards,";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "CDCMastery.com";
				
				if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, "SYSTEM")){
					if(!isset($_SESSION['userUUID']) || empty($_SESSION['userUUID']))
						$this->log->setUserUUID($userUUID);
					$this->log->setAction("USER_QUEUE_ACTIVATION");
					$this->log->setDetail("User UUID",$userUUID);
					$this->log->saveEntry();
					$stmt->close();
					return true;
				}
				else{
					$this->error = "Unable to queue activation e-mail.";
					if(!isset($_SESSION['userUUID']) || empty($_SESSION['userUUID']))
						$this->log->setUserUUID($userUUID);
					$this->log->setAction("ERROR_USER_QUEUE_ACTIVATION");
					$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
					$this->log->setDetail("System Error",$this->error);
					$this->log->setDetail("User UUID",$userUUID);
                    $this->log->setDetail("Email Queue Error",$this->emailQueue->error);
					$this->log->saveEntry();
					$stmt->close();
					return false;
				}
			}
			else{
				$this->error = $stmt->error;
				if(!isset($_SESSION['userUUID']) || empty($_SESSION['userUUID']))
					$this->log->setUserUUID($userUUID);
				$this->log->setAction("ERROR_USER_QUEUE_ACTIVATION");
				$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
				$this->log->setDetail("MySQL Error",$stmt->error);
				$this->log->setDetail("User UUID",$userUUID);
				$this->log->saveEntry();
				$stmt->close();
				return false;
			}
		}
		else{
			$this->error = "That user does not exist.";
			$this->log->setAction("ERROR_USER_QUEUE_ACTIVATION");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("System Error",$this->error);
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->saveEntry();
			return false;
		}
	}
	
	public function verifyActivationToken($activationToken){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM queueUnactivatedUsers WHERE activationCode = ? AND timeExpires > CURRENT_TIMESTAMP");
		$stmt->bind_param("s",$activationToken);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();
			
			if($count){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	public function activateUser($activationToken,$activatingUser=false){
        $targetUserUUID = $this->getActivationTokenUserUUID($activationToken);

		$stmt = $this->db->prepare("DELETE FROM queueUnactivatedUsers WHERE activationCode = ?");
		$stmt->bind_param("s",$activationToken);
		
		if($stmt->execute()){
			$stmt->close();
            if($targetUserUUID && !$activatingUser){
                $this->log->setAction("USER_ACTIVATE");
                $this->log->setUserUUID($targetUserUUID);
            }
            elseif($activatingUser){
                $this->log->setAction("ADMIN_ACTIVATE_USER");
                $this->log->setUserUUID($activatingUser);
                $this->log->setDetail("User UUID",$targetUserUUID);
            }
            $this->log->saveEntry();

			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_ACTIVATE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("Activation Token",$activationToken);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}

    public function getActivationTokenUserUUID($activationToken){
        $stmt = $this->db->prepare("SELECT userUUID FROM queueUnactivatedUsers WHERE activationCode = ?");
        $stmt->bind_param("s",$activationToken);

        if($stmt->execute()){
            $stmt->bind_result($userUUID);
            $stmt->fetch();
            $stmt->close();

            return $userUUID;
        }
        else{
            $stmt->close();
            return false;
        }
    }
}