<?php

/*
This script provides a class interface for the site users
*/

class user extends CDCMastery {
	protected $db;
	protected $log;
	protected $emailQueue;

	public $error;

	public $uuid;				//varchar 40
	public $userFirstName;		//varchar 64
	public $userLastName;		//varchar 64
	public $userHandle;			//varchar 64
	protected $userPassword;	//varchar 255 (SHA512)
	protected $userLegacyPassword;	//varchar 255 (SHA1)
	public $userEmail;			//varchar 255
	public $userRank;			//varchar 255
	public $userDateRegistered;	//datetime
	public $userLastLogin;		//datetime
	public $userTimeZone;		//varchar 255
	public $userRole;			//varchar 40
	public $userOfficeSymbol;	//varchar 40
	public $userBase;			//varchar 40
	public $userSupervisor;		//varchar 40
	public $userDisabled;		//bool

	public function __construct(mysqli $db, log $log, emailQueue $emailQueue) {
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
		$this->uuid = parent::genUUID();
	}

	public function __destruct() {
		//nothing :)
	}

	public function listUsers(){
		$res = $this->db->query("SELECT uuid, userHandle, userFirstName, userLastName, userRank FROM userData ORDER BY userLastName ASC");

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
        }
        else{
            return false;
        }

        if(isset($userArray) && !empty($userArray)){
            return $userArray;
        }
        else{
            return false;
        }
    }

	public function loadUser($uuid){
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
											userTimeZone,
											userRole,
											userOfficeSymbol,
											userBase,
											userSupervisor,
											userDisabled
									FROM userData
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		$stmt->execute();
		$stmt->bind_result( $uuid,
							$userFirstName,
							$userLastName,
							$userHandle,
							$userPassword,
							$userLegacyPassword,
							$userEmail,
							$userRank,
							$userDateRegistered,
							$userLastLogin,
							$userTimeZone,
							$userRole,
							$userOfficeSymbol,
							$userBase,
							$userSupervisor,
							$userDisabled );

		while($stmt->fetch()){
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
			$this->userTimeZone = $userTimeZone;
			$this->userRole = $userRole;
			$this->userOfficeSymbol = $userOfficeSymbol;
			$this->userBase = $userBase;
			$this->userSupervisor = $userSupervisor;
			$this->userDisabled = $userDisabled;
		}

		$stmt->close();

		if(empty($this->userHandle)){
			$this->error = "That user does not exist";
			return false;
		}
		
		return true;
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
															userSupervisor,
															userDisabled )
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
															userSupervisor=VALUES(userSupervisor),
															userDisabled=VALUES(userDisabled)");

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
												$this->userSupervisor,
												$this->userDisabled);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_USER_SAVE");
			$this->log->setDetail("CALLING FUNCTION", "user->saveUser()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}

    public function deleteUser($userUUID){
        if(!$this->verifyUser($userUUID)){
            $this->error[] = "That user does not exist.";
            return false;
        }

        $_user = new user($this->db,$this->log,$this->emailQueue);
        $_user->loadUser($userUUID);
        $userFullName = $_user->getFullName();

        unset($_user);

        $stmt = $this->db->prepare("DELETE FROM userData WHERE uuid = ?");
        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()){
            $this->log->setAction("USER_DELETE");
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("User Name",$userFullName);
            $this->log->saveEntry();

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
		return $this->userLastLogin;
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
	
	public function getUserSupervisor(){
		return $this->userSupervisor;
	}
	
	public function getUserDisabled(){
		return $this->userDisabled;
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
	
	public function setUserSupervisor($userSupervisor){
		$this->userSupervisor = $userSupervisor;
		return true;
	}
	
	public function setUserDisabled($userDisabled){
		$this->userDisabled = $userDisabled;
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
            $_user = new user($this->db, $this->log, $this->emailQueue);

            if ($_user->loadUser($userUUID)) {
                return $_user->getUserRole();
            } else {
                return false;
            }
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
			$_user = new user($this->db, $this->log, $this->emailQueue);
			
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
		$stmt->execute();
		$stmt->bind_result($uuid);

		while($stmt->fetch()){
			$ret = $uuid;
		}

		if(empty($ret)){
			$ret = false;
		}

		$stmt->close();
		return $ret;
	}

	public function getUUIDByHandle($userHandle){
		$stmt = $this->db->prepare("SELECT uuid FROM userData WHERE userHandle = ?");
		$stmt->bind_param("s",$userHandle);
		$stmt->execute();
		$stmt->bind_result($uuid);

		while($stmt->fetch()){
			$ret = $uuid;
		}

		if(empty($ret)){
			$ret = false;
		}

		$stmt->close();
		return $ret;
	}

	public function getUserByUUID($uuid){
        if($uuid == "ANONYMOUS"){
            return "ANONYMOUS";
        }
        elseif($uuid == "SYSTEM"){
            return "SYSTEM";
        }

		$_user = new user($this->db, $this->log, $this->emailQueue);
		if(!$_user->loadUser($uuid)){
			return false;
		}
		else{
			return $_user->getUserHandle();
		}
	}

	public function userLoginName($input){
		if(strpos($input,"@") !== false){
			$ret = $this->getUUIDByEmail($input); //get uuid by email
			if(!$ret){
				$this->error = "That user does not exist";
				return false;
			}
			else{
				return $ret;
			}
		}
		else{
			$ret = $this->getUUIDByHandle($input); //get uuid by username

			if(!$ret){
				$this->error = "That user does not exist";
				return false;
			}
			else{
				return $ret;
			}
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
					$this->log->setAction("ERROR_USER_RESOLVE_NAMES");
					$this->log->setDetail("CALLING FUNCTION", "user->resolveUserNames()");
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
	
	public function sortUserList($userArray, $sortColumn, $sortDirection="ASC"){
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
			
			$userList = implode("','",$implodeArray);
			
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
										WHERE uuid IN ('".$userList."')
										ORDER BY ".$sortColumn." ".$sortDirection);
			
			if($res->num_rows > 0){
				while($row = $res->fetch_assoc()){
					$returnArray[$row['uuid']]['userFirstName'] = $row['userFirstName'];
					$returnArray[$row['uuid']]['userLastName'] = $row['userLastName'];
					$returnArray[$row['uuid']]['userHandle'] = $row['userHandle'];
					$returnArray[$row['uuid']]['userEmail'] = $row['userEmail'];
					$returnArray[$row['uuid']]['userRank'] = $row['userRank'];
					$returnArray[$row['uuid']]['userRole'] = $row['userRole'];
					$returnArray[$row['uuid']]['userOfficeSymbol'] = $row['userOfficeSymbol'];
					$returnArray[$row['uuid']]['userBase'] = $row['userBase'];
					$returnArray[$row['uuid']]['fullName'] = $this->getFullName($row['userRank'],$row['userFirstName'],$row['userLastName']);
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

    public function sortUserUUIDList($userArray, $sortColumn, $sortDirection="ASC"){
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

            $userList = implode("','",$implodeArray);

            $res = $this->db->query("SELECT uuid
										FROM userData
										WHERE uuid IN ('".$userList."')
										ORDER BY ".$sortColumn." ".$sortDirection);

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
			
			if($count > 0){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$this->log->setAction("ERROR_USER_VERIFY");
			$this->log->setDetail("Calling Function","user->verifyUser()");
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
			
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}
}
?>