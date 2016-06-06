<?php
if(!empty($_SESSION['userUUID'])) {
	$log->setAction("LOGOUT_SUCCESS");
	$log->setUserUUID($_SESSION['userUUID']);
	$log->saveEntry();
}

session_destroy();
$cdcMastery->redirect("/");