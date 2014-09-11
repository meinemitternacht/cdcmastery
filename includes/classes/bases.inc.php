<?php

class bases extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;
	
	public $uuid;
	public $baseName;
	public $oldID;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function listBases(){
		$res = $this->db->query("SELECT uuid, baseName FROM baseList ORDER BY baseName ASC");
		
		$roleArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$roleArray[$row['uuid']]['baseName'] = $row['baseName'];
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
	
	public function loadBase($uuid){
		$stmt = $this->db->prepare("SELECT	uuid,
											baseName,
											oldID
									FROM baseList
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result( $uuid,
								$baseName,
								$oldID);
			
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->baseName = $baseName;
				$this->oldID = $oldID;
				
				$ret = true;
			}
			
			$stmt->close();
			
			if(empty($this->uuid)){
				$this->error = "That base does not exist.";
				$ret = false;
			}
			
			return $ret;
		}
		else{
			return false;
		}
	}
	
	public function saveBase(){
		$stmt = $this->db->prepare("INSERT INTO baseList (  uuid,
															baseName,
															oldID )
									VALUES (?,?,?)
									ON DUPLICATE KEY UPDATE
										uuid=VALUES(uuid),
										baseName=VALUES(baseName),
										oldID=VALUES(oldID)");
		$stmt->bind_param("sss", 	$this->uuid,
									$this->baseName,
									$this->oldID);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION", "bases->saveBase()");
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
	
	public function getBaseName($uuid = false){
		if(!empty($uuid)){
			$_bases = new bases($this->db, $this->log);
			if(!$_bases->loadBase($uuid)){
				$this->error = $_bases->error;
				return false;
			}
			else{
				return htmlspecialchars($_bases->getBaseName());
			}
		}
		else{
			return htmlspecialchars($this->baseName);
		}
	}
	
	public function getOldID(){
		return htmlspecialchars($this->oldID);
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setBaseName($baseName){
		$this->baseName = htmlspecialchars_decode($baseName);
		return true;
	}
	
	public function setOldID($oldID){
		$this->oldID = htmlspecialchars_decode($oldID);
		return true;
	}
	
	public function getMigratedBaseUUID($oldID){
		$stmt = $this->db->prepare("SELECT uuid FROM baseList WHERE oldID = ?");
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
	
	public function __destruct(){
		parent::__destruct();
	}
}