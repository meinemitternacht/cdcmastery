<?php
/*
 * CDCMastery.com
 */
ob_start();

$time_start = microtime(true);
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
header('Access-Control-Allow-Origin: *');

define('BASE_PATH', realpath(__DIR__));
define('APP_BASE', realpath(__DIR__ . '/app'));

$maintenanceMode = false;
if($maintenanceMode == true){
	include APP_BASE . '/errors/maintenance.php';
	exit();
}

require BASE_PATH . '/includes/bootstrap.inc.php';

$router = new router();

if($router->parseURI()){
	if(!$router->verifyFilePath()){
		if(isset($router->error) && !empty($router->error)){
			$sysMsg->addMessage($router->error);
		}

		if($router->route == "ajax/testPlatform"){
			$_SESSION['nextPage'] = $_SERVER['HTTP_REFERER'];
		}
		else {
			$_SESSION['nextPage'] = $router->request;
		}

		echo '<META http-equiv="refresh" content="0;URL=https://cdcmastery.com/auth/login">';
		exit();
	}

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

ob_end_flush();
$router->__destruct();