<?php
class logFilter extends log {
	protected $user;

	public $query;
	
	public $rowOffset;
	public $pageRows;
	public $sortBy;
	public $sortDirection;
	
	public $filterAction;
	public $filterIP;
	public $filterTimestampStart;
	public $filterTimestampEnd;
	public $filterUserUUID;
	
	function __construct(mysqli $db, user $user){
		$this->user = $user;
		
		parent::__construct($db);
	}
	
	function countEntries(){
		$query = "SELECT COUNT(*) AS count FROM systemLog";
		
		$whereArray = Array();
		
		if(!empty($this->filterAction))
			$whereArray[] = "action LIKE '%" . $this->db->real_escape_string($this->filterAction) . "%'";
		
		if(!empty($this->filterIP))
			$whereArray[] = "ip LIKE '%" . $this->db->real_escape_string($this->filterIP) . "%'";
		
		if(!empty($this->filterTimestampStart) && !empty($this->filterTimestampEnd))
			$whereArray[] = "timestamp BETWEEN '" . $this->db->real_escape_string($this->filterTimestampStart) . "' AND '" . $this->db->real_escape_string($this->filterTimestampEnd) ."'";
		
		if(!empty($this->filterUserUUID) && $this->user->verifyUser($this->filterUserUUID))
			$whereArray[] = "userUUID LIKE '%" . $this->filterUserUUID . "%'";
		
		if(!empty($whereArray)){
			if(count($whereArray) > 1){
				$query .= " WHERE " . implode(" AND ",$whereArray);
			}
			else{
				$query .= " WHERE " . $whereArray[0];
			}
		}
		
		$res = $this->db->query($query);
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$tmpCount = $row['count'];
			}
			
			if(isset($tmpCount) && !empty($tmpCount)){
				return $tmpCount;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	function listEntries(){
		$sortDirection = strtoupper($this->sortDirection);
		$sortBy = strtolower($this->sortBy);
	
		$validColumns = Array(	0 => 'uuid',
				1 => 'timestamp',
				2 => 'action',
				3 => 'useruuid',
				4 => 'ip');
	
		$validDirections = Array(	0 => 'ASC',
				1 => 'DESC');
	
		/*
		 * Sanitize sort by variable
		 */
		if(!in_array($sortBy,$validColumns)){
			$sortBy = "timestamp";
		}
	
		/*
		 * Sanitize sort direction variable
		 */
		if(!in_array($sortDirection,$validDirections)){
			$sortDirection = "DESC";
		}
		
		/*
		 * Build WHERE clause
		 */
		$whereArray = Array();
		
		if(!empty($this->filterAction))
			$whereArray[] = "action LIKE '%" . $this->db->real_escape_string($this->filterAction) . "%'";
		
		if(!empty($this->filterIP))
			$whereArray[] = "ip LIKE '%" . $this->db->real_escape_string($this->filterIP) . "%'";
		
		if(!empty($this->filterTimestampStart) && !empty($this->filterTimestampEnd))
			$whereArray[] = "timestamp BETWEEN '" . $this->db->real_escape_string($this->filterTimestampStart) . "' AND '" . $this->db->real_escape_string($this->filterTimestampEnd) ."'";
		
		if(!empty($this->filterUserUUID) && $this->user->verifyUser($this->filterUserUUID))
			$whereArray[] = "userUUID LIKE '%" . $this->filterUserUUID . "%'";
		
		if(!empty($whereArray)){
			if(count($whereArray) > 1){
				$whereQuery = "WHERE " . implode(" AND ",$whereArray);
			}
			else{
				$whereQuery = "WHERE " . $whereArray[0];
			}
		}
		
		/*
		 * If we are sorting by user, we need to join the userData table (just the userLastName column)
		 */
		if(strtolower($sortBy) == "useruuid"){
			$query = "SELECT systemLog.uuid, timestamp, action, userUUID, ip FROM systemLog LEFT JOIN userData ON userData.uuid=systemLog.userUUID ";
			
			if(isset($whereQuery))
				$query .= $whereQuery;
			
			$query .= " ORDER BY userData.userLastName " . $sortDirection . ", userData.userFirstName " . $sortDirection . ", userData.userRank " . $sortDirection . ", timestamp, microtime DESC  LIMIT ?, ?";
		}
		else{
			$query = "SELECT uuid, timestamp, action, userUUID, ip FROM systemLog ";
			
			if(isset($whereQuery))
				$query .= $whereQuery;

			if($sortBy == "timestamp"){
				$query .= " ORDER BY " .$sortBy . " " . $sortDirection . ", microtime DESC LIMIT ?, ?";
			}
			else{
				$query .= " ORDER BY " .$sortBy . " " . $sortDirection . " LIMIT ?, ?";
			}
		}

		$this->query = $query;
	
		$stmt = $this->db->prepare($query);
		$stmt->bind_param("ii",$this->rowOffset,$this->pageRows);
	
	
		if($stmt->execute()){
			$stmt->bind_result($uuid, $timestamp, $action, $userUUID, $ip);
				
			while($stmt->fetch()){
				$returnArray[$uuid]['timestamp'] = $timestamp;
				$returnArray[$uuid]['action'] = $action;
				$returnArray[$uuid]['userUUID'] = $userUUID;
				$returnArray[$uuid]['ip'] = $ip;
			}
				
			if(isset($returnArray) && !empty($returnArray)){
				return $returnArray;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	
	function getRowOffset(){
		return $this->rowOffset;
	}
	
	function getPageRows(){
		return $this->pageRows;
	}
	
	function getSortBy(){
		return $this->sortBy;
	}
	
	function getSortDirection(){
		return $this->sortDirection;
	}
	
	function getFilterAction(){
		return $this->filterAction;
	}
	
	function getFilterIP(){
		return $this->filterIP;
	}
	
	function getFilterTimestampStart(){
		return $this->filterTimestampStart;
	}
	
	function getFilterTimestampEnd(){
		return $this->filterTimestampEnd;
	}
	
	function getFilterUserUUID(){
		return $this->filterUserUUID;
	}
	
	function setRowOffset($rowOffset){
		if(is_int($rowOffset)){
			$this->rowOffset = $rowOffset;
		}
		else{
			$this->rowOffset = 0;
		}

		return true;
	}
	
	function setPageRows($pageRows){
		if(is_int($pageRows)){
			$this->pageRows = $pageRows;
		}
		else{
			$this->pageRows = 15;
		}

		return true;
	}
	
	function setSortBy($sortBy){
		$this->sortBy = $sortBy;
		return true;
	}
	
	function setSortDirection($sortDirection){
		$this->sortDirection = $sortDirection;
		return true;
	}
	
	function setFilterAction($filterAction){
		$this->filterAction = $filterAction;
		return true;
	}
	
	function setFilterIP($filterIP){
		$this->filterIP = $filterIP;
		return true;
	}
	
	function setFilterTimestampStart($filterTimestampStart){
		$this->filterTimestampStart = $filterTimestampStart;
		return true;
	}
	
	function setFilterTimestampEnd($filterTimestampEnd){
		$this->filterTimestampEnd = $filterTimestampEnd;
		return true;
	}
	
	function setFilterUserUUID($filterUserUUID){
		$this->filterUserUUID = $filterUserUUID;
		return true;
	}
	
	function __destruct(){
		parent::__destruct();
	}
}