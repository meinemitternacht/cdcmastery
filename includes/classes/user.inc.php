<?php

/*
This script provides a class interface for the site users
*/

class user extends CDCMastery
{
	protected $db;			//holds database object
	protected $log;			//holds the log object

	private $tempRow;		//holds rows temporarily
	private $tempRes;		//holds result set temporarily
	private $stmt;			//holds statements
	private $i;				//increment value

	public $error;			//holds error message(s)

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

	public function __construct(log $log, mysqli $db) {
		$this->db = $db;
		$this->log = $log;
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

			$ret = true;
		}

		$stmt->close();

		if(empty($this->uuid)){
			$this->error = "That user does not exist";
			$ret = false;
		}
		
		return $ret;
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
			
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION", "auth->saveUser()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
		else{
			$stmt->close();
			return true;
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
			$this->log->setAction("MYSQL_ERROR");
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

	public function getFullName(){
		$fullName = $this->getUserRank() . ' ' . $this->getUserFirstName() . ' ' . $this->getUserLastName();

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
		$_user = new user($this->log, $this->db);
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

	public function verifyUser($uuid){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		$stmt->execute();
		$stmt->bind_result($count);

		while($stmt->fetch()){
			$rowCount = $count;
		}

		$stmt->close();

		if($rowCount > 0){
			return true;
		}
		else{
			return false;
		}
	}
}
?>