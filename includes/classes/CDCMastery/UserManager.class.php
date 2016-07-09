<?php

namespace CDCMastery;
use mysqli;

class UserManager extends CDCMastery {
	protected $db;
	protected $log;
	protected $emailQueue;

	public $error;

	public $uuid;				//binary  36
	public $userFirstName;		//varchar 64
	public $userLastName;		//varchar 64
	public $userHandle;			//varchar 64
	public $userPassword;	    //varchar 255 (SHA512)
	public $userLegacyPassword;	//varchar 255 (SHA1)
	public $userEmail;			//varchar 255
	public $userRank;			//varchar 255
	public $userDateRegistered;	//datetime
	public $userLastLogin;		//datetime
	public $userLastActive;		//datetime
	public $userTimeZone;		//varchar 255
	public $userRole;			//binary  36
	public $userOfficeSymbol;	//binary  36
	public $userBase;			//binary  36
	public $userDisabled;		//bool
	public $reminderSent;		//bool

	public function __construct(mysqli $db, SystemLog $log, EmailQueueManager $emailQueue) {
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
		$this->uuid = parent::genUUID();
	}

	public function __destruct() {
		//nothing :)
	}

    public function updateLastActiveTimestamp(){
        if($this->uuid) {
            $stmt = $this->db->prepare("UPDATE userData SET userLastActive = UTC_TIMESTAMP() WHERE uuid = ?");
            $stmt->bind_param("s", $this->uuid);

            if(!$stmt->execute()){
                $this->log->setAction("ERROR_UPDATE_LAST_ACTIVE");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->setDetail("User UUID",$this->uuid);
                $this->log->saveEntry();

                return false;
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }

	public function listUsers(){
		$res = $this->db->query("SELECT uuid, userHandle, userFirstName, userLastName, userRank FROM userData ORDER BY userLastName, userFirstName ASC");

		$userArray = Array();

		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$userArray[$row['uuid']]['userHandle'] = $row['userHandle'];
				$userArray[$row['uuid']]['userFirstName'] = $row['userFirstName'];
				$userArray[$row['uuid']]['userLastName'] = $row['userLastName'];
				$userArray[$row['uuid']]['userRank'] = $row['userRank'];
			}

			$noResults = false;
		}
		else{
			$noResults = true;
		}

		$res->close();

		if($noResults){
			return false;
		}
		else{
			return $userArray;
		}
	}

    public function listUsersByRole($roleUUID){
        $stmt = $this->db->prepare("SELECT  uuid,
                                            userHandle,
                                            userFirstName,
                                            userLastName,
                                            userRank
                                    FROM userData
                                    WHERE userRole = ?
                                    ORDER BY userLastName ASC");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid,$userHandle,$userFirstName,$userLastName,$userRank);

            while($stmt->fetch()){
                $userArray[$uuid]['userHandle'] = $userHandle;
                $userArray[$uuid]['userFirstName'] = $userFirstName;
                $userArray[$uuid]['userLastName'] = $userLastName;
                $userArray[$uuid]['userRank'] = $userRank;
            }

			$stmt->close();

			if(isset($userArray) && !empty($userArray)){
				return $userArray;
			}
			else{
				return false;
			}
        }
        else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_LIST_USERS_BY_ROLE");
			$this->log->setDetail("Role UUID",$roleUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

            return false;
        }
    }

	public function listUsersByBase($baseUUID){
		$stmt = $this->db->prepare("SELECT  uuid,
											userHandle,
											userFirstName,
											userLastName,
											userRank
                                    FROM userData
                                    WHERE userBase = ?
                                    ORDER BY userLastName ASC");
		$stmt->bind_param("s",$baseUUID);

		if($stmt->execute()){
			$stmt->bind_result($uuid,$userHandle,$userFirstName,$userLastName,$userRank);

			while($stmt->fetch()){
				$userArray[$uuid]['userHandle'] = $userHandle;
				$userArray[$uuid]['userFirstName'] = $userFirstName;
				$userArray[$uuid]['userLastName'] = $userLastName;
				$userArray[$uuid]['userRank'] = $userRank;
			}

			$stmt->close();

			if(isset($userArray) && !empty($userArray)){
				return $userArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_LIST_USERS_BY_BASE");
			$this->log->setDetail("Base UUID",$baseUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	public function listUserUUIDByBase($baseUUID){
		$stmt = $this->db->prepare("SELECT  uuid
                                    FROM userData
                                    WHERE userBase = ?");
		$stmt->bind_param("s",$baseUUID);

		if($stmt->execute()){
			$stmt->bind_result($uuid);

			while($stmt->fetch()){
				$userArray[] = $uuid;
			}

			$stmt->close();

			if(isset($userArray) && !empty($userArray)){
				return $userArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_LIST_USER_UUID_BY_BASE");
			$this->log->setDetail("Base UUID",$baseUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	public function loadUser($userUUID){
		$stmt = $this->db->prepare("SELECT  uuid,
											userFirstName,
											userLastName,
											userHandle,
											userPassword,
											userLegacyPassword,
											userEmail,
											userRank,
											userDateRegistered,
											userLastLogin,
											userLastActive,
											userTimeZone,
											userRole,
											userOfficeSymbol,
											userBase,
											userDisabled,
											reminderSent
									FROM userData
									WHERE uuid = ?");
		$stmt->bind_param("s",$userUUID);
		
		if($stmt->execute()) {
			$stmt->bind_result($uuid, $userFirstName, $userLastName, $userHandle, $userPassword, $userLegacyPassword, $userEmail, $userRank, $userDateRegistered, $userLastLogin, $userLastActive, $userTimeZone, $userRole, $userOfficeSymbol, $userBase, $userDisabled, $reminderSent);
			$stmt->fetch();
			$stmt->close();

			$this->uuid = $uuid;
			$this->userFirstName = $userFirstName;
			$this->userLastName = $userLastName;
			$this->userHandle = $userHandle;
			$this->userPassword = $userPassword;
			$this->userLegacyPassword = $userLegacyPassword;
			$this->userEmail = $userEmail;
			$this->userRank = $userRank;
			$this->userDateRegistered = $userDateRegistered;
			$this->userLastLogin = $userLastLogin;
			$this->userLastActive = $userLastActive;
			$this->userTimeZone = $userTimeZone;
			$this->userRole = $userRole;
			$this->userOfficeSymbol = $userOfficeSymbol;
			$this->userBase = $userBase;
			$this->userDisabled = $userDisabled;
			$this->reminderSent = $reminderSent;

			if (empty($this->userHandle)) {
				$this->error = "That user does not exist";
				return false;
			}
			else{
				return true;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USER_LOAD");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
	}

	public function saveUser(){
		$stmt = $this->db->prepare("INSERT INTO userData  ( uuid,
															userFirstName,
															userLastName,
															userHandle,
															userPassword,
															userLegacyPassword,
															userEmail,
															userRank,
															userDateRegistered,
															userLastLogin,
															userTimeZone,
															userRole,
															userOfficeSymbol,
															userBase,
															userDisabled,
															reminderSent)
									VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
									ON DUPLICATE KEY UPDATE
															uuid=VALUES(uuid),
															userFirstName=VALUES(userFirstName),
															userLastName=VALUES(userLastName),
															userHandle=VALUES(userHandle),
															userPassword=VALUES(userPassword),
															userLegacyPassword=VALUES(userLegacyPassword),
															userEmail=VALUES(userEmail),
															userRank=VALUES(userRank),
															userDateRegistered=VALUES(userDateRegistered),
															userLastLogin=VALUES(userLastLogin),
															userTimeZone=VALUES(userTimeZone),
															userRole=VALUES(userRole),
															userOfficeSymbol=VALUES(userOfficeSymbol),
															userBase=VALUES(userBase),
															userDisabled=VALUES(userDisabled),
															reminderSent=VALUES(reminderSent)");

		$stmt->bind_param("ssssssssssssssss", 	$this->uuid,
												$this->userFirstName,
												$this->userLastName,
												$this->userHandle,
												$this->userPassword,
												$this->userLegacyPassword,
												$this->userEmail,
												$this->userRank,
												$this->userDateRegistered,
												$this->userLastLogin,
												$this->userTimeZone,
												$this->userRole,
												$this->userOfficeSymbol,
												$this->userBase,
												$this->userDisabled,
                                                $this->reminderSent);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_USER_SAVE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}

    public function deleteUser($userUUID,$disableLog = false){
        if(!$this->verifyUser($userUUID)){
            $this->error[] = "That user does not exist.";
            return false;
        }

        $_user = new UserManager($this->db, $this->log, $this->emailQueue);
        $_user->loadUser($userUUID);
        $userFullName = $_user->getFullName();

        unset($_user);

        $stmt = $this->db->prepare("DELETE FROM userData WHERE uuid = ?");
        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
			if(!$disableLog){
				$this->log->setAction("USER_DELETE");
				$this->log->setDetail("User UUID",$userUUID);
				$this->log->setDetail("User Name",$userFullName);
				$this->log->saveEntry();
			}

            return true;
        }
        else{
            $this->log->setAction("ERROR_USER_DELETE");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("User Name",$userFullName);
            $this->log->setDetail("Error",$stmt->error);
            $this->log->saveEntry();

            $this->error = $stmt->error;

            return false;
        }
    }

	/***********/
	/* Getters */
	/***********/

	public function getUUID(){
		return $this->uuid;
	}

	public function getUserFirstName(){
		return $this->userFirstName;
	}
	
	public function getUserLastName(){
		return $this->userLastName;
	}

	public function getUserHandle(){
		return $this->userHandle;
	}

	public function getUserPassword(){
		return $this->userPassword;
	}
	
	public function getUserLegacyPassword(){
		return $this->userLegacyPassword;
	}

	public function getUserEmail(){
		return $this->userEmail;
	}
	
	public function getUserRank(){
		return $this->userRank;
	}

	public function getUserDateRegistered(){
		return $this->userDateRegistered;
	}

	public function getUserLastLogin(){
		if($this->isTimeEmpty($this->userLastLogin)){
			return "Never";
		}
		else{
			return $this->userLastLogin;
		}
	}

	public function getUserLastActive(){
		if($this->isTimeEmpty($this->userLastActive)){
			return "N/A";
		}
		elseif(empty($this->userLastActive)){
			return "N/A";
		}
		else{
			return $this->userLastActive;
		}
	}
	
	public function getUserTimeZone(){
		return $this->userTimeZone;
	}
	
	public function getUserRole(){
		return $this->userRole;
	}
	
	public function getUserOfficeSymbol(){
		return $this->userOfficeSymbol;
	}
	
	public function getUserBase(){
		return $this->userBase;
	}
	
	public function getUserDisabled(){
		return $this->userDisabled;
	}

    public function getReminderSent(){
        return $this->reminderSent;
    }

	/***********/
	/* Setters */
	/***********/
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setUserFirstName($userFirstName){
		$this->userFirstName = $userFirstName;
		return true;
	}
	
	public function setUserLastName($userLastName){
		$this->userLastName = $userLastName;
		return true;
	}

	public function setUserHandle($userHandle){
		$this->userHandle = $userHandle;
		return true;
	}

	public function setUserPassword($userPassword){
		$this->userPassword = $this->hashUserPassword($userPassword);
		return true;
	}
	
	public function setUserLegacyPassword($userLegacyPassword){
		$this->userLegacyPassword = $this->hashUserLegacyPassword($userLegacyPassword);
	}

	public function setUserEmail($userEmail){
		$this->userEmail = $userEmail;
		return true;
	}
	
	public function setUserRank($userRank){
		$this->userRank = $userRank;
		return true;
	}

	public function setUserDateRegistered($userDateRegistered){
		$this->userDateRegistered = $userDateRegistered;
		return true;
	}

	public function setUserLastLogin($userLastLogin){
		$this->userLastLogin = $userLastLogin;
		return true;
	}
	
	public function setUserTimeZone($userTimeZone){
		$this->userTimeZone = $userTimeZone;
		return true;
	}
	
	public function setUserRole($userRole){
		$this->userRole = $userRole;
		return true;
	}
	
	public function setUserOfficeSymbol($userOfficeSymbol){
		$this->userOfficeSymbol = $userOfficeSymbol;
		return true;
	}
	
	public function setUserBase($userBase){
		$this->userBase = $userBase;
		return true;
	}
	
	public function setUserDisabled($userDisabled){
		$this->userDisabled = $userDisabled;
		return true;
	}

    public function setReminderSent($reminderSent){
        $this->reminderSent = $reminderSent;
        return true;
    }

	/***********/
	/*   misc  */
	/***********/

	public function updateLastLogin($user, $time="NOW"){
		if($time == "NOW"){
			$timeVar = date("Y-m-d H:i:s",time());
		}
		else{
			$timeVar = $time;
		}

		$stmt = $this->db->prepare("UPDATE userData SET userLastLogin = ? WHERE uuid = ?");
		$stmt->bind_param("ss",$timeVar,$user);
		if(!$stmt->execute()){
			$this->log->setAction("ERROR_USER_UPDATE_LAST_LOGIN");
			$this->log->setDetail("TARGET_USER", $user);
			$this->log->setDetail("TARGET_TIME", $timeVar);
			$this->log->setDetail("ERROR", $stmt->error);
			$this->log->saveEntry();

			return false;
		}
		else{
			return true;
		}
	}

    public function getUserRoleByUUID($userUUID){
        if($this->verifyUser($userUUID)) {
            $_user = new UserManager($this->db, $this->log, $this->emailQueue);

            if ($_user->loadUser($userUUID)) {
                return $_user->getUserRole();
            }
			else {
                return false;
            }
        }
		else {
			return false;
		}
    }
	
	public function getUserNameByUUID($userUUID){
		if($userUUID == "ANONYMOUS"){
			return "ANONYMOUS";
		}
		elseif($userUUID == "SYSTEM"){
			return "SYSTEM";
		}
		else{
			$_user = new UserManager($this->db, $this->log, $this->emailQueue);
			
			if($_user->loadUser($userUUID)){
				return $_user->getFullName();
			}
			else{
				return false;
			}
		}
	}

	public function getFullName($userRank="",$userFirstName="",$userLastName=""){
		if(!empty($userRank) && !empty($userFirstName) && !empty($userLastName)){
			$fullName = $userRank . ' ' . $userFirstName . ' ' . $userLastName;
		}
		else{
			$fullName = $this->getUserRank() . ' ' . $this->getUserFirstName() . ' ' . $this->getUserLastName();
		}

		return $fullName;
	}

	public function getUUIDByEmail($userEmail){
		$stmt = $this->db->prepare("SELECT uuid FROM userData WHERE userEmail = ?");
		$stmt->bind_param("s",$userEmail);

		if($stmt->execute()){
			$stmt->bind_result($uuid);
			$stmt->fetch();
			$stmt->close();

			if($uuid){
				return $uuid;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GET_UUID_BY_EMAIL");
			$this->log->setDetail("User Email",$userEmail);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	public function getLoginUUID($providedUsername){
		$stmt = $this->db->prepare("SELECT `userData`.`uuid` FROM `userData` WHERE `userData`.`userHandle` = ? OR `userData`.`userEmail` = ?");
		$stmt->bind_param("ss",$providedUsername,$providedUsername);

		if($stmt->execute()){
			$stmt->bind_result($userUUID);
			$stmt->fetch();
			$stmt->close();

			if(isset($userUUID) && !empty($userUUID)){
				return $userUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GET_LOGIN_UUID");
			$this->log->setDetail("Provided Username",$providedUsername);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	public function getUUIDByHandle($userHandle){
		$stmt = $this->db->prepare("SELECT uuid FROM userData WHERE userHandle = ?");
		$stmt->bind_param("s",$userHandle);

		if($stmt->execute()){
			$stmt->bind_result($uuid);
			$stmt->fetch();
			$stmt->close();

			if($uuid){
				return $uuid;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GET_UUID_BY_HANDLE");
			$this->log->setDetail("User Handle",$userHandle);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	public function getUserByUUID($uuid){
        if(preg_match("/ANONYMOUS/",$uuid)){
            return "ANONYMOUS";
        }
        elseif(preg_match("/SYSTEM/",$uuid)){
            return "SYSTEM";
        }

		$stmt = $this->db->prepare("SELECT userHandle FROM userData WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);

		if($stmt->execute()){
			$stmt->bind_result($userHandle);
			$stmt->fetch();
			$stmt->close();

			if($userHandle){
				return $userHandle;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_GET_USER_BY_UUID");
			$this->log->setDetail("User UUID",$uuid);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	public function userLoginName($input){
		return $this->getLoginUUID($input);
	}

	public function reportQuestion($userEmail,$afscUUID,$questionUUID,$questionText,$userComments){
		$afscManager = new AFSCManager($this->db, $this->log);
		$afscManager->loadAFSC($afscUUID);
		$afscName = $afscManager->getAFSCName();

		$emailSender = $userEmail;
		$emailRecipient = "support@cdcmastery.com";
		$emailSubject = "Question Flagged for Review";

		$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
		$emailBodyHTML .= "CDCMastery Support,";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= $this->getFullName() . " has flagged a question as containing an error.  Please review the information below and correct the issue as soon as possible.";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "<b>AFSC UUID:</b> ".$afscUUID;
		$emailBodyHTML .= "<br />";
		$emailBodyHTML .= "<b>AFSC Name:</b> ".$afscName;
		$emailBodyHTML .= "<br />";
		$emailBodyHTML .= "<b>Question UUID:</b> ".$questionUUID;
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "<b>Question Text:</b> ".$questionText;
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "<b>User Comments:</b> ".nl2br($userComments);
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "<b>Link to question:</b> <a href=\"https://cdcmastery.com/admin/cdc-data/".$afscUUID."/question/".$questionUUID."/view\">Click Here</a>";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "<b>Link to user:</b> <a href=\"https://cdcmastery.com/admin/users/".$this->getUUID()."\">Click Here</a>";
		$emailBodyHTML .= "<br /><br /><br />";
		$emailBodyHTML .= "Regards,";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "CDCMastery.com";
		$emailBodyHTML .= "</body></html>";

		$emailBodyText = "CDCMastery Support,";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= $this->getFullName() . " has flagged a question as containing an error.  Please review the information below and correct the issue as soon as possible.";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "AFSC UUID: ".$afscUUID;
		$emailBodyText .= "\r\n";
		$emailBodyText .= "AFSC Name: ".$afscName;
		$emailBodyText .= "\r\n";
		$emailBodyText .= "Question UUID: ".$questionUUID;
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Question Text: ".$questionText;
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "User Comments: ".$userComments;
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Link to question: https://cdcmastery.com/admin/cdc-data/".$afscUUID."/question/".$questionUUID."/view";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Link to user: https://cdcmastery.com/admin/users/".$this->getUUID();
		$emailBodyText .= "\r\n\r\n\r\n";
		$emailBodyText .= "Regards,";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "CDCMastery.com";

		$queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

		if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
			$this->log->setAction("NOTIFY_REPORTED_QUESTION");
			$this->log->setUserUUID("SYSTEM");
			$this->log->saveEntry();
			return true;
		}
		else{
			$this->log->setAction("ERROR_NOTIFY_REPORTED_QUESTION");
			$this->log->setUserUUID("SYSTEM");
			$this->log->saveEntry();
			return false;
		}
	}
	
	public function resolveUserNames($uuidArray){
		if(!is_array($uuidArray)){
			return false;
		}
		else{
			$stmt = $this->db->prepare("SELECT userRank, userFirstName, userLastName FROM userData WHERE uuid = ?");
			foreach($uuidArray as $userUUID){
				$stmt->bind_param("s",$userUUID);
				$stmt->bind_result($userRank, $userFirstName, $userLastName);
				
				if($stmt->execute()){
					while($stmt->fetch()){
						$resultArray[$userUUID] = $userRank . ' ' . $userFirstName . ' ' . $userLastName;
					}
				}
				else{
					$this->error = $stmt->error;
					$stmt->close();

					$this->log->setAction("ERROR_USER_RESOLVE_NAMES");
					$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
					$this->log->setDetail("userUUID", $userUUID);
					$this->log->setDetail("MYSQL ERROR", $this->error);
					$this->log->saveEntry();
					
					break;
				}
			}

			$stmt->close();

			if(empty($resultArray)){
				return false;
			}
			else{
				natcasesort($resultArray);
				return $resultArray;
			}
		}
	}
	
	public function sortUserList(array $userArray, $sortColumn, $sortDirection="ASC"){
		/*
		 * Rather than sorting the array directly, we will just implode the list (after verifying UUID's) and return
		 * a completely new array.  Makes life easier, and let's the database do the work!
		 */
		
		if(is_array($userArray) && !empty($userArray)){
			foreach($userArray as $userUUID){
				if($this->verifyUser($userUUID)){
					$implodeArray[] = $userUUID;
				}
			}

			if(isset($implodeArray) && is_array($implodeArray)) {
				$userList = implode("','", $implodeArray);

				$res = $this->db->query("SELECT uuid,
											userFirstName,
											userLastName,
											userHandle,
											userEmail,
											userRank,
											userRole,
											userOfficeSymbol,
											userBase
										FROM userData
										WHERE uuid IN ('" . $userList . "')
										ORDER BY " . $sortColumn . " " . $sortDirection);

				if ($res->num_rows > 0) {
					while ($row = $res->fetch_assoc()) {
						$returnArray[$row['uuid']]['userFirstName'] = $row['userFirstName'];
						$returnArray[$row['uuid']]['userLastName'] = $row['userLastName'];
						$returnArray[$row['uuid']]['userHandle'] = $row['userHandle'];
						$returnArray[$row['uuid']]['userEmail'] = $row['userEmail'];
						$returnArray[$row['uuid']]['userRank'] = $row['userRank'];
						$returnArray[$row['uuid']]['userRole'] = $row['userRole'];
						$returnArray[$row['uuid']]['userOfficeSymbol'] = $row['userOfficeSymbol'];
						$returnArray[$row['uuid']]['userBase'] = $row['userBase'];
						$returnArray[$row['uuid']]['fullName'] = $this->getFullName($row['userRank'], $row['userFirstName'], $row['userLastName']);
					}

					if (isset($returnArray) && !empty($returnArray)) {
						return $returnArray;
					}
					else {
						$this->error = "returnArray was empty.";

						return false;
					}
				}
				else {
					$this->error = $this->db->error;

					return false;
				}
			}
			else{
				$this->error = "No valid users.";
				return false;
			}
		}
		else{
			$this->error = "User list must be an array of values.";
			return false;
		}
	}

    public function sortUserUUIDList(array $userArray, $sortColumn, $sortDirection="ASC"){
        /*
         * Rather than sorting the array directly, we will just implode the list (after verifying UUID's) and return
         * a completely new array.  Makes life easier, and let's the database do the work!
         */

        if(is_array($userArray) && !empty($userArray)){
			$implodeArray = [];
			
            foreach($userArray as $userUUID){
                if($this->verifyUser($userUUID)){
                    $implodeArray[] = $userUUID;
                }
            }

			if(is_array($implodeArray) && !empty($implodeArray)) {
				$userList = implode("','", $implodeArray);

				$res = $this->db->query("SELECT uuid
										FROM userData
										WHERE uuid IN ('" . $userList . "')
										ORDER BY " . $sortColumn . " " . $sortDirection);

				if ($res->num_rows > 0) {
					while ($row = $res->fetch_assoc()) {
						$returnArray[] = $row['uuid'];
					}

					if (isset($returnArray) && !empty($returnArray)) {
						return $returnArray;
					}
					else {
						$this->error = "returnArray was empty.";

						return false;
					}
				}
				else {
					$this->error = $this->db->error;

					return false;
				}
			}
			else{
				return false;
			}
        }
        else{
            $this->error = "User list must be an array of values.";
            return false;
        }
    }

	public function filterUserUUIDList($userArray,$filterColumn,$filterOperand,$filterValue){
		if(is_array($userArray) && !empty($userArray)){
			$implodeArray = Array();
			foreach($userArray as $userUUID){
				if($this->verifyUser($userUUID)){
					$implodeArray[] = $userUUID;
				}
			}

			$userList = implode("','",$implodeArray);
			$query = "SELECT uuid
										FROM userData
										WHERE uuid IN ('".$userList."')
											AND
												`".$filterColumn."` ".$filterOperand." '".$filterValue."'
										ORDER BY userLastName DESC";
			$res = $this->db->query($query);

			if($res->num_rows > 0){
				while($row = $res->fetch_assoc()){
					$returnArray[] = $row['uuid'];
				}

				if(isset($returnArray) && !empty($returnArray)){
					return $returnArray;
				}
				else{
					$this->error = "returnArray was empty.";
					return false;
				}
			}
			else{
				$this->error = $this->db->error;
				return false;
			}
		}
		else{
			$this->error = "User list must be an array of values.";
			return false;
		}
	}

	public function verifyUser($userUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE uuid = ?");
		$stmt->bind_param("s",$userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();
			
			if($count > 0){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USER_VERIFY");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}
}