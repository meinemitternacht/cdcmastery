<?php

class officeSymbol extends CDCMastery
{
	protected $db;
	protected $log;
	
	public $error;
	
	public $uuid;
	public $officeSymbol;
	
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}
	
	public function addOfficeSymbol($officeSymbolName){
		$this->setOfficeSymbol($officeSymbolName);
		$this->setUUID(parent::genUUID());
		
		if($this->saveOfficeSymbol()){
			$this->log->setAction("OFFICE_SYMBOL_ADD");
			$this->log->setDetail("Office Symbol Name",$officeSymbolName);
			$this->log->setDetail("UUID",$this->getUUID());
			$this->log->saveEntry();
			
			return true;
		}
		else{
			$this->log->setAction("ERROR_OFFICE_SYMBOL_ADD");
			$this->log->setDetail("Office Symbol Name",$officeSymbolName);
			$this->log->setDetail("UUID",$this->getUUID());
			$this->log->saveEntry();
			
			return false;
		}
	}
	
	public function editOfficeSymbol($officeSymbolUUID, $officeSymbolName){
		$this->loadOfficeSymbol($officeSymbolUUID);
		$this->setOfficeSymbol($officeSymbolName);
		
		if($this->saveOfficeSymbol()){
			$this->log->setAction("OFFICE_SYMBOL_EDIT");
			$this->log->setDetail("Office Symbol Name",$officeSymbolName);
			$this->log->setDetail("UUID",$officeSymbolUUID);
			$this->log->saveEntry();
			
			return true;
		}
		else{
			$this->log->setAction("ERROR_OFFICE_SYMBOL_EDIT");
			$this->log->setDetail("Office Symbol Name",$officeSymbolName);
			$this->log->setDetail("UUID",$officeSymbolUUID);
			$this->log->saveEntry();
			
			return false;
		}
	}
	
	public function deleteOfficeSymbol($uuid){
		if($this->getOfficeSymbol($uuid)){
			$logOSName = $this->getOfficeSymbol($uuid);
		
			$stmt = $this->db->prepare("DELETE FROM officeSymbolList WHERE uuid = ?");
			$stmt->bind_param("s",$uuid);
			
			if(!$stmt->execute()){
				$this->log->setAction("ERROR_OFFICE_SYMBOL_DELETE");
				$this->log->setDetail("MySQL Error", $stmt->error);
				$this->log->setDetail("UUID",$uuid);
				$this->log->setDetail("Office Symbol Name",$logOSName);
				$this->log->saveEntry();
				
				return false;
			}
			else{
				$this->log->setAction("OFFICE_SYMBOL_DELETE");
				$this->log->setDetail("UUID",$uuid);
				$this->log->setDetail("Office Symbol Name",$logOSName);
				$this->log->saveEntry();
				
				return true;
			}
		}
		else{
			$_SESSION['messages'][] = "That Office Symbol does not exist.";
			return false;
		}
	}
	
	public function listOfficeSymbols(){
		$res = $this->db->query("SELECT uuid, officeSymbol FROM officeSymbolList ORDER BY officeSymbol ASC");
		
		$osArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$osArray[$row['uuid']] = $row['officeSymbol'];
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
			return $osArray;
		}
	}
	
	public function loadOfficeSymbol($uuid){
		$stmt = $this->db->prepare("SELECT	uuid,
											officeSymbol
									FROM officeSymbolList
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result( $uuid,
								$officeSymbol);
			
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->officeSymbol = $officeSymbol;
				
				$ret = true;
			}
			
			$stmt->close();
			
			if(empty($this->uuid)){
				$this->error = "That office symbol does not exist.";
				$ret = false;
			}
			
			return $ret;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();
				
			$this->log->setAction("ERROR_OFFICE_SYMBOL_LOAD");
			$this->log->setDetail("CALLING FUNCTION", "officeSymbol->loadOfficeSymbol()");
			$this->log->setDetail("Office Symbol UUID",$uuid);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
	}
	
	public function saveOfficeSymbol(){
		$stmt = $this->db->prepare("INSERT INTO officeSymbolList (  uuid,
																	officeSymbol )
									VALUES (?,?)
									ON DUPLICATE KEY UPDATE
										uuid=VALUES(uuid),
										officeSymbol=VALUES(officeSymbol)");
		$stmt->bind_param("ss", 	$this->uuid,
									$this->officeSymbol);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_OFFICE_SYMBOL_SAVE");
			$this->log->setDetail("CALLING FUNCTION", "officeSymbol->saveOfficeSymbol()");
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
	
	public function getOfficeSymbol($uuid = false){
		if(!empty($uuid)){
			$_officeSymbols = new officeSymbol($this->db, $this->log);
			if(!$_officeSymbols->loadOfficeSymbol($uuid)){
				$this->error = $_officeSymbols->error;
				return false;
			}
			else{
				return htmlspecialchars($_officeSymbols->getOfficeSymbol());
			}
		}
		else{
			return htmlspecialchars($this->officeSymbol);
		}
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	public function setOfficeSymbol($officeSymbol){
		$this->officeSymbol = htmlspecialchars_decode($officeSymbol);
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}