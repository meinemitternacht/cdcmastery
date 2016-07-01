<?php
$ajaxRoute = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$cdcMastery->verifyAdmin()){
    $systemLog->setAction("ACCESS_DENIED");
    $systemLog->setDetail("Page", "ajax/admin");
    $systemLog->setDetail("AJAX Route", $ajaxRoute);
    $systemLog->saveEntry();
}

if($ajaxRoute){
    if($ajaxRoute == "viewIncompleteTest"){
        include BASE_PATH . "/app/ajax/admin/viewIncompleteTest.php";
    }
}