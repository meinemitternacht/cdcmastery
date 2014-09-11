<?php
date_default_timezone_set("UTC");

require BASE_PATH . '/includes/config.inc.php';
require BASE_PATH . '/includes/classes/cdcmastery.inc.php';
require BASE_PATH . '/includes/classes/router.inc.php';
require BASE_PATH . '/includes/classes/log.inc.php';
require BASE_PATH . '/includes/classes/dbSession.inc.php';
require BASE_PATH . '/includes/classes/user.inc.php';
require BASE_PATH . '/includes/classes/auth.inc.php';
require BASE_PATH . '/includes/classes/roles.inc.php';
require BASE_PATH . '/includes/classes/bases.inc.php';
require BASE_PATH . '/includes/classes/officeSymbol.inc.php';

$db = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], $cfg['db']['name'], $cfg['db']['port'], $cfg['db']['socket']);

if($db->connect_errno){
	include APP_BASE . '/error/dbError.php';
	exit();
}

$cdcMastery = new CDCMastery();
$session = new dbSession($db);
$log = new log($db);
$roles = new roles($db, $log);
$bases = new bases($db, $log);
$user = new user($log, $db);
$officeSymbol = new officeSymbol($db, $log);

if(isset($_SESSION['userUUID']) && !empty($_SESSION['userUUID']))
	$user->loadUser($_SESSION['userUUID']);