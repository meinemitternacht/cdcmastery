<?php

namespace CDCMastery;
use mysqli;

/**
 * Class roles
 */
class RoleManager extends CDCMastery
{
    /**
     * @var mysqli
     */
    protected $db;
    /**
     * @var SystemLog
     */
    protected $log;
    /**
     * @var EmailQueueManager
     */
    protected $emailQueue;

    /**
     * @var
     */
    public $error;

    /**
     * @var
     */
    public $uuid;
    /**
     * @var
     */
    public $roleType;
    /**
     * @var
     */
    public $roleName;
    /**
     * @var
     */
    public $roleDescription;

    /**
     * @var
     */
    public $permissionArray;

    /**
     * @param mysqli $db
     * @param SystemLog $log
     * @param EmailQueueManager $emailQueue
     */
    public function __construct(mysqli $db, SystemLog $log, EmailQueueManager $emailQueue){
		$this->db = $db;
		$this->log = $log;
		$this->emailQueue = $emailQueue;
	}

    /**
     * @return array|bool
     */
    public function listRoles(){
		$res = $this->db->query("SELECT uuid, roleType, roleName, roleDescription FROM roleList ORDER BY roleName ASC");
		
		$roleArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$roleArray[$row['uuid']]['roleType'] = $row['roleType'];
				$roleArray[$row['uuid']]['roleName'] = $row['roleName'];
				$roleArray[$row['uuid']]['roleDescription'] = $row['roleDescription'];
			}
			
			$noResults = false;
		}
		else{
			$noResults = true;
		}
		
		$res->close();
		
		if($noResults){
			return false;
		}
		else{
			return $roleArray;
		}
	}

    /**
     * @param $roleUUID
     * @return bool
     */
    public function loadRole($roleUUID){
		$stmt = $this->db->prepare("SELECT	uuid,
											roleType,
											roleName,
											roleDescription
									FROM roleList
									WHERE uuid = ?");
		$stmt->bind_param("s",$roleUUID);

		if($stmt->execute()) {
			$stmt->bind_result($uuid, $roleType, $roleName, $roleDescription);
			$stmt->fetch();
			$stmt->close();

			$this->uuid = $uuid;
			$this->roleType = $roleType;
			$this->roleName = $roleName;
			$this->roleDescription = $roleDescription;

			if(empty($this->uuid)){
				$this->error = "That role does not exist.";
				return false;
			}
			else{
				return true;
			}
		}
		else{
			$sqlError = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_ROLE_LOAD");
			$this->log->setDetail("Role UUID",$roleUUID);
			$this->log->setDetail("MySQL Error",$sqlError);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

    /**
     * @return bool
     */
    public function saveRole(){
		$stmt = $this->db->prepare("INSERT INTO roleList (  uuid,
															roleType,
															roleName,
															roleDescription)
									VALUES (?,?,?,?)
									ON DUPLICATE KEY UPDATE
										uuid=VALUES(uuid),
										roleType=VALUES(roleType),
										roleName=VALUES(roleName),
										roleDescription=VALUES(roleDescription)");

		$stmt->bind_param("ssss",$this->uuid,$this->roleType,$this->roleName,$this->roleDescription);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();
			
			$this->log->setAction("ERROR_ROLE_SAVE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}

    /**
     * @param $roleUUID
     * @return bool
     */
    public function verifyRole($roleUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM roleList WHERE uuid = ?");
        $stmt->bind_param("s",$roleUUID);

		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			if(!$count){
				return false;
			}
			else{
				return $count;
			}
		}
		else{
			$sqlError = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_ROLE_VERIFY");
			$this->log->setDetail("Role UUID",$roleUUID);
			$this->log->setDetail("MySQL Error",$sqlError);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
    }

    /**
     * @param $currentRoleUUID
     * @param $targetRoleUUID
     * @return bool
     */
    public function migrateUserRoles($currentRoleUUID,$targetRoleUUID){
        $stmt = $this->db->prepare("UPDATE userData SET userRole = ? WHERE userRole = ?");
        $stmt->bind_param("ss",$targetRoleUUID,$currentRoleUUID);

        if($stmt->execute()){
			$affectedRows = $stmt->affected_rows;
			$stmt->close();

            $this->log->setAction("ROLE_MIGRATE");
            $this->log->setDetail("Current Role",$currentRoleUUID);
            $this->log->setDetail("Current Role Name",$this->getRoleName($currentRoleUUID));
            $this->log->setDetail("Target Role",$targetRoleUUID);
            $this->log->setDetail("Target Role Name",$this->getRoleName($targetRoleUUID));
            $this->log->setDetail("Affected Users",$affectedRows);
            $this->log->saveEntry();

            return true;
        }
        else{
			$sqlError = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_ROLE_MIGRATE");
            $this->log->setDetail("Current Role",$currentRoleUUID);
            $this->log->setDetail("Current Role Name",$this->getRoleName($currentRoleUUID));
            $this->log->setDetail("Target Role",$targetRoleUUID);
            $this->log->setDetail("Target Role Name",$this->getRoleName($targetRoleUUID));
            $this->log->setDetail("MySQL Error",$sqlError);
            $this->log->saveEntry();

            return false;
        }
    }

    /**
     * @param $userUUID
     * @return bool|string
     */
    public function verifyUserRole($userUUID){
		$_user = new UserManager($this->db, $this->log, $this->emailQueue);
		
		if(!$_user->loadUser($userUUID)){
			$this->error = $_user->error;
			return false;
		}
		else{
			return $this->getRoleType($_user->getUserRole());
		}
	}

    /**
     * @param $roleName
     * @param bool $baseUUID
     * @return bool
     */
    public function listRoleUsers($roleName,$baseUUID=false){
		$roleUUID = $this->getRoleUUIDByName($roleName);

        if($baseUUID) {
            $stmt = $this->db->prepare("SELECT uuid FROM userData WHERE userRole = ? AND userBase = ? ORDER BY userData.userLastName, userData.userFirstName ASC");
            $stmt->bind_param("ss", $roleUUID,$baseUUID);
        }
        else{
            $stmt = $this->db->prepare("SELECT uuid FROM userData WHERE userRole = ? ORDER BY userData.userLastName, userData.userFirstName ASC");
            $stmt->bind_param("s", $roleUUID);
        }

		if($stmt->execute()){
			$stmt->bind_result($userUUID);

			while($stmt->fetch()){
				$userArray[] = $userUUID;
			}

			$stmt->close();

			if(isset($userArray) && is_array($userArray)){
				return $userArray;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_ROLE_LIST_USERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
            $this->log->setDetail("Role Name",$roleName);
            if($baseUUID)
                $this->log->setDetail("Base UUID",$baseUUID);

			$this->log->saveEntry();

			return false;
		}
	}

    /**
     * @return bool
     */
    public function listAdministrators(){
		return $this->listRoleUsers("Administrators");
	}

    /**
     * @return bool
     */
    public function listEditors(){
		return $this->listRoleUsers("Question Editors");
	}

    /**
     * @return bool
     */
    public function listSuperAdministrators(){
		return $this->listRoleUsers("Super Administrators");
	}

    /**
     * @return bool
     */
    public function listSupervisors(){
		return $this->listRoleUsers("Supervisors");
	}

    /**
     * @return bool
     */
    public function listTrainingManagers(){
		return $this->listRoleUsers("Training Managers");
	}

    /**
     * @return bool
     */
    public function listUsers(){
		return $this->listRoleUsers("Users");
	}

    /**
     * @param $baseUUID
     * @return bool
     */
    public function listUsersByBase($baseUUID){
        return $this->listRoleUsers("Users",$baseUUID);
    }

    /**
     * @param $baseUUID
     * @return bool
     */
    public function listSupervisorsByBase($baseUUID){
        return $this->listRoleUsers("Supervisors",$baseUUID);
    }

    /**
     * @param $baseUUID
     * @return bool
     */
    public function listTrainingManagersByBase($baseUUID){
        return $this->listRoleUsers("Training Managers",$baseUUID);
    }

    /**
     * @param $baseUUID
     * @return bool
     */
    public function listAdministratorsByBase($baseUUID){
        return $this->listRoleUsers("Administrators",$baseUUID);
    }

    /**
     * @param $baseUUID
     * @return bool
     */
    public function listSuperAdministratorsByBase($baseUUID){
        return $this->listRoleUsers("Super Administrators",$baseUUID);
    }

    /**
     * @return mixed
     */
    public function getUUID(){
		return $this->uuid;
	}

    /**
     * @param bool $uuid
     * @return bool|string
     */
    public function getRoleType($uuid = false){
		if(!empty($uuid)){
			$_roles = new RoleManager($this->db, $this->log, $this->emailQueue);
			
			if($_roles->loadRole($uuid)){
				$tempRoleType = $_roles->getRoleType();
			
				return htmlspecialchars($tempRoleType);
			}
			else{
				$this->error = $_roles->error;
				return false;
			}
		}
		else{
			return htmlspecialchars($this->roleType);
		}
	}

    /**
     * @param $roleName
     * @return bool
     */
    public function getRoleUUIDByName($roleName){
		$stmt = $this->db->prepare("SELECT uuid FROM roleList WHERE roleName = ?");
		$stmt->bind_param("s",$roleName);
		
		if($stmt->execute()){
			$stmt->bind_result($roleUUID);
			$stmt->fetch();
			$stmt->close();
			
			if(!empty($roleUUID)){
				return $roleUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_ROLE_GET_UUID");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("Role Name",$roleName);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

    /**
     * @param bool $uuid
     * @return bool|string
     */
    public function getRoleName($uuid=false){
		if(!empty($uuid)){
			$_roles = new RoleManager($this->db, $this->log, $this->emailQueue);
			
			if(!$_roles->loadRole($uuid)){
				$this->error = $_roles->error;
				return false;
			}
			else{
				return htmlspecialchars($_roles->getRoleName());
			}
		}
		else{
			return htmlspecialchars($this->roleName);
		}
	}

    /**
     * @return string
     */
    public function getRoleDescription(){
		return htmlspecialchars($this->roleDescription);
	}

    /**
     * @param $uuid
     * @return bool
     */
    public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}

    /**
     * @param $roleType
     * @return bool
     */
    public function setRoleType($roleType){
		$this->roleType = htmlspecialchars_decode($roleType);
		return true;
	}

    /**
     * @param $roleName
     * @return bool
     */
    public function setRoleName($roleName){
		$this->roleName = htmlspecialchars_decode($roleName);
		return true;
	}

    /**
     * @param $roleDescription
     * @return bool
     */
    public function setRoleDescription($roleDescription){
		$this->roleDescription = htmlspecialchars_decode($roleDescription);
		return true;
	}

    /**
     *
     */
    public function __destruct(){
		parent::__destruct();
	}
}