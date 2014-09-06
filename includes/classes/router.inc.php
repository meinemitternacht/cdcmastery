<?php

class router extends CDCMastery
{
	public $error;
	public $errorNumber;
	public $filePath;
	public $outputPage;
	public $request;
	public $route;
	
	public function __construct(){
		
	}
	
	public function checkAdminPath($path){
		if(strpos($path, "/admin/") !== false){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function checkFileExists($path){
		if(file_exists($path)){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function parseURI(){
		if(isset($_SERVER['REQUEST_URI'])){
			$this->request = $_SERVER['REQUEST_URI'];
			
			if($this->request != "/index.php" && strpos($this->request,".php") !== false){
				$this->route = substr($this->request, 1);
			}
			elseif($this->request == "/" || $this->request == "/index.php"){
				$this->route = "index";
			}
			elseif(strpos($this->request,"/") !== false){
				$requestArray = explode('/',$this->request);
				$requestCount = count($requestArray);
		
				if(!isset($requestArray[2]))
					$requestArray[2] = "index";
		
				$this->route = $requestArray[1]."/".$requestArray[2];
			}
			else{
				$this->route = $this->request;
			}
		}
		else{
			$this->route = "index";
		}
		
		if($this->route == "index"){
			$this->filePath = APP_BASE . "/index.php";
		}
		elseif(!isset($requestArray)){
			if(strpos($this->route,".php")){
				$this->filePath = APP_BASE . "/" . $this->route;
			}
			else{
				$this->filePath = APP_BASE . "/" . $this->route . ".php";
			}
		}
		elseif(isset($requestArray)){
			if($requestCount > 3){
				for($i=0;$i<($requestCount - 3);$i++){
					$_SESSION['vars'][$i] = $requestArray[($i + 3)];
				}
			}
		
			if(preg_match("/\/$/",$request)){
				$this->filePath = APP_BASE . $this->request . "index.php";
			}
			else{
				$this->filePath = APP_BASE . "/" . $this->route . ".php";
			}
		}
		
		if(!empty($this->request) && !empty($this->route) && !empty($this->filePath)){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function verifyFilePath(){
		if(isset($this->filePath)) {
			if(strpos($this->filePath, "/admin/") !== false && !$this->verifyAdmin()) {
				$this->outputPage = APP_BASE . "/app/errors/403.php";
				$this->errorNumber = 403;
				return false;
			}
			elseif(strpos($this->filePath, "/export/") !== false){
				$this->outputPage = $this->filePath;
				return true;
			}
			else {
				if(!file_exists($this->filePath) && preg_match("/\/$/",$this->filePath)) {
					if(file_exists($this->filePath . "/index.php")) {
						$this->outputPage = $this->filePath . "/index.php";
						return true;
					}
					else {
						$this->outputPage = APP_BASE . "/app/errors/404.php";
						$this->errorNumber = 404;
						return false;
					}
				}
				else {
					$this->outputPage = $this->filePath;
					return true;
				}
			}
		}
		else {
			$this->outputPage = APP_BASE . "/app/errors/404.php";
			$this->errorNumber = 404;
			return false;
		}
	}
	
	public function __destruct(){
		unset($_SESSION['vars']);
	}
}