<?php

/**
 * Class auth
 */
class auth extends user
{
    /**
     * @var roles
     */
    protected $roles;

    /**
     * @var
     */
    public $activationStatus;

    /**
     * @param bool $uuid
     * @param log $log
     * @param mysqli $db
     * @param roles $roles
     * @param emailQueue $emailQueue
     */
    function __construct($uuid, log $log, mysqli $db, roles $roles, emailQueue $emailQueue){
		parent::__construct($db,$log,$emailQueue);
		parent::loadUser($uuid);
		
		$this->roles = $roles;
	}

    /**
     * @param $password
     * @return bool
     */
    function comparePassword($password){
		if(empty($this->userPassword)){
			if($this->hashUserLegacyPassword($password) == $this->userLegacyPassword){
				$this->setUserPassword($password);
				$this->userLegacyPassword = NULL;
				$this->saveUser();
				
				$this->log->setAction("MIGRATED_PASSWORD");
				$this->log->setUserUUID($this->uuid);
				$this->log->saveEntry();
				
				return true;
			}
			else{
				return false;
			}
		}
		elseif(password_verify($password,$this->userPassword)){
			return true;
		}
		else{
			return false;
		}
	}

    /**
     * @return bool
     */
    function getActivationStatus(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM queueUnactivatedUsers WHERE userUUID = ?");
		$stmt->bind_param("s",$this->uuid);
		$stmt->execute();
		$stmt->bind_result($count);

		while($stmt->fetch()){
			$tempCount = $count;
		}

		if($tempCount > 0){
			$this->activationStatus = false;
		}
		else{
			$this->activationStatus = true;
		}
		
		return $this->activationStatus;
	}

    /**
     * @return mixed
     */
    function getError(){
		return $this->error;
	}

    /**
     * @param $userPassword
     * @return string
     */
    function getHash($userPassword){
		return $this->hashUserPassword($userPassword);
	}

    /**
     * @param bool $increment
     * @param bool $reset
     * @return bool
     */
    function limitLogins($increment = false, $reset = false){
		if(!isset($_SESSION['limitStartTime'])){
			$_SESSION['limitStartTime'] = time();
		}

		if(!isset($_SESSION['limitAttempts'])){
			$_SESSION['limitAttempts'] = 1;
		}

		if($increment == true){
			$_SESSION['limitAttempts']++;
		}
		else{
			if($reset == true){
				unset($_SESSION['limitAttempts']);
				unset($_SESSION['limitStartTime']);
				unset($_SESSION['rateLimitRecorded']);

				return true;
			}
			else{
				if($_SESSION['limitAttempts'] < 10){
					return true;
				}
				else{
					if(time() >= ($_SESSION['limitStartTime'] + 300)){
						$this->limitLogins(false,true);
						return true;
					}
					else{
						return false;
					}
				}
			}
		}
	}

    /**
     * @param $password
     * @return bool
     */
    function login($password){
		if(empty($password)){
			$this->error = "You must provide a password.";
			$this->log->setAction("ERROR_LOGIN_EMPTY_PASSWORD");
			$this->log->saveEntry();

			return false;
		}
		elseif(!$this->limitLogins()){
			$this->error = "You have made too many login attempts recently.  Please try again soon.<br /><br />While you wait, would you like to <a href=\"/auth/reset\">reset your password</a>?";

			if(!isset($_SESSION['rateLimitRecorded'])){
				$this->log->setAction("ERROR_LOGIN_RATE_LIMIT_REACHED");
				$this->log->saveEntry();
				$_SESSION['rateLimitRecorded'] = true;
			}

			return false;
		}
		elseif(!$this->comparePassword($password)){
			$this->error = "Your password is incorrect";
			$this->limitLogins(true);
			
			$this->log->setAction("ERROR_LOGIN_INVALID_PASSWORD");
			$this->log->saveEntry();

			return false;
		}
		elseif(!$this->getActivationStatus()){
			$this->error = "Your account has not been activated. <a href=\"/auth/activate\">Click Here</a> to activate your account.";
			$this->log->setAction("ERROR_LOGIN_UNACTIVATED_ACCOUNT");
			$this->log->saveEntry();

			return false;
		}
        elseif($this->getUserDisabled()){
            $this->error = "Your account has been disabled.  If you feel this is in error, <a href=\"http://helpdesk.cdcmastery.com/\">Open a Support Ticket</a>.";
            $this->log->setAction("ERROR_LOGIN_USER_DISABLED");
            $this->log->saveEntry();

            return false;
        }
		else{ //authorization successful
			$this->limitLogins(false,true); //reset rate limiter
			
			if($this->roles->getRoleType($this->getUserRole()) == "admin"){
				$_SESSION['cdcMasteryAdmin'] = true;
			}
			elseif($this->roles->getRoleType($this->getUserRole()) == "trainingManager"){
				$_SESSION['trainingManager'] = true;
			}
			elseif($this->roles->getRoleType($this->getUserRole()) == "supervisor"){
				$_SESSION['supervisor'] = true;
			}
			elseif($this->roles->getRoleType($this->getUserRole()) == "editor"){
				$_SESSION['editor'] = true;
			}

			$_SESSION['auth']		= true;
			$_SESSION['userUUID']	= $this->getUUID();
			$_SESSION['userName']	= $this->getFullName();
			$_SESSION['userEmail']	= $this->getUserEmail();
			$_SESSION['timeZone']	= $this->getUserTimeZone();

			$this->log->setAction("LOGIN_SUCCESS");
			$this->log->setUserUUID($_SESSION['userUUID']);
			$this->log->saveEntry();

			$this->updateLastLogin($this->getUUID());

			return true;
		}
	}

    /**
     *
     */
    function __destruct(){
		parent::__destruct();
	}
}