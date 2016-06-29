<?php

class router extends CDCMastery
{
	protected $log;
	
	public $error;
	public $errorNumber;
	public $filePath;
	public $outputPage;
	public $request;
	public $route;
	public $showTheme;
	public $siteSection;
	public $errorMessage;

    public $publicRoutes = ['index','about','auth','register','errors','ajax/registration'];
	
	public function __construct(log $log){
		$this->showTheme = true;
		$this->log = $log;
	}

	/**
	 * @param $path
	 * @return bool
	 *
	 * Determines if the path should be classified as an administration path
	 */
	public function checkAdminPath($path){
		if((strpos($path, "/admin/") !== false) || (strpos($path, "/dev/") !== false)){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @param $path
	 * @return bool
	 */
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

	/**
	 * @return bool|string
	 *
	 * Return the site section, used by the header menu to highlight links
	 */
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

	/**
	 * @return bool
	 *
	 * Parse the path and create session variables
	 */
	public function parseURI(){
		if(isset($_SERVER['REQUEST_URI'])){
			$this->request = $_SERVER['REQUEST_URI'];

			/**
			 * Make sure the request is more than just "/" before trying to match a trailing slash.  After matching,
			 * trim the trailing slash to process further (does not redirect to non-trailing slash)
			 */
            if(strlen($this->request) > 1 && preg_match("/\/$/",$this->request)){
                $this->request = substr($this->request, 0, -1);
            }

			/**
			 * Process and store GET variables
			 */
            if(strpos($this->request,"?") !== false){
				/**
				 * Split the path from the variables
				 */
                $tmpRequest = explode("?",$this->request);
                $this->request = $tmpRequest[0];

				/**
				 * See if there is more than one GET variable
				 */
                if(strpos($tmpRequest[1],"&") !== false){
					/**
					 * Create array of GET variables
					 */
                    $getVarsArray = explode("&",$tmpRequest[1]);
                    foreach($getVarsArray as $getVar){
                        $getVarArray = explode("=",$getVar);
                        $getVarKey = $getVarArray[0];
                        $getVarVal = $getVarArray[1];
						/**
						 * Store variables in the session
						 */
                        $_SESSION['vars']['get'][$getVarKey] = $getVarVal;
                    }
                }
                else {
					/**
					 * Only one GET variable
					 */
                    $getVarArray = explode("=",$tmpRequest[1]);
                    $getVarKey = $getVarArray[0];
					/**
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
			/**
			 * If the path ends with .php, we want to include that file.  Trim the leading "/" and store the filename
			 */
			elseif(preg_match("/\.php$/",$this->request)){
				$this->route = substr($this->request, 1);
			}
			/**
			 * If the path contains "/", create an array of the values after splitting the path.  The filesystem contains
			 * files based on <project root>/app/<path part 1>/<path part 2>.php
			 *
			 * Additional values (greater than 2) will be stored in the session later to be used as sub-module routes
			 * (e.g., /admin/users/delete, where delete is a sub-module
			 */
			elseif(strpos(substr($this->request, 1),"/") !== false){
				$requestArray = explode('/',substr($this->request,1));
				$requestCount = count($requestArray);
		
				if(!isset($requestArray[1]))
					$requestArray[1] = "index";

				/**
				 * Our main route is the first two elements of the path
				 */
				$this->route = $requestArray[0]."/".$requestArray[1];
			}
			else{
				/**
				 * Remove the leading "/" and store the route
				 */
				$this->route = substr($this->request, 1);
			}
		}
		else{
			/**
			 * Default route should be the front page
			 */
			$this->route = "index";
		}

		/**
		 * The application home page route is index
		 */
		if($this->route == "index"){
			$this->filePath = APP_BASE . "/index.php";
		}
		/**
		 * No more than two values were in the path
		 */
		elseif(!isset($requestArray)){
			if(preg_match("/\.php$/",$this->route)){
				$this->filePath = APP_BASE . "/" . $this->route;
			}
			else{
				$this->filePath = APP_BASE . "/" . $this->route . "/index.php";
			}
		}
		/**
		 * More than two values were in the path, so we need to store those in the session
		 */
		elseif(isset($requestArray)){
			if(isset($requestCount) && $requestCount > 2){
				for($i=0;$i<($requestCount - 2);$i++){
					$_SESSION['vars'][$i] = $requestArray[($i + 2)];
				}
			}

			/**
			 * If the request ends with .php, we can include it directly, otherwise we need to append ".php" to
			 * hopefully match it with a file on the filesystem
			 */
			if(preg_match("/\.php$/",$this->request)){
				$this->filePath = APP_BASE . "/" . $this->route;
			}
			else{
				$this->filePath = APP_BASE . "/" . $this->route . ".php";
			}
		}

		/**
		 * Make sure request, route, and filepath are set before continuing
		 */
		if(!empty($this->request) && !empty($this->route) && !empty($this->filePath)){
			return true;
		}
		else{
			$this->log->setAction("ROUTING_ERROR");
			$this->log->setDetail("REQUEST_URI",$_SERVER['REQUEST_URI']);

			foreach($_SESSION as $sessionKey => $sessionVar){
				if(is_array($sessionVar)){
					$this->log->setDetail($sessionKey,implode(",",$sessionVar));
				}
				else{
					$this->log->setDetail($sessionKey,$sessionVar);
				}
			}

			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @return bool
	 *
	 * After processing $this->parseURI(), figure out what to do with the file
	 */
	public function verifyFilePath(){
		if(isset($this->filePath)) {
			/**
			 * Check route against public routes. If the route is not in the array, and the user is not logged in,
			 * redirect them to the login page.
			 */
			if(!$this->loggedIn() && !in_array($this->getSiteSection(),$this->publicRoutes)){
				$this->log->setAction("ROUTING_ERROR");
				$this->log->setDetail("Route",$this->getRoute());
				$this->log->setDetail("Error","Session Expired or not logged in");

				foreach($_SESSION as $sessionKey => $sessionVar){
					if(is_array($sessionVar)){
						$this->log->setDetail($sessionKey,serialize($sessionVar));
					}
					else{
						$this->log->setDetail($sessionKey,$sessionVar);
					}
				}

				$this->log->saveEntry();

				$this->errorNumber = 403;
				$this->error = "We're sorry, but either you have not logged in or your session has expired.  Please log in or register to continue.";
				return false;
			}
			/**
			 * Check if this is an admin path, and that an admin or training manager is logged in.  If not, set error number to 403
			 * and return false, letting /index.php know to redirect to the access denied page
			 */
			elseif($this->checkAdminPath($this->filePath) && !$this->verifyAdmin() && !$this->verifyTrainingManager()){
				$this->log->setAction("ACCESS_DENIED");
				$this->log->setDetail("Route",$this->getRoute());
				$this->log->saveEntry();

				$this->errorNumber = 403;
				return false;
			}
			/**
			 * Check if this is a training manager path, and that an admin or training manager is logged in.  If not, set error number to 403
			 * and return false, letting /index.php know to redirect to the access denied page
			 */
			elseif(strpos($this->filePath, "/training/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager()){
				$this->log->setAction("ACCESS_DENIED");
				$this->log->setDetail("Route",$this->getRoute());
				$this->log->setDetail("Rule","verifyTrainingManager, verifyAdmin");
				$this->log->saveEntry();

				$this->errorNumber = 403;
				return false;
			}
			/**
			 * Check if this is a supervisor path, and that an admin, training manager, or supervisor is logged in.  If not, set error number to 403
			 * and return false, letting /index.php know to redirect to the access denied page
			 */
			elseif(strpos($this->filePath, "/supervisor/") !== false && !$this->verifyAdmin() && !$this->verifyTrainingManager() && !$this->verifySupervisor()){
				$this->log->setAction("ACCESS_DENIED");
				$this->log->setDetail("Route",$this->getRoute());
				$this->log->setDetail("Rule","verifySupervisor, verifyTrainingManager, verifyAdmin");
				$this->log->saveEntry();

				$this->errorNumber = 403;
				return false;
			}
			/**
			 * If this request is for an AJAX script, don't show the theme, it will seriously fubar the output
			 */
			elseif(strpos($this->filePath, "/ajax/") !== false){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			/**
			 * Same goes for export.  We don't want to show the theme.
			 */
			elseif(strpos($this->filePath, "/export/") !== false){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			/**
			 * And for printer friendly pages, no theme output
			 */
			elseif(isset($_SESSION['vars'][0]) && $_SESSION['vars'][0] == "print"){
				$this->outputPage = $this->filePath;
				$this->showTheme = false;
				return true;
			}
			else{
				/**
				 * If we are not ajax'ing, exporting, or printing, we can continue.  First, check if the filePath we
				 * computed actually exists.  If it doesn't, and we can see a trailing "/" on the filepath, we need to
				 * append "index.php"
				 */
				if(!file_exists($this->filePath) && preg_match("/\/$/",$this->filePath)){
					if(file_exists($this->filePath . "index.php")) {
						$this->outputPage = $this->filePath . "index.php";
						return true;
					}
					else{
						$this->log->setAction("ROUTING_ERROR");
						$this->log->setDetail("Route",$this->getRoute());
						$this->log->setDetail("File Path",$this->filePath);
						$this->log->saveEntry();

						/**
						 * No way to recover, we need to tell them we could not find what they were looking for.
						 */
						$this->errorNumber = 404;
						return false;
					}
				}
				/**
				 * If the file does not exist, and there is no trailing slash, there is nothing we can do.
				 */
				elseif(!file_exists($this->filePath)){
					$this->log->setAction("ROUTING_ERROR");
					$this->log->setDetail("Route",$this->getRoute());
					$this->log->setDetail("File Path",$this->filePath);
					$this->log->saveEntry();

					$this->errorNumber = 404;
					return false;
				}
				/**
				 * If all of the other checks have passed, we can set the outputPage to the filePath and return true.
				 * This variable will be included dynamically on the calling script page (/index.php)
				 */
				else{
					$this->outputPage = $this->filePath;
					return true;
				}
			}
		}
		else{
			/**
			 * How did we get this far without a filePath?  No matter, just tell them it doesn't exist.  Who cares if they 
			 * didn't ask for it first?
			 */
			$this->log->setAction("ROUTING_ERROR");
			$this->log->setDetail("Route",$this->getRoute());
			$this->log->setDetail("File Path","NONE");
			$this->log->saveEntry();

			$this->errorNumber = 404;
			return false;
		}
	}
	
	public function __destruct(){
		/**
		 * To prevent vars from bleeding across requests, we need to unset them here.  This also happens to be the last thing
		 * that gets processed before the application terminates.
		 */
		unset($_SESSION['vars']);
		session_write_close();
		parent::__destruct();
	}
}