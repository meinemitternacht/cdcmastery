<?php
$ajaxRoute = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$cdcMastery->verifyAdmin()){
    $log->setAction("ACCESS_DENIED");
    $log->setDetail("Page","ajax/admin");
    $log->setDetail("AJAX Route",$ajaxRoute);
    $log->saveEntry();
}

if($ajaxRoute){
    if($ajaxRoute == "viewIncompleteTest"){
        include BASE_PATH . "/app/ajax/admin/viewIncompleteTest.php";
    }
}