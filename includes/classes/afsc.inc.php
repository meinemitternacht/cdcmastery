<?php

class afsc extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;
	
	public $uuid;
	public $afscName;
	public $afscDescription;
	public $afscVersion;
	public $afscFOUO;
	public $afscHidden;
	public $oldID;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function verifyAFSC($afscUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM afscList WHERE uuid = ?");
		$stmt->bind_param("s",$afscUUID);
		
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
			$this->log->setAction("ERROR_AFSC_VERIFY");
			$this->log->setDetail("Calling Function","afsc->verifyAFSC()");
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->saveEntry();
				
			$this->error = $stmt->error;
			$stmt->close();
			return false;
		}
	}
	
	public function listAFSC(){
		$res = $this->db->query("SELECT uuid, afscName, afscDescription, afscVersion, afscFOUO, afscHidden, oldID FROM afscList ORDER BY afscName ASC");
		
		$afscArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$afscArray[$row['uuid']]['afscName'] = $row['afscName'];
				$afscArray[$row['uuid']]['afscDescription'] = $row['afscDescription'];
				$afscArray[$row['uuid']]['afscVersion'] = $row['afscVersion'];
				$afscArray[$row['uuid']]['afscFOUO'] = $row['afscFOUO'];
				$afscArray[$row['uuid']]['afscHidden'] = $row['afscHidden'];
				$afscArray[$row['uuid']]['oldID'] = $row['oldID'];
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
			return $afscArray;
		}
	}
	
	public function loadAFSC($uuid){
		$stmt = $this->db->prepare("SELECT	uuid,
											afscName,
											afscDescription,
											afscVersion,
											afscFOUO,
											afscHidden,
											oldID
									FROM afscList
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result( $uuid,
								$afscName,
								$afscDescription,
								$afscVersion,
								$afscFOUO,
								$afscHidden,
								$oldID );
			
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->afscName = $afscName;
				$this->afscDescription = $afscDescription;
				$this->afscVersion = $afscVersion;
				$this->afscFOUO = $afscFOUO;
				$this->afscHidden = $afscHidden;
				$this->oldID = $oldID;
				
				$ret = true;
			}
			
			$stmt->close();

			if(empty($this->uuid)){
				$this->error = "That AFSC does not exist.";
				$ret = false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();
				
			$this->log->setAction("ERROR_AFSC_LOAD");
			$this->log->setDetail("CALLING FUNCTION", "afsc->loadAFSC()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("UUID",$uuid);
			$this->log->saveEntry();
			
			$ret = false;
		}
		
		return $ret;
	}
	
	public function saveAFSC(){
		$stmt = $this->db->prepare("INSERT INTO afscList (  uuid,
															afscName,
															afscDescription,
															afscVersion,
															afscFOUO,
															afscHidden )
									VALUES (?,?,?,?,?,?)
									ON DUPLICATE KEY UPDATE
										uuid=VALUES(uuid),
										afscName=VALUES(afscName),
										afscDescription=VALUES(afscDescription),
										afscVersion=VALUES(afscVersion),
										afscFOUO=VALUES(afscFOUO),
										afscHidden=VALUES(afscHidden)");
		$stmt->bind_param("ssssss", $this->uuid,
									$this->afscName,
									$this->afscDescription,
									$this->afscVersion,
									$this->afscFOUO,
									$this->afscHidden);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_AFSC_SAVE");
			$this->log->setDetail("CALLING FUNCTION", "afsc->saveAFSC()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}
	
	public function getMigratedAFSCUUID($oldID){
		$stmt = $this->db->prepare("SELECT uuid FROM afscList WHERE oldID = ?");
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
	
	public function getUUID(){
		return $this->uuid;
	}
	
	public function getAFSCName($uuid=false){
		if(!empty($uuid)){
			$_afsc = new afsc($this->db, $this->log);
			
			if(!$_afsc->loadAFSC($uuid)){
				$this->error = $_afsc->error;
				return false;
			}
			else{
				return htmlspecialchars($_afsc->getAFSCName());
			}
		}
		else{
			return htmlspecialchars($this->afscName);
		}
	}
	
	public function getAFSCDescription(){
		return htmlspecialchars($this->afscDescription);
	}
	
	public function getAFSCVersion(){
		return htmlspecialchars($this->afscVersion);
	}
	
	public function getAFSCFOUO(){
		return $this->afscFOUO;
	}
	
	public function getAFSCHidden(){
		return $this->afscHidden;
	}
	
	public function getOldID(){
		return $this->oldID;
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setAFSCName($afscName){
		$this->afscName = htmlspecialchars_decode($afscName);
		return true;
	}
	
	public function setAFSCDescription($afscDescription){
		$this->afscDescription = htmlspecialchars_decode($afscDescription);
		return true;
	}
	
	public function setAFSCVersion($afscVersion){
		$this->afscVersion = htmlspecialchars_decode($afscVersion);
		return true;
	}
	
	public function setAFSCFOUO($afscFOUO){
		$this->afscFOUO = $afscFOUO;
		return true;
	}
	
	public function setAFSCHidden($afscHidden){
		$this->afscHidden = $afscHidden;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}