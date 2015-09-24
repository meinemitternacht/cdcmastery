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

    public $publicRoutes = ['index','about','auth','register','contact','errors'];
	
	public function __construct(){
		$this->showTheme = true;
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

	public function getRoute(){
		return $this->route;
	}
	
	public function getSiteSection(){
		if(empty($this->route))
			return false;
		
		if(strpos($this->route,"/") !== false){
			$routeArray = explode("/",$this->route);

            if($routeArray[1] == "register"){
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
                    $_SESSION['vars']['get'] = explode("&",$tmpRequest[1]);
                }
                else {
                    $_SESSION['vars']['get'] = $tmpRequest[1];
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
			if(strpos($this->filePath, "/admin/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager()){
				$this->outputPage = APP_BASE . "/errors/403.php";
				$this->errorNumber = 403;
				return false;
			}
            elseif(!$this->loggedIn() && !in_array($this->getSiteSection(),$this->publicRoutes)){
                $this->outputPage = APP_BASE . "/errors/403.php";
                $this->errorNumber = 403;
                return false;
            }
			elseif(strpos($this->filePath, "/export/") !== false){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			elseif(strpos($this->filePath, "/ajax/") !== false){
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
						$this->errorNumber = 404;
						return false;
					}
				}
				elseif(!file_exists($this->filePath)){
					$this->outputPage = APP_BASE . "/errors/404.php";
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
		parent::__destruct();
	}
}