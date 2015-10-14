<?php
date_default_timezone_set("UTC");

require BASE_PATH . '/includes/config.inc.php';
require BASE_PATH . '/includes/classes/cdcmastery.inc.php';

require BASE_PATH . '/includes/classes/user.inc.php';

require BASE_PATH . '/includes/classes/afsc.inc.php';
require BASE_PATH . '/includes/classes/auth.inc.php';
require BASE_PATH . '/includes/classes/answerManager.inc.php';
require BASE_PATH . '/includes/classes/associations.inc.php';
require BASE_PATH . '/includes/classes/bases.inc.php';
require BASE_PATH . '/includes/classes/zebraSessions.php';
require BASE_PATH . '/includes/classes/emailQueue.inc.php';
require BASE_PATH . '/includes/classes/log.inc.php';
require BASE_PATH . '/includes/classes/logFilter.inc.php';
require BASE_PATH . '/includes/classes/officeSymbol.inc.php';
require BASE_PATH . '/includes/classes/questionManager.inc.php';
require BASE_PATH . '/includes/classes/roles.inc.php';
require BASE_PATH . '/includes/classes/router.inc.php';
require BASE_PATH . '/includes/classes/testManager.inc.php';
require BASE_PATH . '/includes/classes/userStatistics.inc.php';
require BASE_PATH . '/includes/classes/user/passwordReset.inc.php';
require BASE_PATH . '/includes/classes/user/userActivation.inc.php';
require BASE_PATH . '/includes/classes/user/userAuthorizationQueue.inc.php';

require BASE_PATH . '/includes/classes/systemMessages.inc.php';
require BASE_PATH . '/includes/classes/overviews/trainingManagerOverview.inc.php';
require BASE_PATH . '/includes/classes/overviews/supervisorOverview.inc.php';
require BASE_PATH . '/includes/classes/search.inc.php';
require BASE_PATH . '/includes/classes/statistics.inc.php';
require BASE_PATH . '/includes/classes/flashCardManager.inc.php';

require 'Mail.php';
require 'Mail/mime.php';

$db = new mysqli(   $cfg['db']['host'],
                    $cfg['db']['user'],
                    $cfg['db']['pass'],
                    $cfg['db']['name'],
                    $cfg['db']['port']);

if($db->connect_errno){
    http_response_code(500);
	include APP_BASE . '/errors/dbError.php';
	exit();
}

$cdcMastery = new CDCMastery();
$session = new Zebra_Session($db,"92304j8j8fjsdsn923enkc");
$sysMsg = new systemMessages();
$log = new log($db);

$emailQueue = new emailQueue($db, $log, $cfg['smtp']['host'],
                                        $cfg['smtp']['port'],
                                        $cfg['smtp']['user'],
                                        $cfg['smtp']['pass']);

$roles = new roles($db, $log, $emailQueue);
$bases = new bases($db, $log);
$afsc = new afsc($db, $log);
$user = new user($db, $log, $emailQueue);
$officeSymbol = new officeSymbol($db, $log);
$userStatistics = new userStatistics($db, $log, $roles);
$assoc = new associations($db, $log, $user, $afsc, $emailQueue);

if(isset($_SESSION['userUUID']) && !empty($_SESSION['userUUID'])){
	if(!$user->loadUser($_SESSION['userUUID'])){
        session_destroy();
        $cdcMastery->redirect("/auth/logout");
    }
    $user->updateLastActiveTimestamp();
	$userStatistics->setUserUUID($_SESSION['userUUID']);
}