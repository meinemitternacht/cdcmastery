<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 6/3/2016
 * Time: 4:32 AM
 */

/*
 * This file tests database relationships, specifically ensuring that deleting a user row will delete all related
 * records for the user in the rest of the database.
 */

$roleObj = new roles($db,$log,$emailQueue);
$officeSymbolObj = new officeSymbol($db,$log);
$userStatisticsObj = new userStatistics($db,$log,$roleObj,$memcache);
$afscObj = new afsc($db,$log);
$userObj = new user($db,$log,$emailQueue);
$associationsObj = new associations($db,$log,$userObj,$afscObj,$emailQueue);

$userObj->setUserFirstName("Sample");
$userObj->setUserLastName("User");
$userObj->setUserHandle($userObj->genUUID());
$userObj->setUserPassword($cdcMastery->hashUserPassword($userObj->genUUID()));
$userObj->setUserEmail($userObj->genUUID()."@testing.dev");
$userObj->setUserRank("AB");
$userObj->setUserDateRegistered("2016-01-01 00:00:00");
$userObj->setUserLastLogin("2016-01-01 00:00:00");
$userObj->setUserTimeZone("America/New_York");
$userObj->setUserRole($roleObj->getRoleUUIDByName("Users"));
$userObj->setUserOfficeSymbol($officeSymbolObj->getOfficeSymbolByName("MXACW"));
$userObj->setUserBase("4b7483b1-0c48-4902-a356-ea6dad85c5ba");
$userObj->setUserDisabled(false);
$userObj->saveUser();

$associationsObj->addPendingAFSCAssociation($userObj->getUUID(),$afscObj->getAFSCUUIDByName("2W151B"));
$associationsObj->addAFSCAssociation($userObj->getUUID(),$afscObj->getAFSCUUIDByName("2W151A"));
$associationsObj->addSupervisorAssociation($userObj->getUUIDByHandle("sample.supervisor"),$userObj->getUUID());
$associationsObj->addTrainingManagerAssociation($userObj->getUUIDByHandle("unit.training.manager"),$userObj->getUUID());

$log->setAction("TEST_ACTION");
$log->setUserUUID($userObj->getUUID());
$log->saveEntry();