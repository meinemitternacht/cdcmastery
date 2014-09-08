<?php
if(!empty($_SESSION['userUUID'])) {
	$log->setAction("LOGOUT_SUCCESS");
	$log->setUserID($_SESSION['userUUID']);
	$log->saveEntry();
}

session_destroy();
header('Location: /');
?>