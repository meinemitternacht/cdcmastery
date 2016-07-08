<?php
require 'Mail.php'; /* PHP Mail functions (PEAR) */
require 'Mail/mime.php'; /* PHP Mail functions (PEAR) */

define('BASE_PATH', realpath(__DIR__) . '/../');
define('APP_BASE', realpath(__DIR__ . '/../app'));

require BASE_PATH . '/includes/classes/CDCMastery/AutoLoader.class.php';

$autoLoader = new CDCMastery\PSR4AutoLoader();
$autoLoader->addNamespace("CDCMastery",__DIR__ . '/classes/CDCMastery');
$autoLoader->register();

$app = new CDCMastery\Application();

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
 *
 */
$memcache = new Memcache(); /* Load Memcache */
$memcache->connect($configurationManager->getMemcachedConfiguration('host'),$configurationManager->getMemcachedConfiguration('port'));

define('ENCRYPTION_KEY', $configurationManager->getEncryptionKey());

/**
 * Instantiate base classes
 */
$cdcMastery = new CDCMastery\CDCMastery();
$zebraSession = new CDCMastery\ZebraSessions($db,"92304j8j8fjsdsn923enkc");
$systemMessages = new CDCMastery\SystemMessageManager();
$systemLog = new CDCMastery\SystemLog($db);
$emailQueue = new CDCMastery\EmailQueueManager($db, 
                                    $systemLog, 
                                    $configurationManager->getMailServerConfiguration('host'),
                                    $configurationManager->getMailServerConfiguration('port'),
                                    $configurationManager->getMailServerConfiguration('username'),
                                    $configurationManager->getMailServerConfiguration('password'));
$roleManager = new CDCMastery\RoleManager($db, $systemLog, $emailQueue);
$baseManager = new CDCMastery\BaseManager($db, $systemLog);
$afscManager = new CDCMastery\AFSCManager($db, $systemLog);
$userManager = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$officeSymbolManager = new CDCMastery\OfficeSymbolManager($db, $systemLog);
$userStatistics = new CDCMastery\UserStatisticsModule($db, $systemLog, $roleManager, $memcache);
$associationManager = new CDCMastery\AssociationManager($db, $systemLog, $userManager, $afscManager, $emailQueue);

if(isset($_SESSION['userUUID']) && !empty($_SESSION['userUUID'])){
	if(!$userManager->loadUser($_SESSION['userUUID'])){ /* Something went very wrong, and if the application cannot load the user, let's just destroy the session and start over */
        $zebraSession->destroy(session_id());
        $cdcMastery->redirect("/auth/logout");
    }

    $userManager->updateLastActiveTimestamp(); /* This updates the user's latest recorded activity on each page request */
	$userStatistics->setUserUUID($_SESSION['userUUID']); /* Ensure user statistics module is fetching stats for this user */
}

$router = new CDCMastery\Router($systemLog,$systemMessages);

if($router->showTheme)
    include BASE_PATH . '/theme/header.inc.php';

include $router->outputPage;

if($router->showTheme)
    include BASE_PATH . '/theme/footer.inc.php';

$router->__destruct();
$app->ApplicationShutdown();