<?php

class router extends CDCMastery
{
	public $error;
	public $errorNumber;
	public $filePath;
	public $outputPage;
	public $request;
	public $route;
	public $showTheme;
	public $siteSection;
	public $errorMessage;

    public $publicRoutes = ['index','about','auth','register','contact','errors','ajax/registration'];
	
	public function __construct(){
		$this->showTheme = true;
	}
	
	public function checkAdminPath($path){
		if((strpos($path, "/admin/") !== false) || (strpos($path, "/dev/") !== false)){
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

	public function getRoute(){
		return $this->route;
	}
	
	public function getSiteSection(){
		if(empty($this->route))
			return false;
		
		if(strpos($this->route,"/") !== false){
			$routeArray = explode("/",$this->route);

            if($routeArray[1] == "register" || $routeArray[1] == "registration"){
                return "register";
            }
            else{
                return $routeArray[0];
            }
		}
		else{
			return $this->route;
		}
	}
	
	public function parseURI(){
		if(isset($_SERVER['REQUEST_URI'])){
			$this->request = $_SERVER['REQUEST_URI'];

            if(strlen($this->request) > 1 && preg_match("/\/$/",$this->request)){
                $this->request = substr($this->request, 0, -1);
            }

            if(strpos($this->request,"?") !== false){
                $tmpRequest = explode("?",$this->request);
                $this->request = $tmpRequest[0];

                if(strpos($tmpRequest[1],"&") !== false){
                    $getVarsArray = explode("&",$tmpRequest[1]);
                    foreach($getVarsArray as $getVar){
                        $getVarArray = explode("=",$getVar);
                        $getVarKey = $getVarArray[0];
                        $getVarVal = $getVarArray[1];

                        $_SESSION['vars']['get'][$getVarKey] = $getVarVal;
                    }
                }
                else {
                    $getVarArray = explode("=",$tmpRequest[1]);
                    $getVarKey = $getVarArray[0];
					/*
					 * Special case for vars that do not set a value
					 */
					if(isset($getVarArray[1])) {
						$getVarVal = $getVarArray[1];
					}
					else{
						$getVarVal = false;
					}

                    $_SESSION['vars']['get'][$getVarKey] = $getVarVal;
                }
            }
			
			if($this->request == "/" || $this->request == "/index.php"){
				$this->route = "index";
			}
			elseif(preg_match("/\.php$/",$this->request)){
				$this->route = substr($this->request, 1);
			}
			elseif(strpos(substr($this->request, 1),"/") !== false){
				$requestArray = explode('/',substr($this->request,1));
				$requestCount = count($requestArray);
		
				if(!isset($requestArray[1]))
					$requestArray[1] = "index";
		
				$this->route = $requestArray[0]."/".$requestArray[1];
			}
			else{
				$this->route = substr($this->request, 1);
			}
		}
		else{
			$this->route = "index";
		}
		
		if($this->route == "index"){
			$this->filePath = APP_BASE . "/index.php";
		}
		elseif(!isset($requestArray)){
			if(preg_match("/\.php$/",$this->route)){
				$this->filePath = APP_BASE . "/" . $this->route;
			}
			else{
				$this->filePath = APP_BASE . "/" . $this->route . "/index.php";
			}
		}
		elseif(isset($requestArray)){
			if($requestCount > 2){
				for($i=0;$i<($requestCount - 2);$i++){
					$_SESSION['vars'][$i] = $requestArray[($i + 2)];
				}
			}
		
			if(preg_match("/\/$/",$this->request)){
				$this->filePath = APP_BASE . $this->request . "index.php";
			}
			elseif(preg_match("/\.php$/",$this->request)){
				$this->filePath = APP_BASE . "/" . $this->route;
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

			if(!$this->loggedIn() && !in_array($this->getSiteSection(),$this->publicRoutes)){
				$this->outputPage = APP_BASE . "/auth/login.php";
				$this->errorNumber = 403;
				$this->error = "We're sorry, but either you have not logged in or your session has expired.  Please log in or register to continue.";
				return false;
			}
			elseif(strpos($this->filePath, "/admin/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager()){
				$this->outputPage = APP_BASE . "/errors/403.php";
				$this->errorNumber = 403;
				return false;
			}
			elseif(strpos($this->filePath, "/training/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager()){
				$this->outputPage = APP_BASE . "/errors/403.php";
				$this->errorNumber = 403;
				return false;
			}
			elseif(strpos($this->filePath, "/supervisor/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager() && !$this->verifySupervisor()){
				$this->outputPage = APP_BASE . "/errors/403.php";
				$this->errorNumber = 403;
				return false;
			}
			elseif(strpos($this->filePath, "/ajax/") !== false){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			elseif(strpos($this->filePath, "/export/") !== false){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			elseif(isset($_SESSION['vars'][0]) && $_SESSION['vars'][0] == "print"){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			else{
				if(!file_exists($this->filePath) && preg_match("/\/$/",$this->filePath)){
					if(file_exists($this->filePath . "/index.php")) {
						$this->outputPage = $this->filePath . "/index.php";
						return true;
					}
					else{
						$this->outputPage = APP_BASE . "/errors/404.php";
                        $this->error = "That page does not exist.";
						$this->errorNumber = 404;
						return false;
					}
				}
				elseif(!file_exists($this->filePath)){
					$this->outputPage = APP_BASE . "/errors/404.php";
                    $this->error = "That page does not exist.";
					$this->errorNumber = 404;
					return false;
				}
				else{
					$this->outputPage = $this->filePath;
					return true;
				}
			}
		}
		else{
			$this->outputPage = APP_BASE . "/errors/404.php";
			$this->errorNumber = 404;
			return false;
		}
	}
	
	public function __destruct(){
		unset($_SESSION['vars']);
		session_write_close();
		parent::__destruct();
	}
}