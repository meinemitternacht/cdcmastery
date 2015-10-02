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

	public function addBase($baseName){
		$this->uuid = $this->genUUID();
		$this->setBaseName($baseName);

		if($this->saveBase()){
			$this->log->setAction("BASE_ADD");
			$this->log->setDetail("Base UUID", $this->uuid);
			$this->log->setDetail("Base Name",$this->baseName);
			$this->log->saveEntry();

			return true;
		} else{
			$this->log->setAction("ERROR_BASE_ADD");
			$this->log->setDetail("CALLING FUNCTION", "bases->addBase()");
			$this->log->setDetail("Base UUID", $this->uuid);
			$this->log->setDetail("Base Name",$this->baseName);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	public function editBase($baseUUID,$baseName){
		if($this->loadBase($baseUUID)) {
			$this->setBaseName($baseName);

			if ($this->saveBase()) {
				$this->log->setAction("BASE_EDIT");
				$this->log->setDetail("Base UUID", $this->uuid);
				$this->log->setDetail("Base Name", $this->baseName);
				$this->log->saveEntry();

				return true;
			} else {
				$this->log->setAction("ERROR_BASE_EDIT");
				$this->log->setDetail("CALLING FUNCTION", "bases->editBase()");
				$this->log->setDetail("Base UUID", $this->uuid);
				$this->log->setDetail("Base Name", $this->baseName);
				$this->log->setDetail("ERROR", $this->error);
				$this->log->saveEntry();

				return false;
			}
		} else {
			return false;
		}
	}

    public function deleteBase($baseUUID){
        if($this->loadBase($baseUUID)){
            $stmt = $this->db->prepare("DELETE FROM baseList WHERE uuid = ?");
            $stmt->bind_param("s",$baseUUID);

            if($stmt->execute()){
                $this->log->setAction("BASE_DELETE");
                $this->log->setDetail("Base UUID", $this->uuid);
                $this->log->setDetail("Base Name", $this->baseName);
                $this->log->saveEntry();
                $stmt->close();

                return true;
            }
            else{
                $this->log->setAction("ERROR_BASE_DELETE");
                $this->log->setDetail("CALLING FUNCTION", "bases->deleteBase()");
                $this->log->setDetail("Base UUID", $this->uuid);
                $this->log->setDetail("Base Name", $this->baseName);
                $this->log->setDetail("MYSQL_ERROR", $stmt->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }
        else{
            $this->error = "That base does not exist.";
            $this->log->setAction("ERROR_BASE_DELETE");
            $this->log->setDetail("CALLING FUNCTION", "bases->deleteBase()");
            $this->log->setDetail("Base UUID", $this->uuid);
            $this->log->setDetail("Base Name", $this->baseName);
            $this->log->setDetail("ERROR", $this->error);
            $this->log->saveEntry();

            return false;
        }
    }

	public function listBases(){
		$res = $this->db->query("SELECT uuid, baseName FROM baseList ORDER BY baseName ASC");
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$baseArray[$row['uuid']] = $row['baseName'];
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
			return $baseArray;
		}
	}

    public function listUserBases(){
        $res = $this->db->query("SELECT DISTINCT(userBase), baseName FROM userData LEFT JOIN baseList ON baseList.uuid = userData.userBase ORDER BY baseName ASC");

        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $baseArray[$row['userBase']] = $row['baseName'];
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
            return $baseArray;
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
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_BASE_LOAD");
			$this->log->setDetail("CALLING FUNCTION", "bases->loadBase()");
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("UUID",$uuid);
			$this->log->saveEntry();
			
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
			
			$this->log->setAction("ERROR_BASE_SAVE");
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