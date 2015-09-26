<?php
$ajaxRoute = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($ajaxRoute){
    if($ajaxRoute == "checkEmail"){
        include BASE_PATH . "/app/ajax/registration/checkEmail.php";
    }
    elseif($ajaxRoute == "checkHandle"){
        include BASE_PATH . "/app/ajax/registration/checkHandle.php";
    }
}