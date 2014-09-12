<?php

/*
This script provides a class interface for the site logging function.
*/

class log extends CDCMastery
{
	protected $logDB;				//holds database object

	private $tempRow;			//holds rows temporarily
	private $tempRes;			//holds result set temporarily
	private $stmt;				//holds statements
	private $i;					//increment value

	public $uuid;				//uuid of the log entry
	public $timestamp;			//timestamp of the log entry
	public $action;				//log entry action
	public $userUUID;				//id of the user
	public $ip;					//ip of the user

	public $uuidDetail;			//uuid of the log detail
	public $timestampDetail;	//timestamp of the log detail
	public $typeDetail;			//log detail data type
	public $dataDetail;			//log detail data

	public $detailArray;		//array of log details
	public $detailCount;		//count of detail array

	function __construct(mysqli $db) {
		$this->logDB = $db;
		$this->uuid = parent::genUUID();
		$this->timestamp = date("Y-m-d H:i:s",time());
		if(php_sapi_name() != 'cli'){
			$logUID = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "ANONYMOUS";
			$this->setuserUUID($logUID);
			$this->setIP($_SERVER['REMOTE_ADDR']);
		}
		else{
			$this->setuserUUID("SYSTEM");
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
			$logUID = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "ANONYMOUS";
			$this->setuserUUID($logUID);
			$this->setIP($_SERVER['REMOTE_ADDR']);
		}
		else{
			$this->setuserUUID("SYSTEM");
			$this->setIP("127.0.0.1");
		}

		return true;
	}

	function fetchDetails($uuid){
		$this->stmt = $this->logDB->prepare('SELECT uuid, dataType, data FROM systemLogData WHERE logUUID = ? ORDER BY dataType ASC');
		$this->stmt->bind_param("s",$uuid);
		$this->stmt->execute();

		/* bind result variables */
		$this->stmt->bind_result($logUUID, $dataType, $data);

		/* fetch values */
		$this->i = 0;
		while ($this->stmt->fetch()) {
			$this->detailArray[$this->i]['uuid'] = $logUUID;
			$this->detailArray[$this->i]['dataType'] = htmlspecialchars($dataType);
			$this->detailArray[$this->i]['data'] = htmlspecialchars($data);

			$this->i++;
		}

		return $this->detailArray;
	}

	function getAction() {
		return htmlspecialchars($this->action);
	}

	function getIP() {
		return $this->ip;
	}

	function getRowStyle( $action ){
		/*
		Warnings (Administrative functions, errors)
		Background: Pink
		Foreground: Black
		*/
		$warningArray = Array(
			'ACCESS_DENIED',
			'LOGIN_FAIL_BAD_PASSWORD',
			'LOGIN_FAIL_UNKNOWN_USER',
			'LOGIN_RATE_LIMIT_REACHED',
			'MYSQL_ERROR',
			'USER_ADD',
			'USER_DELETE',
			'USER_EDIT');

		/*
		Normal entries (UTM's, Supervisors)
		Background: Light Green
		Foreground: Black
		*/
		$generalArray = Array(
			'LOGIN_SUCCESS',
			'LOGOUT_SUCCESS',
			'USER_EDIT_PROFILE',
			'USER_PASSWORD_RESET',
			'USER_REGISTER'
			);

		$cautionArray = Array(
			'ROUTE_ERROR'
		);

		if(in_array($action,$warningArray)) {
			$style = "background-color:Pink;";
		}
		elseif(in_array($action,$generalArray)) {
			$style = "background-color:LightGreen;";
		}
		elseif(in_array($action,$cautionArray)) {
			$style = "background-color:LightBlue;";
		}
		else {
			$style = "background-color:White";
		}

		return $style;
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

	function loadEntry($uuid) {
		$this->stmt = $this->logDB->prepare('SELECT uuid, timestamp, action, userUUID, ip FROM systemLog WHERE uuid = ?');
		$this->stmt->bind_param("s",$uuid);
		$this->stmt->execute();

		/* bind result variables */
		$this->stmt->bind_result($logUUID, $timestamp, $action, $userUUID, $ip);

		/* fetch values */
		while ($this->stmt->fetch()) {
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
		$this->stmt = $this->logDB->prepare('INSERT INTO systemLog (uuid, timestamp, userUUID, action, ip) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
		$this->stmt->bind_param('sssss', $this->uuid, $this->timestamp, $this->userUUID, $this->action, $this->ip);
		if(!$this->stmt->execute()) {
			printf("Error Message: %s\n<br />", $this->stmt->error);
			printf("UUID: %s Timestamp: %s userUUID: %s Action: %s IP: %s", $this->uuid, $this->timestamp, $this->userUUID, $this->action, $this->ip);
			return false;
		}

		if(isset($this->detailArray) && !empty($this->detailArray)) {
			$this->stmt = $this->logDB->prepare('INSERT INTO systemLogData (uuid, logUUID, dataType, data) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
			$this->detailCount = count($this->detailArray);

			for($this->i=0;$this->i < $this->detailCount; $this->i++)
			{
				if(!$this->stmt->bind_param('ssss', $this->detailArray[$this->i]['uuid'], $this->uuid, $this->detailArray[$this->i]['type'], $this->detailArray[$this->i]['data'])) {
					printf("Error Message: %s\n", $this->stmt->error);
					return false;
				}

				if(!$this->stmt->execute()) {
					printf("Error Message: %s\n", $this->stmt->error);
					return false;
				}
			}
		}

		$this->cleanEntry();
		$this->regenerateUUID();

		return true;
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
	}

	function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
		return true;
	}

	function setUserUUID($userUUID) {
		$this->userUUID = htmlspecialchars_decode($userUUID);
		return true;
	}

	function __destruct() {
		parent::__destruct();
	}
}
?>