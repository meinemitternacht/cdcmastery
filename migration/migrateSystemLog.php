<?php
echo "\n";
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$workingLog = new log($db);
$testManager = new testManager($db, $log, $afsc);
$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

function getUserHandle($userID, $oldDBObject){
	$stmt = $oldDBObject->prepare("SELECT username FROM users WHERE id = ?");
	$stmt->bind_param("s",$userID);
	
	if($stmt->execute()){
		$stmt->bind_result($username);
		
		while($stmt->fetch()){
			$ret = $username;
		}
		
		if(isset($ret)){
			return $ret;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}
}

$res = $oldDB->query("SELECT 	`log`.`u_id`, 
								`log`.`action`, 
								`log`.`data`, 
								`log`.`data2`, 
								`log`.`timestamp`, 
								`log`.`ip`, 
								`users`.`username` AS logEnteredBy 
						FROM log 
						LEFT JOIN `users` ON `users`.`id`=`log`.`u_id` 
						ORDER BY action ASC");
$migrationArrayCount = $res->num_rows;
$assocArray = Array();

echo "Migrating log entries...\n";

if($migrationArrayCount > 0){
	$buildTotal=1;
	while($row = $res->fetch_assoc()){
		$skip = false;
		switch($row['action']){
			case "ADDED_QUESTION":
				$workingLog->setAction("QUESTION_ADD");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("Question UUID",$row['data']);
			break;
			case "ADD_AFSC":
				$workingLog->setAction("AFSC_ADD");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("AFSC Name",$row['data']);
			break;
			case "AUTH_ERROR":
				$workingLog->setAction("ERROR_LOGIN_BAD_PASSWORD");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "AUTH_ERROR_UNKNOWN":
				$workingLog->setAction("ERROR_LOGIN_UNKNOWN_USER");
				$workingLog->setUserUUID("ANONYMOUS");
				$workingLog->setDetail("Provided User Handle",$row['data']);
			break;
			case "COMPLETED_TEST":
				$workingLog->setAction("TEST_COMPLETED");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$testUUID = $testManager->getMigratedTestUUID($row['data']);
				if($testUUID){
					$workingLog->setDetail("Test UUID",$testUUID);
				}
			break;
			case "DELETED_QUESTION":
				$workingLog->setAction("QUESTION_DELETE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "DELETED_TEST":
				$workingLog->setAction("TEST_DELETE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$testUUID = $testManager->getMigratedTestUUID($row['data']);
				if($testUUID){
					$workingLog->setDetail("Test UUID",$testUUID);
				}
			break;
			case "DELETED_USER":
				$workingLog->setAction("USER_DELETE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("User Full Name",$row['data']);
			break;
			case "DELETE_AFSC":
				$workingLog->setAction("AFSC_DELETE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("AFSC Name",$row['data']);
			break;
			case "EDITED_QUESTION":
				$workingLog->setAction("QUESTION_EDIT");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("Question UUID",$row['data']);
			break;
			case "EDIT_AFSC":
				$workingLog->setAction("AFSC_EDIT");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "EDIT_PROFILE":
				$workingLog->setAction("USER_EDIT_PROFILE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("Target User",$row['data']);
				$workingLog->setDetail("Query String",$row['data2']);
			break;
			case "FOUO_NOT_AUTH":
				$workingLog->setAction("ERROR_TEST_UNAUTHORIZED");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "LOGGED_IN":
				$workingLog->setAction("LOGIN_SUCCESS");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "LOGGED_OUT":
				$workingLog->setAction("LOGOUT_SUCCESS");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "LOGIN_RATE_LIMIT_REACHED":
				$workingLog->setAction("ERROR_LOGIN_RATE_LIMIT_REACHED");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "MYSQL_ERROR":
				$workingLog->setAction("MYSQL_ERROR");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$workingLog->setDetail("MYSQL ERROR",$row['data']);
			break;
			case "PASSWORD_CHANGE":
				$workingLog->setAction("USER_EDIT_PROFILE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "PASSWORD_RESET":
				$workingLog->setAction("USER_PASSWORD_RESET");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$userHandle = getUserHandle($row['data'],$oldDB);
				if($userHandle){
					$userUUID = $user->getUUIDByHandle($userHandle);
					if($userUUID){
						$user->loadUser($userUUID);
						$workingLog->setDetail("Target User UUID", $userUUID);
						$workingLog->setDetail("Target User Name", $user->getFullName());
					}
				}
			break;
			case "REGISTERED":
				$workingLog->setAction("USER_REGISTER");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "STARTED_TEST":
				$workingLog->setAction("TEST_START");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
				$testUUID = $testManager->getMigratedTestUUID($row['data']);
				if($testUUID){
					$workingLog->setDetail("Test UUID",$testUUID);
				}
			break;
			case "UPDATE_BASE":
				$workingLog->setAction("USER_EDIT_PROFILE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "UPDATE_TIME_ZONE":
				$workingLog->setAction("USER_EDIT_PROFILE");
				$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
			break;
			case "UPLOAD_FILE":
				$workingLog->setAction("FILE_UPLOAD");
				$workingLog->setDetail("File Name", $row['data']);
				
				if(!empty($row['data2'])){
					$workingLog->setDetail("Description", $row['data2']);
				}
			break;
			case "USER_AFSC_ASSOC":
				$workingLog->setAction("USER_ADD_AFSC_ASSOCIATION");
				if(empty($row['data2'])){
					$workingLog->setUserUUID("SYSTEM");
					$afscUUID = $afsc->getMigratedAFSCUUID($row['data']);
						
					if($afscUUID){
						$workingLog->setDetail("AFSC UUID",$afscUUID);
						$workingLog->setDetail("AFSC Name",$afsc->getAFSCName($afscUUID));
					}
					
					$workingLog->setDetail("Target User",$row['logEnteredBy']);
				}
				else{
					$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
					$afscUUID = $afsc->getMigratedAFSCUUID($row['data2']);
					
					if($afscUUID){
						$workingLog->setDetail("AFSC UUID",$afscUUID);
						$workingLog->setDetail("AFSC Name",$afsc->getAFSCName($afscUUID));
					}
					
					$userHandle = getUserHandle($row['data'],$oldDB);
					if($userHandle){
						$userUUID = $user->getUUIDByHandle($userHandle);
						if($userUUID){
							$user->loadUser($userUUID);
							$workingLog->setDetail("Target User UUID", $userUUID);
							$workingLog->setDetail("Target User Name", $user->getFullName());
						}
					}
				}
			break;
			case "USER_AFSC_UNASSOC":
				$workingLog->setAction("USER_DELETE_AFSC_ASSOCIATION");
				if(empty($row['data2'])){
					$workingLog->setUserUUID("SYSTEM");
					$afscUUID = $afsc->getMigratedAFSCUUID($row['data']);
						
					if($afscUUID){
						$workingLog->setDetail("AFSC UUID",$afscUUID);
						$workingLog->setDetail("AFSC Name",$afsc->getAFSCName($afscUUID));
					}
					
					$workingLog->setDetail("Target User",$row['logEnteredBy']);
				}
				else{
					$workingLog->setUserUUID($user->getUUIDByHandle($row['logEnteredBy']));
					$afscUUID = $afsc->getMigratedAFSCUUID($row['data2']);
					
					if($afscUUID){
						$workingLog->setDetail("AFSC UUID",$afscUUID);
						$workingLog->setDetail("AFSC Name",$afsc->getAFSCName($afscUUID));
					}
					
					$userHandle = getUserHandle($row['data'],$oldDB);
					if($userHandle){
						$userUUID = $user->getUUIDByHandle($userHandle);
						if($userUUID){
							$user->loadUser($userUUID);
							$workingLog->setDetail("Target User UUID", $userUUID);
							$workingLog->setDetail("Target User Name", $user->getFullName());
						}
					}
				}
			break;
			default:
				$skip = true;
			break;
		}
		
		if(!$skip){
			$workingLog->setIP($row['ip']);
			$workingLog->setTimestamp($row['timestamp']);
			
			if($workingLog->userUUID != false){
				$workingLog->saveEntry();
			}
			
			$workingLog->cleanEntry();
			$workingLog->regenerateUUID();
		}
		
		if(!empty($workingLog->error)){
			foreach($workingLog->error as $errorMessage){
				$outputLine[] = $errorMessage;
			}
		}
		
		$percentDone = intval(($buildTotal / $migrationArrayCount) * 100);
		
		echo ($buildTotal), "/$migrationArrayCount ($percentDone %)\r";
		$buildTotal++;
	}
	
	$res->close();
	
	echo "\nMigration complete.\n";
	
	if(!empty($outputArray)){
		echo "Results:\n";
		foreach($outputArray as $outputLine){
			echo $outputLine."\n";
		}
	}
}
else{
	echo "No log entries found.";
}