<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 7/8/16
 * Time: 3:18 AM
 */

require '../includes/bootstrap.inc.php';

$router = new CDCMastery\Router($systemLog,$systemMessages);

if($router->showTheme)
    include BASE_PATH . '/theme/header.inc.php';

include $router->outputPage;

if($router->showTheme)
    include BASE_PATH . '/theme/footer.inc.php';

$router->__destruct();
$app->ApplicationShutdown();