<?php
/**
 * All times should be UTC unless overridden by a user's time zone
 */
date_default_timezone_set("UTC");

/**
 * Load classes
 */
require 'pageTitles.inc.php';

require __DIR__ . '/../includes/classes/CDCMastery.class.php';
require __DIR__ . '/../includes/classes/UserManager.class.php';
require __DIR__ . '/../includes/classes/AFSCManager.class.php';
require __DIR__ . '/../includes/classes/AuthenticationManager.class.php';
require __DIR__ . '/../includes/classes/AnswerManager.class.php';
require __DIR__ . '/../includes/classes/AssociationManager.class.php';
require __DIR__ . '/../includes/classes/BaseManager.class.php';
require __DIR__ . '/../includes/classes/configurationManager.class.php';
require __DIR__ . '/../includes/classes/ZebraSessions.class.php';
require __DIR__ . '/../includes/classes/EmailQueueManager.class.php';
require __DIR__ . '/../includes/classes/SystemLog.class.php';
require __DIR__ . '/../includes/classes/SystemLogFilter.class.php';
require __DIR__ . '/../includes/classes/OfficeSymbolManager.class.php';
require __DIR__ . '/../includes/classes/QuestionManager.class.php';
require __DIR__ . '/../includes/classes/RoleManager.class.php';
require __DIR__ . '/../includes/classes/Router.class.php';
require __DIR__ . '/../includes/classes/TestManager.class.php';
require __DIR__ . '/../includes/classes/UserStatisticsModule.class.php';
require __DIR__ . '/../includes/classes/UserPasswordResetManager.class.php';
require __DIR__ . '/../includes/classes/UserActivationManager.class.php';
require __DIR__ . '/../includes/classes/UserAuthorizationQueueManager.class.php';
require __DIR__ . '/../includes/classes/SystemMessageManager.class.php';
require __DIR__ . '/../includes/classes/TrainingManagerOverview.class.php';
require __DIR__ . '/../includes/classes/SupervisorOverview.class.php';
require __DIR__ . '/../includes/classes/SearchModule.class.php';
require __DIR__ . '/../includes/classes/StatisticsModule.class.php';
require __DIR__ . '/../includes/classes/FlashCardManager.class.php';
require __DIR__ . '/../includes/classes/TestGenerator.class.php';

/**
 * PHP Mail functions (PEAR)
 */
require 'Mail.php';
require 'Mail/mime.php';

/**
 * Set configuration parameters
 */
require 'config.inc.php';

$db = new mysqli($configurationManager->getDatabaseConfiguration('host'), 
                 $configurationManager->getDatabaseConfiguration('username'),
                 $configurationManager->getDatabaseConfiguration('password'),
                 $configurationManager->getDatabaseConfiguration('name'),
                 $configurationManager->getDatabaseConfiguration('port'),
                 $configurationManager->getDatabaseConfiguration('socket'));

/**
 * Redirect to error page if database connection fails
 */
if($db->connect_errno){
    http_response_code(500);
	include __DIR__ . '../app/errors/dbError.php';
	exit();
}

/**
 * Load Memcache
 */
$memcache = new Memcache();
$memcache->connect($configurationManager->getMemcachedConfiguration('host'),$configurationManager->getMemcachedConfiguration('port'));

define('ENCRYPTION_KEY', $configurationManager->getEncryptionKey());

/**
 * Instantiate base classes
 */
$cdcMastery = new CDCMastery();
$zebraSession = new ZebraSessions($db,"92304j8j8fjsdsn923enkc");
$systemMessages = new SystemMessageManager();
$systemLog = new SystemLog($db);
$emailQueue = new EmailQueueManager($db, 
                                    $systemLog, 
                                    $configurationManager->getMailServerConfiguration('host'),
                                    $configurationManager->getMailServerConfiguration('port'),
                                    $configurationManager->getMailServerConfiguration('username'),
                                    $configurationManager->getMailServerConfiguration('password'));
$roleManager = new RoleManager($db, $systemLog, $emailQueue);
$baseManager = new BaseManager($db, $systemLog);
$afscManager = new AFSCManager($db, $systemLog);
$userManager = new UserManager($db, $systemLog, $emailQueue);
$officeSymbolManager = new OfficeSymbolManager($db, $systemLog);
$userStatistics = new UserStatisticsModule($db, $systemLog, $roleManager, $memcache);
$associationManager = new AssociationManager($db, $systemLog, $userManager, $afscManager, $emailQueue);

if(isset($_SESSION['userUUID']) && !empty($_SESSION['userUUID'])){
    /**
     * Something went very wrong, and if the application cannot load the user, let's just destroy the session and start over
     */
	if(!$userManager->loadUser($_SESSION['userUUID'])){
        $zebraSession->destroy(session_id());
        $cdcMastery->redirect("/auth/logout");
    }

    /**
     * This updates the user's latest recorded activity on each page request
     */
    $userManager->updateLastActiveTimestamp();

    /**
     * Ensure user statistics module is fetching stats for this user
     */
	$userStatistics->setUserUUID($_SESSION['userUUID']);
}