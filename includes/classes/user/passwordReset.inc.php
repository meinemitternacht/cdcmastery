<?php
class passwordReset extends user {
	protected $db;
	protected $log;
	protected $emailQueue;
	
	public function __construct($db, $log, $emailQueue){
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
		
		parent::__construct($db, $log, $emailQueue);
	}
	
	public function __destruct(){
		parent::__destruct();
	}
	
	public function deletePasswordResetToken($passwordToken){
		$stmt = $this->db->prepare("DELETE FROM userPasswordResets WHERE uuid = ?");
		$stmt->bind_param("s",$passwordToken);
	
		if($stmt->execute()){
			return true;
		}
		else{
			$this->log->setAction("ERROR_USER_DELETE_PASSWORD_RESET_TOKEN");
			$this->log->setDetail("Calling Function","user->deletePasswordResetToken()");
			$this->log->setDetail("Password Token",$passwordToken);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
	
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function getPasswordResetUser($passwordToken){
		$stmt = $this->db->prepare("SELECT userUUID FROM userPasswordResets WHERE uuid = ?");
		$stmt->bind_param("s",$passwordToken);
	
		if($stmt->execute()){
			$stmt->bind_result($userUUID);
			$stmt->fetch();
	
			if($userUUID){
				return $userUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->log->setAction("ERROR_USER_GET_PASSWORD_RESET_USER");
			$this->log->setDetail("Calling Function","user->getPasswordResetUser()");
			$this->log->setDetail("Password Token",$passwordToken);
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
				
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function sendPasswordReset($userUUID){
		if($this->verifyUser($userUUID)){
			$this->loadUser($userUUID);
				
			$dtObj = new DateTime();
			$dtObj->modify("+1 day");
				
			$timeExpires = $dtObj->format("Y-m-d H:i:s");
			$uuid = parent::genUUID();
				
			$stmt = $this->db->prepare("INSERT INTO userPasswordResets (uuid, userUUID, timeExpires) VALUES (?,?,?)
											ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
																	userUUID=VALUES(userUUID),
																	timeExpires=VALUES(timeExpires)");
				
			$stmt->bind_param("sss",$uuid,$userUUID,$timeExpires);
				
			if($stmt->execute()){
				$emailSender = "support@cdcmastery.com";
				$emailRecipient = $this->getUserEmail();
				$emailSubject = "CDCMastery.com Password Reset Request";
	
				$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
				$emailBodyHTML .= $this->getFullName().",";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "You have requested a password reset from CDC Mastery.  ";
				$emailBodyHTML .= "If you did not request a password reset, ignore this e-mail and contact us (support@cdcmastery.com).  ";
				$emailBodyHTML .= "Click on the link below to access your account and change your password.  ";
				$emailBodyHTML .= "This link will be valid for 24 hours, and expires on ".parent::outputDateTime($timeExpires,$_SESSION['timeZone']).".";
				$emailBodyHTML .= "<br /><br />";
				$emailBodyHTML .= "<a href=\"http://dev.cdcmastery.com/auth/reset/".$uuid."\">Reset your password</a>";
				$emailBodyHTML .= "</body></html>";
	
				$emailBodyText = $this->getFullName().",";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "You have requested a password reset from CDC Mastery.  ";
				$emailBodyText .= "If you did not request a password reset, ignore this e-mail and contact us (support@cdcmastery.com).  ";
				$emailBodyText .= "Click on the link below to access your account and change your password.  ";
				$emailBodyText .= "This link will be valid for 24 hours, and expires on ".parent::outputDateTime($timeExpires,$_SESSION['timeZone']).".";
				$emailBodyText .= "\r\n\r\n";
				$emailBodyText .= "http://dev.cdcmastery.com/auth/reset/".$uuid;
	
				if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $_SESSION['userUUID'])){
					$this->log->setAction("USER_PASSWORD_RESET");
					$this->log->setDetail("User UUID",$userUUID);
					$this->log->saveEntry();
					return true;
				}
				else{
					$this->log->setAction("ERROR_USER_PASSWORD_RESET");
					$this->log->setDetail("Calling Function","user->sendPasswordReset()");
					$this->log->setDetail("Child function","emailQueue->queueEmail()");
					$this->log->setDetail("User UUID",$userUUID);
					$this->log->saveEntry();
					return false;
				}
			}
			else{
				$this->log->setAction("ERROR_USER_PASSWORD_RESET");
				$this->log->setDetail("Calling Function","user->sendPasswordReset()");
				$this->log->setDetail("MySQL Error",$stmt->error);
				$this->log->setDetail("User UUID",$userUUID);
				$this->log->saveEntry();
					
				$this->error = $stmt->error;
				$stmt->close();
				return false;
			}
		}
		else{
			$this->error = "That user does not exist.";
			$this->log->setAction("ERROR_USER_PASSWORD_RESET");
			$this->log->setDetail("Calling Function","user->sendPasswordReset()");
			$this->log->setDetail("System Error",$this->error);
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->saveEntry();
			return false;
		}
	}
	
	public function verifyPasswordResetToken($passwordToken){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userPasswordResets WHERE uuid = ? AND timeExpires > CURRENT_TIMESTAMP");
		$stmt->bind_param("s",$passwordToken);
	
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
				
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
}