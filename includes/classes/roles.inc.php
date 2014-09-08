<?php

class roles extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;
	
	public $uuid;
	public $roleType;
	public $roleName;
	public $roleDescription;
	
	public $permissionArray;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function listRoles(){
		$res = $this->db->query("SELECT uuid, roleType, roleName, roleDescription FROM roleList ORDER BY roleName ASC");
		
		$roleArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$roleArray[$row['uuid']]['roleType'] = $row['roleType'];
				$roleArray[$row['uuid']]['roleName'] = $row['roleName'];
				$roleArray[$row['uuid']]['roleDescription'] = $row['roleDescription'];
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
			return $roleArray;
		}
	}
	
	public function loadRole($uuid){
		$stmt = $this->db->prepare("SELECT	uuid,
											roleType,
											roleName,
											roleDescription
									FROM roleList
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		$stmt->execute();
		$stmt->bind_result( $uuid,
							$roleType,
							$roleName,
							$roleDescription );
		
		while($stmt->fetch()){
			$this->uuid = $uuid;
			$this->roleType = $roleType;
			$this->roleName = $roleName;
			$this->roleDescription = $roleDescription;
			
			$ret = true;
		}
		
		$stmt->close();
		
		if(empty($this->uuid)){
			$this->error = "That role does not exist.";
			$ret = false;
		}
		
		return $ret;
	}
	
	public function saveRole(){
		$stmt = $this->db->prepare("INSERT INTO roleList (  uuid,
															roleType,
															roleName,
															roleDescription
									VALUES (?,?,?,?)
									ON DUPLICATE KEY UPDATE
										uuid=VALUES(uuid),
										roleType=VALUES(roleType),
										roleName=VALUES(roleName),
										roleDescription=VALUES(roleDescription)");
		$stmt->bind_param("ssss", 	$this->uuid,
									$this->roleType,
									$this->roleName,
									$this->roleDescription);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION", "roles->saveRole()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}
	
	public function getUUID(){
		return $this->uuid;
	}
	
	public function getRoleType($uuid = false){
		if(isset($uuid)){
			$_roles = new roles($this->db, $this->log);
			
			if($_roles->loadRole($uuid)){
				$tempRoleType = $_roles->getRoleType();
			
				return htmlspecialchars($tempRoleType);
			}
			else{
				$this->error = $_roles->error;
				return false;
			}
		}
		else{
			return htmlspecialchars($this->roleType);
		}
	}
	
	public function getMigratedRoleUUID($oldID){
		$stmt = $this->db->prepare("SELECT uuid FROM roleList WHERE oldID = ?");
		$stmt->bind_param("s",$oldID);
		
		if($stmt->execute()){
			$stmt->bind_result($uuid);
				
			while($stmt->fetch()){
				$retUUID = $uuid;
			}
				
			if(!empty($retUUID)){
				return $retUUID;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	public function getRoleName(){
		return htmlspecialchars($this->roleName);
	}
	
	public function getRoleDescription(){
		return htmlspecialchars($this->roleDescription);
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setRoleType($roleType){
		$this->roleType = htmlspecialchars_decode($roleType);
		return true;
	}
	
	public function setRoleName($roleName){
		$this->roleName = htmlspecialchars_decode($roleName);
		return true;
	}
	
	public function setRoleDescription($roleDescription){
		$this->roleDescription = htmlspecialchars_decode($roleDescription);
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}