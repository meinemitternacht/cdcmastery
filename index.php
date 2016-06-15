<?php
/**
 * Start output buffering in case we want to redirect
 */
ob_start();

$time_start = microtime(true);
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
header('Access-Control-Allow-Origin: *');

/**
 * Define application constants
 */
define('BASE_PATH', realpath(__DIR__));
define('APP_BASE', realpath(__DIR__ . '/app'));

/**
 * Maintenance mode short-circuit
 */
$maintenanceMode = false;
if($maintenanceMode == true){
	include APP_BASE . '/errors/maintenance.php';
	exit();
}

/**
 * Start the application
 */
require BASE_PATH . '/includes/bootstrap.inc.php';

$router = new router($log);

/**
 * Parse the URI passed from the web server
 */
if($router->parseURI()){
	/**
	 * Ensure the file path is valid, and that the page exists.  Additionally, this function checks permissions for
	 * protected areas of the site (/admin, etc)
	 */
	if(!$router->verifyFilePath()){
		if(isset($router->error) && !empty($router->error)){
			$sysMsg->addMessage($router->error,"danger");
		}

		if($router->route == "ajax/testPlatform"){
			$_SESSION['nextPage'] = $_SERVER['HTTP_REFERER'];
		}
		else {
			/**
			 * After logging in, redirect user to where they attempted to go before
			 */
			$_SESSION['nextPage'] = $router->request;
		}

		if(!empty($router->errorNumber)) {
			$cdcMastery->redirect("/errors/" . $router->errorNumber);
		}
		else {
			echo '<META http-equiv="refresh" content="0;URL=https://cdcmastery.com/auth/login">';
			exit();
		}
	}

	/**
	 * Handle HTTP error codes and redirect as required
	 */
	if($router->route == "errors/403"){
        http_response_code(403);
    }
    elseif($router->route == "errors/404"){
        http_response_code(404);
    }
    elseif($router->route == "errors/500"){
        http_response_code(500);
    }
    elseif($router->route == "errors/dbError"){
        http_response_code(500);
    }
	
	if($router->showTheme)
		include BASE_PATH . '/theme/header.inc.php';
	
	include $router->outputPage;
	
	if($router->showTheme)
		include BASE_PATH . '/theme/footer.inc.php';
}

/**
 * After processing everything, flush the output buffer and destroy the router
 */
ob_end_flush();
$router->__destruct();