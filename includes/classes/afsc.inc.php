<?php

/**
 * Class afsc
 */
class afsc extends CDCMastery
{
	/**
	 * @var mysqli
     */
	protected $db;
	/**
	 * @var log
     */
	protected $log;

	/**
	 * @var
     */
	public $error;

	/**
	 * @var
     */
	public $uuid;
	/**
	 * @var
     */
	public $afscName;
	/**
	 * @var
     */
	public $afscDescription;
	/**
	 * @var
     */
	public $afscVersion;
	/**
	 * @var
     */
	public $afscFOUO;
	/**
	 * @var
     */
	public $afscHidden;
	/**
	 * @var
     */
	public $oldID;

	/**
	 * @param mysqli $db
	 * @param log $log
     */
	public function __construct(mysqli $db, log $log){
		$this->db = $db;
		$this->log = $log;
	}

	/**
	 * @return bool
     */
	public function newAFSC(){
        $this->uuid = parent::genUUID();
        $this->afscName = null;
        $this->afscVersion = null;
        $this->afscFOUO = null;
        $this->afscDescription = null;
        $this->afscHidden = false;

        return true;
    }

	/**
	 * @param $afscUUID
	 * @return bool
     */
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

	/**
	 * @return array|bool
     */
	public function listAFSC($showHidden=true){
		if(!$showHidden) {
			$res = $this->db->query("SELECT uuid, afscName, afscDescription, afscVersion, afscFOUO, afscHidden, oldID FROM afscList WHERE afscHidden = 0 ORDER BY afscName ASC");
		}
		else{
			$res = $this->db->query("SELECT uuid, afscName, afscDescription, afscVersion, afscFOUO, afscHidden, oldID FROM afscList ORDER BY afscName ASC");
		}

		$afscArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$afscArray[$row['uuid']]['afscName'] = $row['afscName'];
				$afscArray[$row['uuid']]['afscDescription'] = $row['afscDescription'];
				$afscArray[$row['uuid']]['afscVersion'] = $row['afscVersion'];
				$afscArray[$row['uuid']]['afscFOUO'] = $row['afscFOUO'];
				$afscArray[$row['uuid']]['afscHidden'] = $row['afscHidden'];
				$afscArray[$row['uuid']]['oldID'] = $row['oldID'];
				$afscArray[$row['uuid']]['totalQuestions'] = $this->getTotalQuestions($row['uuid']);
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

	/**
	 * @param $uuid
	 * @return bool
     */
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

	/**
	 * @return bool
     */
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

	/**
	 * @param $oldID
	 * @return bool
     */
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

	/**
	 * @param bool|false $uuid
	 * @return bool
     */
	public function getTotalQuestions($uuid = false){
		if($uuid) {
			$tempAFSC = new afsc($this->db, $this->log);
			if(!$tempAFSC->loadAFSC($uuid)){
				return false;
			}
			else{
				return $tempAFSC->getTotalQuestions();
			}
		}

		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM questionData WHERE afscUUID = ?");
		$stmt->bind_param("s",$this->uuid);

		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();

			if(!empty($count)){
				return $count;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
	 * @return mixed
     */
	public function getUUID(){
		return $this->uuid;
	}

	/**
	 * @param bool|false $uuid
	 * @return bool|string
     */
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

	/**
	 * @param string $uuid
	 * @return bool|string
	 */
	public function getAFSCNameCallback(&$item){
		$afscName = $this->getAFSCName($item);
		$item = $afscName;
		return true;
	}

	/**
	 * @return string
     */
	public function getAFSCDescription(){
		return htmlspecialchars($this->afscDescription);
	}

	/**
	 * @return string
     */
	public function getAFSCVersion(){
		return htmlspecialchars($this->afscVersion);
	}

	/**
	 * @return mixed
     */
	public function getAFSCFOUO(){
		return $this->afscFOUO;
	}

	/**
	 * @return mixed
     */
	public function getAFSCHidden(){
		return $this->afscHidden;
	}

	/**
	 * @return mixed
     */
	public function getOldID(){
		return $this->oldID;
	}

	/**
	 * @param $uuid
	 * @return bool
     */
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}

	/**
	 * @param $afscName
	 * @return bool
     */
	public function setAFSCName($afscName){
		$this->afscName = htmlspecialchars_decode($afscName);
		return true;
	}

	/**
	 * @param $afscDescription
	 * @return bool
     */
	public function setAFSCDescription($afscDescription){
		$this->afscDescription = htmlspecialchars_decode($afscDescription);
		return true;
	}

	/**
	 * @param $afscVersion
	 * @return bool
     */
	public function setAFSCVersion($afscVersion){
		$this->afscVersion = htmlspecialchars_decode($afscVersion);
		return true;
	}

	/**
	 * @param $afscFOUO
	 * @return bool
     */
	public function setAFSCFOUO($afscFOUO){
		$this->afscFOUO = $afscFOUO;
		return true;
	}

	/**
	 * @param $afscHidden
	 * @return bool
     */
	public function setAFSCHidden($afscHidden){
		$this->afscHidden = $afscHidden;
		return true;
	}

	/**
	 *
     */
	public function __destruct(){
		parent::__destruct();
	}
}