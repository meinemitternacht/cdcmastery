<?php
if(!empty($_SESSION['userUUID'])) {
	$systemLog->setAction("LOGOUT_SUCCESS");
	$systemLog->setUserUUID($_SESSION['userUUID']);
	$systemLog->saveEntry();
}

session_destroy();
$cdcMastery->redirect("/");