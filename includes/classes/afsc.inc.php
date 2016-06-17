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

			$this->log->setAction("ERROR_AFSC_VERIFY");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();
			return false;
		}
	}

	/**
	 * @param bool $showHidden
	 * @return array|bool
	 */
	public function listAFSC($showHidden=true){
		if(!$showHidden) {
			$res = $this->db->query("SELECT uuid, afscName, afscDescription, afscVersion, afscFOUO, afscHidden FROM afscList WHERE afscHidden = 0 ORDER BY afscName ASC");
		}
		else{
			$res = $this->db->query("SELECT uuid, afscName, afscDescription, afscVersion, afscFOUO, afscHidden FROM afscList ORDER BY afscName ASC");
		}

		$afscArray = Array();

		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$afscArray[$row['uuid']]['afscName'] = $row['afscName'];
				$afscArray[$row['uuid']]['afscDescription'] = $row['afscDescription'];
				$afscArray[$row['uuid']]['afscVersion'] = $row['afscVersion'];
				$afscArray[$row['uuid']]['afscFOUO'] = $row['afscFOUO'];
				$afscArray[$row['uuid']]['afscHidden'] = $row['afscHidden'];
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
	 * @param $showHidden
	 * @return array|bool
	 */
	public function listAFSCUUID($showHidden=true){
		if(!$showHidden) {
			$res = $this->db->query("SELECT uuid FROM afscList WHERE afscHidden = 0 ORDER BY afscName ASC");
		}
		else{
			$res = $this->db->query("SELECT uuid FROM afscList ORDER BY afscName ASC");
		}

		$afscArray = Array();

		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$afscArray[] = $row['uuid'];
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
			}
			
			$stmt->close();

			if(empty($this->uuid)){
				$this->error = "That AFSC does not exist.";
				return false;
			}
			else{
				return true;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();
				
			$this->log->setAction("ERROR_AFSC_LOAD");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("UUID",$uuid);
			$this->log->saveEntry();
			
			return false;
		}
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

	/**
	 * @param $afscName
	 * @return bool
	 */
	public function getAFSCUUIDByName($afscName){
		$stmt = $this->db->prepare("SELECT uuid FROM afscList WHERE afscName = ?");
		$stmt->bind_param("s",$afscName);
		
		if($stmt->execute()){
			$stmt->bind_result($uuid);
			$stmt->fetch();
			$stmt->close();
			
			if(!empty($uuid)){
				return $uuid;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_AFSC_GET_UUID_BY_NAME");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("AFSC Name",$afscName);
			$this->log->saveEntry();
			
			return false;
		}
	}

	/**
	 * @param $targetStatus
	 * @return bool
	 */
	public function toggleFOUO($targetStatus){
		$answerManager = new answerManager($this->db,$this->log);
		$questionManager = new questionManager($this->db,$this->log,$this,$answerManager);

		if($this->afscFOUO === $targetStatus){
			return true;
		}
		else{
			$questionManager->setAFSCUUID($this->uuid);
			$questionManager->setFOUO($this->afscFOUO);

			$questionUUIDList = $questionManager->listQuestionsForAFSC();

			$error = false;
			$errorUUIDArray = Array();

			foreach($questionUUIDList as $questionUUID){
				$questionManager->loadQuestion($questionUUID);

				$answerUUIDList = $questionManager->loadAssociatedAnswers();

				$answerManager->setFOUO($this->afscFOUO);

				foreach($answerUUIDList as $answerUUID => $answerData){
					$answerManager->loadAnswer($answerUUID);
					$answerManager->setFOUO($targetStatus);
					$answerManager->saveAnswer();
					$answerManager->setFOUO($this->afscFOUO);
				}

				$questionManager->setFOUO($targetStatus);
				if(!$questionManager->saveQuestion()){
					$error = true;
					$errorUUIDArray[] = $questionUUID;
				}
				$questionManager->setFOUO($this->afscFOUO);
			}

			if(isset($error) && $error == true){
				$this->log->setAction("ERROR_TOGGLE_AFSC_FOUO");
				$this->log->setDetail("Target FOUO Status",$targetStatus);

				if(isset($questionUUIDList) && !empty($questionUUIDList) && sizeof($questionUUIDList) > 1){
					foreach($questionUUIDList as $questionUUIDError){
						$this->log->setDetail("Question UUID",$questionUUIDError);
					}
				}
				else{
					$this->log->setDetail("Question UUID",$questionUUIDList[0]);
				}

				$this->log->setDetail("AFSC UUID",$this->uuid);
				$this->log->setDetail("Question Manager Error",$questionManager->error);
				$this->log->saveEntry();

				return false;
			}
			else{
                $this->log->setAction("TOGGLE_AFSC_FOUO");
                $this->log->setDetail("AFSC UUID",$this->uuid);
                $this->log->setDetail("Target FOUO Status",$targetStatus);
                $this->log->saveEntry();
				return true;
			}
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
			$stmt->close();

			if(!empty($count)){
				return $count;
			}
			else{
				return false;
			}
		}
		else{
			$sqlError = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_AFSC_GET_TOTAL_QUESTIONS");
			if($uuid){
				$this->log->setDetail("AFSC UUID",$uuid);
			}
			else{
				$this->log->setDetail("AFSC UUID",$this->uuid);
			}
			$this->log->setDetail("MySQL Error",$sqlError);
			$this->log->saveEntry();
			
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
	 * @param $item
	 * @return bool|string
	 * @internal param string $uuid
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
		$this->afscVersion = $afscVersion;
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