<?php
class userActivation extends user {
	protected $db;
	protected $log;
	protected $emailQueue;
	
	public function __construct(mysqli $db, log $log, emailQueue $emailQueue){
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
	
		parent::__construct($db, $log, $emailQueue);
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	public function queueActivation($userUUID){
		if($this->verifyUser($userUUID)){
			$activationCode = parent::genUUID();
			
			$dtObj = new DateTime();
			$dtObj->modify("+7 days");
			
			$timeExpires = $dtObj->format("Y-m-d H:i:s");
			
			$stmt = $this->db->prepare("INSERT INTO queueUnactivatedUsers (activationCode, userUUID, timeExpires) VALUES (?,?,?)
											ON DUPLICATE KEY UPDATE activationCode=VALUES(activationCode),userUUID=VALUES(userUUID),timeExpires=VALUES(timeExpires)");
			
			$stmt->bind_param("sss",$activationCode,$userUUID,$timeExpires);
			
			if($stmt->execute()){
				$emailSender = "<support@cdcmastery.com>";
				$emailRecipient = $this->getUserEmail();
				$emailSubject = "CDCMastery.com Account Activation";
				
				$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
				$emailBodyHTML .= $this->getFullName().",";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "Thank you for registering an account at CDCMastery!  ";
				$emailBodyHTML .= "Please click on the link at the bottom of this message to activate your account.  ";
				$emailBodyHTML .= "If you did not register an account or you are having issues, please contact us at support@cdcmastery.com.  ";
				$emailBodyHTML .= "This link will be valid for 4 days, and expires on ".parent::outputDateTime($timeExpires,$_SESSION['timeZone']).".";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "<a href=\"http://dev.cdcmastery.com/auth/activate/".$activationCode."\">Click Here to Activate Your Account</a>";
				$emailBodyHTML .= "</body></html>";
				
				$emailBodyText = $this->getFullName().",";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "Thank you for registering an account at CDCMastery!  ";
				$emailBodyText .= "Please click on the link at the bottom of this message to activate your account.  ";
				$emailBodyText .= "If you did not register an account or you are having issues, please contact us at support@cdcmastery.com.  ";
				$emailBodyText .= "This link will be valid for 4 days, and expires on ".parent::outputDateTime($timeExpires,$_SESSION['timeZone']).".";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "http://dev.cdcmastery.com/auth/activate/".$activationCode;
				
				if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, "SYSTEM")){
					$this->log->setAction("USER_QUEUE_ACTIVATION");
					$this->log->setDetail("User UUID",$userUUID);
					$this->log->saveEntry();
					$stmt->close();
					return true;
				}
				else{
					$this->error = "Unable to send activation e-mail.";
					$this->log->setAction("ERROR_USER_QUEUE_ACTIVATION");
					$this->log->setDetail("Calling Function","user\userActivation->queueActivation()");
					$this->log->setDetail("System Error",$this->error);
					$this->log->setDetail("User UUID",$userUUID);
					$this->log->saveEntry();
					$stmt->close();
					return false;
				}
			}
			else{
				$this->error = $stmt->error;
				$this->log->setAction("ERROR_USER_QUEUE_ACTIVATION");
				$this->log->setDetail("Calling Function","user\userActivation->queueActivation()");
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
			$this->log->setDetail("Calling Function","user\userActivation->queueActivation()");
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
	
	public function activateUser($activationToken){
		$stmt = $this->db->prepare("DELETE FROM queueUnactivatedUsers WHERE activationCode = ?");
		$stmt->bind_param("s",$activationToken);
		
		if($stmt->execute()){
			$stmt->close();
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_ACTIVATE");
			$this->log->setDetail("Calling Function","user->activateUser()");
			$this->log->setDetail("Activation Token",$activationToken);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
		
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}
}