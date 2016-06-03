<?php

/*
This script provides a class interface for the site logging function.
*/

class log extends CDCMastery
{
	protected $db;				//holds database object
	
	public $error;				//error messages (array)

	public $uuid;				//uuid of the log entry
	public $timestamp;			//timestamp of the log entry
	public $microtime;			//microtime double value
	public $action;				//log entry action
	public $userUUID;			//uuid of the user
	public $ip;					//ip of the user

	public $uuidDetail;			//uuid of the log detail
	public $typeDetail;			//log detail data type
	public $dataDetail;			//log detail data

	public $detailArray;		//array of log details
	public $detailCount;		//count of detail array

	function __construct(mysqli $db) {
		$this->db = $db;
		$this->uuid = parent::genUUID();

		$this->microtime = microtime(true);
		
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
		$this->microtime		= microtime(true);
		$this->action			= NULL;
		$this->userUUID			= NULL;
		$this->ip				= NULL;
		$this->uuidDetail		= NULL;
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

    function clearLogEntries($userUUID,$disableLog = false){
        $stmt = $this->db->prepare("DELETE FROM systemLog WHERE userUUID = ?");
        $stmt->bind_param("s",$userUUID);

        if(!$stmt->execute()){
            $this->setAction("ERROR_USER_LOG_CLEAR");
            $this->setDetail("MySQL Error",$stmt->error);
            $this->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->setDetail("User UUID",$userUUID);
            $this->saveEntry();

            $stmt->close();

            return false;
        }
        else{
			if(!$disableLog){
				$this->setAction("USER_LOG_CLEAR");
				$this->setDetail("User UUID",$userUUID);
				$this->setDetail("Affected Rows",$stmt->affected_rows);
				$this->saveEntry();
			}

            $stmt->close();

            return true;
        }
    }

	function fetchDetails($uuid){
		$this->detailArray = Array();

		$stmt = $this->db->prepare('SELECT uuid, dataType, data FROM systemLogData WHERE logUUID = ? ORDER BY dataType ASC');
		$stmt->bind_param("s",$uuid);
		$stmt->execute();
		
		$stmt->bind_result($detailUUID, $dataType, $data);
		
		$i = 0;
		while ($stmt->fetch()) {
			$this->detailArray[$i]['uuid'] = $detailUUID;
			$this->detailArray[$i]['dataType'] = htmlspecialchars($dataType);
			if($dataType != "AFSC ARRAY") {
				$this->detailArray[$i]['data'] = htmlspecialchars($data);
			}
			else{
				$this->detailArray[$i]['data'] = $data;
			}

			$i++;
		}

		if(!empty($this->detailArray)){
			return $this->detailArray;
		}
		else{
			return false;
		}
	}

	function loadEntry($uuid) {
		$stmt = $this->db->prepare('SELECT uuid, timestamp, microtime, action, userUUID, ip FROM systemLog WHERE uuid = ?');
		$stmt->bind_param("s",$uuid);
		$stmt->execute();

		$stmt->bind_result($logUUID, $timestamp, $microtime, $action, $userUUID, $ip);

		while($stmt->fetch()) {
			$this->uuid = $logUUID;
			$this->timestamp = $timestamp;
			$this->microtime = $microtime;
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
		$string = "UUID: " . $this->uuid . " Timestamp: " . $this->timestamp . " Microtime: " . $this->microtime . " Action: " . $this->action . " User ID: " . $this->userUUID . " IP: " . $this->ip;

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
		if(!empty($this->timestamp)) {
			$stmt = $this->db->prepare('INSERT INTO systemLog (uuid, timestamp, microtime, userUUID, action, ip) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
			$stmt->bind_param('ssdsss', $this->uuid, $this->timestamp, $this->microtime, $this->userUUID, $this->action, $this->ip);
		}
		else{
			$stmt = $this->db->prepare('INSERT INTO systemLog (uuid, timestamp, microtime, userUUID, action, ip) VALUES (?, UTC_TIMESTAMP, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE uuid = VALUES(uuid)');
			$stmt->bind_param('sdsss', $this->uuid, $this->microtime, $this->userUUID, $this->action, $this->ip);
		}

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

	function listLogActions(){
		$stmt = $this->db->prepare("SELECT DISTINCT(action) AS logAction FROM systemLog");

		if($stmt->execute()){
			$stmt->bind_result($logAction);

			while($stmt->fetch()){
				$logActionList[] = $logAction;
			}

			$stmt->close();

			if(isset($logActionList) && !empty($logActionList)){
				return $logActionList;
			}
			else{
				return false;
			}
		}
		else{
			return false;
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
		$this->typeDetail = htmlspecialchars_decode($type);

		if(is_array($data)) {
			$this->dataDetail = implode(",",$data);
			$this->dataDetail = htmlspecialchars_decode($this->dataDetail);
		}
		else {
			$this->dataDetail = htmlspecialchars_decode($data);
		}

		$this->detailArray[] = Array(	"uuid" => $this->uuidDetail,
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
		$this->userUUID = $userUUID;
		return true;
	}

	function getAction() {
		return htmlspecialchars($this->action);
	}

	function getIP() {
		return $this->ip;
	}

	function getMicrotime(){
		return $this->microtime;
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
			'AJAX_ACTION_ERROR',
			'AJAX_DIRECT_ACCESS',
			'APPROVE_ROLE_AUTHORIZATION',
			'BASE_DELETE',
			'DATABASE_ERROR',
			'ERROR_AFSC_ADD',
			'ERROR_AFSC_EDIT',
			'ERROR_AFSC_LOAD',
			'ERROR_AFSC_SAVE',
			'ERROR_AFSC_VERIFY',
			'ERROR_AJAX_CHECK_USER_HANDLE',
			'ERROR_AJAX_CHECK_EMAIL',
			'ERROR_ANSWER_ARCHIVE',
			'ERROR_ANSWER_DELETE',
			'ERROR_ANSWERS_LIST',
			'ERROR_ANSWERS_LOAD',
			'ERROR_ANSWERS_SAVE',
			'ERROR_APPROVE_ROLE_AUTHORIZATION',
			'ERROR_ASSOCIATIONS_AFSC_ADD',
			'ERROR_ASSOCIATIONS_AFSC_DELETE',
			'ERROR_ASSOCIATIONS_AFSC_LIST_PENDING',
			'ERROR_ASSOCIATIONS_LIST_USERS_BY_AFSC',
			'ERROR_BASE_ADD',
			'ERROR_BASE_EDIT',
			'ERROR_BASE_DELETE',
			'ERROR_BASE_LOAD',
			'ERROR_BASE_SAVE',
			'ERROR_EMAIL_QUEUE_ADD',
			'ERROR_EMAIL_QUEUE_PROCESS',
			'ERROR_EMAIL_QUEUE_REMOVE',
			'ERROR_EMAIL_SEND',
			'ERROR_FILE_UPLOAD',
			'ERROR_FLASH_CARD_CATEGORY_DELETE',
			'ERROR_FLASH_CARD_CATEGORY_LIST',
			'ERROR_FLASH_CARD_CATEGORY_LOAD',
			'ERROR_FLASH_CARD_CATEGORY_SAVE',
			'ERROR_FLASH_CARD_COUNT_CARDS',
			'ERROR_FLASH_CARD_DATA_DELETE',
			'ERROR_FLASH_CARD_DELETE',
			'ERROR_FLASH_CARD_LIST',
			'ERROR_FLASH_CARD_LOAD',
			'ERROR_FLASH_CARD_SAVE',
			'ERROR_INCOMPLETE_TEST_DELETE',
			'ERROR_INCOMPLETE_TEST_SAVE',
			'ERROR_LOGIN_BAD_PASSWORD',
			'ERROR_LOGIN_EMPTY_PASSWORD',
			'ERROR_LOGIN_EMPTY_USER',
			'ERROR_LOGIN_EMPTY_USERNAME',
			'ERROR_LOGIN_INVALID_PASSWORD',
			'ERROR_LOGIN_RATE_LIMIT_REACHED',
			'ERROR_LOGIN_UNACTIVATED_ACCOUNT',
			'ERROR_LOGIN_UNKNOWN_USER',
			'ERROR_LOGIN_USER_DISABLED',
			'ERROR_MIGRATE_AFSC_ASSOCIATIONS',
			'ERROR_MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE',
			'ERROR_NOTIFY_PASSWORD_CHANGED',
			'ERROR_NOTIFY_AFSC_ASSOCIATION_APPROVAL',
			'ERROR_NOTIFY_PENDING_AFSC_ASSOCIATION',
			'ERROR_NOTIFY_ROLE_APPROVAL',
			'ERROR_NOTIFY_ROLE_REJECTION',
			'ERROR_OFFICE_SYMBOL_ADD',
			'ERROR_OFFICE_SYMBOL_DELETE',
			'ERROR_OFFICE_SYMBOL_EDIT',
			'ERROR_OFFICE_SYMBOL_LOAD',
			'ERROR_OFFICE_SYMBOL_SAVE',
			'ERROR_QUESTION_ARCHIVE',
			'ERROR_QUESTION_DELETE',
			'ERROR_QUESTION_DELETE_MULTIPLE',
			'ERROR_QUESTION_LIST',
			'ERROR_QUESTION_LOAD',
			'ERROR_QUESTION_QUERY_FOUO',
			'ERROR_QUESTION_SAVE',
			'ERROR_QUEUE_ROLE_AUTHORIZATION',
			'ERROR_REJECT_ROLE_AUTHORIZATION',
			'ERROR_ROLE_EDIT',
			'ERROR_ROLE_GET_UUID',
			'ERROR_ROLE_LIST_USERS',
			'ERROR_ROLE_MIGRATE',
			'ERROR_ROLE_SAVE',
			'ERROR_SAVE_TEST',
			'ERROR_SCORE_TEST',
			'ERROR_SET_ADD',
			'ERROR_SET_DELETE',
			'ERROR_SET_EDIT',
			'ERROR_TEST_AJAX_ANSWER_EMPTY',
			'ERROR_TEST_ARCHIVE',
			'ERROR_TEST_DATA_DELETE',
			'ERROR_TEST_DELETE',
			'ERROR_TEST_POPULATE_QUESTIONS',
			'ERROR_TEST_RESUME',
			'ERROR_TEST_RETRIEVE_PREVIOUS_ANSWER',
			'ERROR_TEST_STORE_ANSWER',
			'ERROR_TEST_UNAUTHORIZED',
			'ERROR_TEST_USER_UUID_NOT_EQUAL',
			'ERROR_TOGGLE_AFSC_FOUO',
			'ERROR_UPDATE_LAST_ACTIVE',
			'ERROR_USER_ACTIVATE',
			'ERROR_USER_ADD_SUPERVISOR_ASSOCIATION',
			'ERROR_USER_ADD_TRAINING_MANAGER_ASSOCIATION',
			'ERROR_USER_APPROVE_PENDING_AFSC_ASSOCIATION',
			'ERROR_USER_BAN',
			'ERROR_USER_BAN_NOT_AUTH',
			'ERROR_USER_DELETE',
			'ERROR_USER_DELETE_AFSC_ASSOCIATIONS_ALL',
			'ERROR_USER_DELETE_NOT_AUTH',
			'ERROR_USER_DELETE_PASSWORD_RESET_TOKEN',
			'ERROR_USER_DELETE_PROCESS',
			'ERROR_USER_EDIT',
			'ERROR_USER_LIST_TESTS',
			'ERROR_USER_LIST_INCOMPLETE_TESTS',
			'ERROR_USER_PASSWORD_RESET',
			'ERROR_USER_QUEUE_ACTIVATION',
			'ERROR_USER_REGISTER',
			'ERROR_USER_REMOVE_SUPERVISOR_ASSOCIATION',
			'ERROR_USER_REMOVE_SUPERVISOR_ASSOCIATIONS_ALL',
			'ERROR_USER_REMOVE_TRAINING_MANAGER_ASSOCIATION',
			'ERROR_USER_REMOVE_TRAINING_MANAGER_ASSOCIATIONS_ALL',
			'ERROR_USER_SAVE',
			'ERROR_USER_UNBAN',
			'ERROR_USER_UPDATE_LAST_LOGIN',
			'ERROR_USER_RESOLVE_NAMES',
			'ERROR_USER_VERIFY',
			'ERROR_VOLUME_ADD',
			'ERROR_VOLUME_DELETE',
			'ERROR_VOLUME_EDIT',
			'NOTIFY_ROLE_REJECTION',
			'OFFICE_SYMBOL_DELETE',
			'QUESTION_ARCHIVE_COMPLETE',
			'QUESTION_DELETE',
			'QUESTION_DELETE_MULTIPLE',
			'QUEUE_ROLE_AUTHORIZATION',
			'REJECT_ROLE_AUTHORIZATION',
			'ROLE_EDIT',
			'ROLE_MIGRATE',
			'ROUTING_ERROR',
			'SET_DELETE',
			'TOGGLE_AFSC_FOUO',
			'USER_ACCOUNT_DISABLE_SELF',
			'USER_BAN',
			'USER_DELETE',
			'USER_DELETE_PROCESS_COMPLETE',
			'USER_EDIT',
			'USER_LOG_CLEAR',
			'USER_PASSWORD_RESET',
			'USER_REMOVE_SUPERVISOR_ASSOCIATION',
			'USER_REMOVE_SUPERVISOR_ASSOCIATIONS_ALL',
			'USER_REMOVE_TRAINING_MANAGER_ASSOCIATION',
			'USER_REMOVE_TRAINING_MANAGER_ASSOCIATIONS_ALL',
			'USER_UNBAN',
			'VOLUME_DELETE'
		);

		/*
		 * Informational entries
		 * .text-caution
		 */
		$cautionArray = Array(
			'FILE_UPLOAD',
			'FLASH_CARD_CATEGORY_DELETE',
			'FLASH_CARD_CATEGORY_EDIT',
			'FLASH_CARD_DATA_DELETE',
			'INCOMPLETE_TEST_DELETE',
			'INCOMPLETE_TEST_DELETE_ALL',
			'OFFICE_SYMBOL_EDIT',
			'QUESTION_EDIT',
			'ROLE_SAVE',
			'SET_EDIT',
			'TEST_ARCHIVE',
			'TEST_DATA_DELETE',
			'TEST_DELETE',
			'USER_ADD',
			'USER_ADD_PENDING_AFSC_ASSOCIATION',
			'USER_APPROVE_PENDING_AFSC_ASSOCIATION',
			'USER_DELETE_AFSC_ASSOCIATION',
			'USER_DELETE_AFSC_ASSOCIATIONS_ALL',
			'USER_EDIT_PROFILE',
			'VOLUME_EDIT'
		);

		/*
		 * Normal entries
		 * .text-success
		 */
		$generalArray = Array(
			'AFSC_ADD',
			'BASE_ADD',
			'BASE_EDIT',
			'EMAIL_QUEUE_ADD',
			'EMAIL_SEND',
			'FLASH_CARD_ADD',
			'FLASH_CARD_CATEGORY_ADD',
			'LOGIN_SUCCESS',
			'LOGOUT_SUCCESS',
			'MIGRATE_AFSC_ASSOCIATIONS',
			'MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE',
			'MIGRATED_PASSWORD',
			'NEW_FLASH_CARD_SESSION',
			'NOTIFY_PASSWORD_CHANGED',
			'NOTIFY_PENDING_AFSC_ASSOCIATION',
			'NOTIFY_ROLE_APPROVAL',
			'OFFICE_SYMBOL_ADD',
			'QUESTION_ADD',
			'SAVE_TEST',
			'SCORE_TEST',
			'SET_ADD',
			'TEST_COMPLETED',
			'TEST_START',
			'USER_ACTIVATE',
			'USER_ADD_AFSC_ASSOCIATION',
			'USER_ADD_SUPERVISOR_ASSOCIATION',
			'USER_ADD_TRAINING_MANAGER_ASSOCIATION',
			'USER_PASSWORD_RESET_COMPLETE',
			'USER_QUEUE_ACTIVATION',
			'USER_REGISTER',
			'VOLUME_ADD'
		);

		if(in_array($actionName,$warningArray)){
			$class = "text-warning";
		}
		elseif(in_array($actionName,$cautionArray)){
			$class = "text-caution";
		}
		elseif(in_array($actionName,$generalArray)){
			$class = "text-success";
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
		if(preg_match("/ANONYMOUS/",$this->userUUID)){
			return "ANONYMOUS";
		}
		elseif(preg_match("/SYSTEM/",$this->userUUID)){
			return "SYSTEM";
		}
		else{
			return $this->userUUID;
		}
	}

	function __destruct() {
		parent::__destruct();
	}
}
?>