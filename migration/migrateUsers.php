<?php
echo "\n";
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('APP_BASE', realpath(__DIR__ . '/../app'));

include "../includes/bootstrap.inc.php";

$oldDB = new mysqli($cfg['db']['host'], $cfg['db']['user'], $cfg['db']['pass'], "cdcmastery_main", $cfg['db']['port'], $cfg['db']['socket']);

if($oldDB->connect_errno){
	echo $oldDB->error;
	exit();
}

$res = $oldDB->query("SELECT username, password, last_login, default_group, base_id, display_name, email, rank, first_name, last_name, register_date, disabled, time_zone, officeSymbol FROM users ORDER BY last_name ASC");

$userArray = Array();

if($res->num_rows > 0){
	while($row = $res->fetch_assoc()){
		$uuid = $cdcMastery->genUUID();
		$userArray[$uuid]['userFirstName'] = $row['first_name'];
		$userArray[$uuid]['userLastName'] = $row['last_name'];
		$userArray[$uuid]['userHandle'] = $row['username'];
		$userArray[$uuid]['userLegacyPassword'] = $row['password'];
		$userArray[$uuid]['userEmail'] = $row['email'];
		$userArray[$uuid]['userRank'] = $row['rank'];
		$userArray[$uuid]['userDateRegistered'] = $row['register_date'];
		$userArray[$uuid]['userLastLogin'] = $row['last_login'];
		$userArray[$uuid]['userTimeZone'] = $row['time_zone'];
		$userArray[$uuid]['userRole'] = $roleManager->getMigratedRoleUUID($row['default_group']);
		$userArray[$uuid]['userOfficeSymbol'] = $row['officeSymbol'];
		$userArray[$uuid]['userBase'] = $baseManager->getMigratedBaseUUID($row['base_id']);
		$userArray[$uuid]['userDisabled'] = $row['disabled'];
	}
	
	$arrayCount = count($userArray) + 1;
	
	$stmt = $db->prepare("INSERT INTO userData (uuid,
												userFirstName,
												userLastName,
												userHandle,
												userPassword,
												userLegacyPassword,
												userEmail,
												userRank,
												userDateRegistered,
												userLastLogin,
												userTimeZone,
												userRole,
												userOfficeSymbol,
												userBase,
												userDisabled)
										VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
										ON DUPLICATE KEY UPDATE
												uuid=VALUES(uuid),
												userFirstName=VALUES(userFirstName),
												userLastName=VALUES(userLastName),
												userHandle=VALUES(userHandle),
												userPassword=VALUES(userPassword),
												userLegacyPassword=VALUES(userLegacyPassword),
												userEmail=VALUES(userEmail),
												userRank=VALUES(userRank),
												userDateRegistered=VALUES(userDateRegistered),
												userLastLogin=VALUES(userLastLogin),
												userTimeZone=VALUES(userTimeZone),
												userRole=VALUES(userRole),
												userOfficeSymbol=VALUES(userOfficeSymbol),
												userBase=VALUES(userBase),
												userDisabled=VALUES(userDisabled)");
	
	$total = 1;
	$error = false;
	foreach($userArray as $key => $user){
		$nullVal = NULL;
		$stmt->bind_param("ssssssssssssssi", 	$key,
												$user['userFirstName'],
												$user['userLastName'],
												$user['userHandle'],
												$nullVal,
												$user['userLegacyPassword'],
												$user['userEmail'],
												$user['userRank'],
												$user['userDateRegistered'],
												$user['userLastLogin'],
												$user['userTimeZone'],
												$user['userRole'],
												$user['userOfficeSymbol'],
												$user['userBase'],
												$user['userDisabled']);
		
		if(!$stmt->execute()){
			echo "Error inserting user: ".$user['userFirstName']." ".$user['userLastName'].". MySQL error: ".$stmt->error."\n";
			$error = true;
		}
		else{
			$total++;
		}
		
	}
	
	$stmt->close();
	
	if($error){
		echo "Errors were encountered while migrating users. " . $total . "/" . $arrayCount . " users were processed.";
	}
	else{
		echo "Users migrated successfully. " . $total . "/" . $arrayCount . " users were processed.";
	}
}
else{
	echo "There are no users to migrate.  Uh oh!";
}

echo "\n";

$res->close();