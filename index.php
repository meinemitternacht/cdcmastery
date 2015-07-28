<?php
/*
 * CDCMastery.com
 */
ob_start();

$time_start = microtime(true);

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);
header('Access-Control-Allow-Origin: *');

define('BASE_PATH', realpath(__DIR__));
define('APP_BASE', realpath(__DIR__ . '/app'));

require BASE_PATH . '/includes/bootstrap.inc.php';

$router = new router();

if($router->parseURI()){
	if(!$router->verifyFilePath()){
		$log->setAction("ROUTING_ERROR");
		$log->setDetail("ERROR NUMBER", $router->errorNumber);
		$log->setDetail("REQUEST", $router->request);
		$log->setDetail("PATH", $router->filePath);
		$log->setDetail("OUTPUT PAGE", $router->outputPage);
		$log->setDetail("ROUTE", $router->route);
		$log->saveEntry();
	}
	
	if($router->showTheme)
		include BASE_PATH . '/theme/header.inc.php';
	
	include $router->outputPage;
	
	if($router->showTheme)
		include BASE_PATH . '/theme/footer.inc.php';
}

ob_end_flush();
$router->__destruct();