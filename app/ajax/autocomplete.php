<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/8/2015
 * Time: 11:03 AM
 */

$ajaxRoute = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($ajaxRoute){
    if($ajaxRoute == "userEmail"){
        include BASE_PATH . "/app/ajax/autocomplete/userEmail.php";
    }
    elseif($ajaxRoute == "userHandle"){
        include BASE_PATH . "/app/ajax/autocomplete/userHandle.php";
    }
    elseif($ajaxRoute == "userFirstName"){
        include BASE_PATH . "/app/ajax/autocomplete/userFirstName.php";
    }
    elseif($ajaxRoute == "userLastName"){
        include BASE_PATH . "/app/ajax/autocomplete/userLastName.php";
    }
}