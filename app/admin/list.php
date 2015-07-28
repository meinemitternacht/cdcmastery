<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/21/15
 * Time: 12:23 AM
 */

if($_SESSION['vars'][0] == "users"){
    if($_SESSION['vars'][1] == "group"){
        $linkBaseURL = "admin/users";
        require BASE_PATH . "/includes/modules/admin/user/userGroupList.inc.php";
    }
}