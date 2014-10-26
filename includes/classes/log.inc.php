<?php

/*
This script provides a class interface for the site logging function.
*/

class log extends CDCMastery
{
	protected $db;				//holds database object

	private $tempRow;			//holds rows temporarily
	private $tempRes;			//holds result set temporarily
	private $stmt;				//holds statements
	private $i;					//increment value
	
	public $error;				//error messages (array)

	public $uuid;				//uuid of the log entry
	public $timestamp;			//timestamp of the log entry
	public $action;				//log entry action
	public $userUUID;			//uuid of the user
	public $ip;					//ip of the user

	public $uuidDetail;			//uuid of the log detail
	public $timestampDetail;	//timestamp of the log detail
	public $typeDetail;			//log detail data type
	public $dataDetail;			//log detail data

	public $detailArray;		//array of log details
	public $detailCount;		//count of detail array

	function __construct(mysqli $db) {
		$this->db = $db;
		$this->uuid = parent::genUUID();
		$this->timestamp = date("Y-m-d H:i:s",time());
		
		if(php_sapi_name() != 'cli'){
			$logUID = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "ANONYMOUS";
			$this->setUserUUID($logUID);
			$this->setIP($_SERVER['REMOTE_ADDR']);
		}
		else{
			$this->setUserUUID("SYSTEM");
			$this->setIP("127.0.0.1");
		}
	}

	function cleanEntry(){
		$this->uuid				= NULL;
		$this->timestamp		= NULL;
		$this->action			= NULL;
		$this->userUUID			= NULL;
		$this->ip				= NULL;
		$this->uuidDetail		= NULL;
		$this->timestampDetail	= NULL;
		$this->typeDetail		= NULL;
		$this->dataDetail		= NULL;
		$this->detailArray		= NULL;
		$this->detailCount		= NULL;

		if(php_sapi_name() != 'cli'){
			$logUserUUID = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "ANONYMOUS";
			$this->setUserUUID($logUserUUID);
			$this->setIP($_SERVER['REMOTE_ADDR']);
		}
		else{
			$this->setUserUUID("SYSTEM");
			$this->setIP("127.0.0.1");
		}

		return true;
	}

    function clearLogEntries($userUUID){
        $stmt = $this->db->prepare("DELETE FROM systemLog WHERE userUUID = ?");
        $stmt->bind_param("s",$userUUID);

        if(!$stmt->execute()){
            $this->setAction("ERROR_USER_LOG_CLEAR");
            $this->setDetail("MySQL Error",$stmt->error);
            $this->setDetail("Calling Function","log->clearLogEntries()");
            $this->setDetail("User UUID",$userUUID);
            $this->saveEntry();

            $stmt->close();

            return false;
        }
        else{
            $this->setAction("USER_LOG_CLEAR");
            $this->setDetail("User UUID",$userUUID);
            $this->setDetail("Affected Rows",$stmt->affected_rows);
            $this->saveEntry();

            $stmt->close();

            return true;
        }
    }

	function fetchDetails($uuid){
		$stmt = $this->db->prepare('SELECT uuid, dataType, data FROM systemLogData WHERE logUUID = ? ORDER BY dataType ASC');
		$stmt->bind_param("s",$uuid);
		$stmt->execute();
		
		$stmt->bind_result($detailUUID, $dataType, $data);
		
		$i = 0;
		while ($stmt->fetch()) {
			$this->detailArray[$i]['uuid'] = $detailUUID;
			$this->detailArray[$i]['dataType'] = htmlspecialchars($dataType);
			$this->detailArray[$i]['data'] = htmlspecialchars($data);

			$i++;
		}

		return $this->detailArray;
	}

	function loadEntry($uuid) {
		$stmt = $this->db->prepare('SELECT uuid, timestamp, action, userUUID, ip FROM systemLog WHERE uuid = ?');
		$stmt->bind_param("s",$uuid);
		$stmt->execute();

		$stmt->bind_result($logUUID, $timestamp, $action, $userUUID, $ip);

		while($stmt->fetch()) {
			$this->uuid = $logUUID;
			$this->timestamp = $timestamp;
			$this->action = $action;
			$this->userUUID = $userUUID;
			$this->ip = $ip;
		}

		if(!empty($this->uuid)){
			return true;
		}
		else{
			return false;
		}
	}

	function printEntry() {
		$string = "UUID: " . $this->uuid . " Timestamp: " . $this->timestamp . " Action: " . $this->action . " User ID: " . $this->userUUID . " IP: " . $this->ip;

		if(isset($this->detailArray) && !empty($this->detailArray))
		{
			foreach($this->detailArray as $row)
			{
				foreach($row as $key => $var)
				{
					$string .= " ".htmlspecialchars($key).": ".htmlspecialchars($var);
				}
			}
		}

		return $string;
	}

	function regenerateUUID() {
		$this->uuid = parent::genUUID();
		return true;
	}

	function saveEntry() {
		$stmt = $this->db->prepare('INSERT INTO systemLog (uuid, timestamp, userUUID, action, ip) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
		$stmt->bind_param('sssss', $this->uuid, $this->timestamp, $this->userUUID, $this->action, $this->ip);
		if(!$stmt->execute()) {
			$this->error[] = $stmt->error;
			return false;
		}

		if(isset($this->detailArray) && !empty($this->detailArray)) {
			$stmt = $this->db->prepare('INSERT INTO systemLogData (uuid, logUUID, dataType, data) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
			$this->detailCount = count($this->detailArray);

			for($i=0;$i < $this->detailCount; $i++)
			{
				if(!$stmt->bind_param('ssss', $this->detailArray[$i]['uuid'], $this->uuid, $this->detailArray[$i]['type'], $this->detailArray[$i]['data'])) {
					$this->error[] = $stmt->error;
					return false;
				}

				if(!$stmt->execute()) {
					$this->error[] = $stmt->error;
					return false;
				}
			}
		}

		$this->cleanEntry();
		$this->regenerateUUID();

		return true;
	}
	
	function verifyLogUUID($logUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE uuid = ?");
		$stmt->bind_param("s",$logUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			
			$stmt->close();
			
			if($count){
				return true;
			}
			else{
				return false;
			}
		}
	}

	function setIP($ip) {
		$this->ip = htmlspecialchars_decode($ip);
		return true;
	}

	function setAction($action) {
		$this->action = htmlspecialchars_decode($action);
		return true;
	}

	function setDetail($type, $data) {
		$this->uuidDetail = $this->genUUID();
		$this->timestampDetail = date("Y-m-d H:i:s",time());
		$this->typeDetail = htmlspecialchars_decode($type);

		if(is_array($data)) {
			$this->dataDetail = implode(",",$data);
			$this->dataDetail = htmlspecialchars_decode($this->dataDetail);
		}
		else {
			$this->dataDetail = htmlspecialchars_decode($data);
		}

		$this->detailArray[] = Array(	"uuid" => $this->uuidDetail,
										"timestamp" => $this->timestampDetail,
										"type" => $this->typeDetail,
										"data" => $this->dataDetail
									);
		
		return true;
	}

	function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
		return true;
	}

	function setUserUUID($userUUID) {
		$this->userUUID = htmlspecialchars_decode($userUUID);
		return true;
	}

	function getAction() {
		return htmlspecialchars($this->action);
	}

	function getIP() {
		return $this->ip;
	}

	function getRowStyle($actionName){
		/*
		Warnings (Administrative functions, errors)
		Class Name: text-warning
		*/
		$warningArray = Array(
			'ACCESS_DENIED',
			'AFSC_DELETE',
			'AFSC_EDIT',
			'ERROR_EMAIL_QUEUE_PROCESS',
			'ERROR_EMAIL_SEND',
			'ERROR_LOGIN_INVALID_PASSWORD',
			'LOGIN_ERROR_UNKNOWN_USER',
			'LOGIN_RATE_RATE_LIMIT',
			'MYSQL_ERROR',
			'QUESTION_DELETE',
			'QUESTION_EDIT',
			'TEST_DELETE',
			'TEST_ERROR_UNAUTHORIZED',
			'USER_DELETE',
			'USER_EDIT',
			'USER_EDIT_PROFILE'
		);

		/*
		 * Normal entries
		 * .text-success
		 */
		$generalArray = Array(
			'EMAIL_SEND',
			'LOGIN_SUCCESS',
			'LOGOUT_SUCCESS',
			'MIGRATED_PASSWORD',
			'TEST_COMPLETED',
			'TEST_START',
			'USER_PASSWORD_RESET',
			'USER_REGISTER'
		);
		
		/*
		 * Informational entries
		 * .text-caution
		 */
		$cautionArray = Array(
			'ROUTING_ERROR',
			'AFSC_ADD',
			'QUESTION_ADD',
			'USER_ADD',
			'USER_ADD_AFSC_ASSOCIATION',
			'USER_ADD_TRAINING_MANAGER_ASSOCIATION',
			'USER_DELETE_AFSC_ASSOCIATION',
			'USER_PASSWORD_RESET',
			'USER_PASSWORD_RESET_COMPLETE',
			'USER_REMOVE_SUPERVISOR_ASSOCIATION',
			'USER_REMOVE_TRAINING_MANAGER_ASSOCIATION'
		);

		if(in_array($actionName,$warningArray)){
			$class = "text-warning";
		}
		elseif(in_array($actionName,$generalArray)){
			$class = "text-success";
		}
		elseif(in_array($actionName,$cautionArray)){
			$class = "text-caution";
		}
		else{
			$class = "text-caution";
		}

		return $class;
	}

	function getTimestamp() {
		return $this->timestamp;
	}

	function getUUID() {
		return $this->uuid;
	}

	function getUserUUID() {
		return $this->userUUID;
	}

	function __destruct() {
		parent::__destruct();
	}
}
?>