<?php
class CDCMastery 
{
	public $aesKey = "***REMOVED***";
	public $maxQuestions = 100;
    public $passingScore = 80;
	public $staticUserArray = Array('SYSTEM','ANONYMOUS');
	
	public function __construct(){
		
	}

    public function checkEmailAddress($emailAddress){
		if(strpos($emailAddress,"@") !== false) {
			$emailArray = explode("@", $emailAddress);

			if (!preg_match("/(af|mail)\.mil$/",$emailArray[1])) {
				return false;
			}
			else {
				return true;
			}
		}
		else {
			return false;
		}
    }

    public function checkPasswordComplexity($passwordString,$userHandleString,$userEmailString){
        $errors = Array();
        $noLetters = false;

        if (strlen($passwordString) < 8) {
            $errors[] = "Password must be at least eight characters.";
        }

        if (!preg_match("#[0-9]+#", $passwordString)) {
            $errors[] = "Password must include at least one number.";
        }

        if (!preg_match("#[a-zA-Z]+#", $passwordString)) {
            $errors[] = "Password must include at least one letter.";
            $noLetters = true;
        }

        if (!preg_match("#[A-Z]+#", $passwordString) && !$noLetters) {
            $errors[] = "Password must include at least one uppercase letter.";
        }

        if (!preg_match("#[a-z]+#", $passwordString) && !$noLetters) {
            $errors[] = "Password must include at least one lowercase letter.";
        }

        if (strtolower($passwordString) == strtolower($userHandleString)){
            $errors[] = "Password cannot match username.";
        }

        if (strtolower($passwordString) == strtolower($userEmailString)){
            $errors[] = "Password cannot match e-mail address.";
        }

        if(empty($errors)){
            return true;
        }
        else{
            return $errors;
        }
    }
	
	public function formatOutputString($outputString, $trimLength=false, $ucFirst = false){
		if(empty($outputString)){
			return "N/A";
		}
		else{
			if($trimLength){
				if($ucFirst){
					return ucfirst($this->trimString($outputString, $trimLength));
				}
				else{
					return $this->trimString($outputString, $trimLength);
				}
			}
			else{
				if($ucFirst){
					return ucfirst($outputString);
				}
				else{
					return $outputString;
				}
			}
		}
	}
	
	public function outputDateTime($timestamp, $userTimeZone, $format="F j, Y, g:i a"){
		$sourceTimeZone = new DateTimeZone("UTC");
		$destinationTimeZone = new DateTimeZone($userTimeZone);
		
		$dateTimeObject = new DateTime($timestamp, $sourceTimeZone);
		$dateTimeObject->setTimezone($destinationTimeZone);
		
		return $dateTimeObject->format($format);
	}
	
	public function formatDateTime($dateTime, $format="F j, Y, g:i a"){
		return date($format,strtotime($dateTime));
	}
	
	public function genUUID() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	
	public function getEncryptionKey(){
		return $this->aesKey;
	}
	
	public function getMaxQuestions(){
		return $this->maxQuestions;
	}

    public function getPassingScore(){
        return $this->passingScore;
    }
	
	public function getStaticUserArray(){
		return $this->staticUserArray;
	}
	
	public function hashUserPassword($userPassword){
		return password_hash($userPassword,PASSWORD_BCRYPT,["cost" => 13]);
	}
	
	public function hashUserLegacyPassword($userLegacyPassword){
		return hash('sha1',$userLegacyPassword);
	}
	
	public function isTimeEmpty($time){
		if($time == "0000-00-00 00:00:00" || $time == "0000-00-00"){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function listRanks(){
		$return['Enlisted'][0]['AB'] 	= "Airman Basic";
		$return['Enlisted'][1]['Amn'] 	= "Airman";
		$return['Enlisted'][2]['A1C'] 	= "Airman First Class";
		$return['Enlisted'][3]['SrA'] 	= "Senior Airman";
		$return['Enlisted'][4]['SSgt'] 	= "Staff Sergeant";
		$return['Enlisted'][5]['TSgt'] 	= "Technical Sergeant";
		$return['Enlisted'][6]['MSgt'] 	= "Master Sergeant";
		$return['Enlisted'][7]['SMSgt'] = "Senior Master Sergeant";
		$return['Enlisted'][8]['CMSgt'] = "Chief Master Sergeant";
	
		$return['Officer'][0]['2LT']	= "Second Lieutenant";
		$return['Officer'][1]['1LT']	= "First Lieutenant";
		$return['Officer'][2]['Cpt']	= "Captain";
		$return['Officer'][3]['Maj']	= "Major";
		$return['Officer'][4]['Lt Col']	= "Lieutenant Colonel";
		$return['Officer'][5]['Col']	= "Colonel";
		$return['Officer'][6]['Brig Gen']	= "Brigadier General";
		$return['Officer'][7]['Maj Gen']	= "Major General";
		$return['Officer'][8]['Lt Gen']		= "Lieutenant General";
		$return['Officer'][9]['Gen']		= "General";
	
		return $return;
	}
	
	public function listTimeZones(){
		static $regions = array(
				'Africa' => DateTimeZone::AFRICA,
				'America' => DateTimeZone::AMERICA,
				'Antarctica' => DateTimeZone::ANTARCTICA,
				'Asia' => DateTimeZone::ASIA,
				'Atlantic' => DateTimeZone::ATLANTIC,
				'Europe' => DateTimeZone::EUROPE,
				'Indian' => DateTimeZone::INDIAN,
				'Pacific' => DateTimeZone::PACIFIC
		);
	
		foreach ($regions as $name => $mask) {
			$tzlist[] = DateTimeZone::listIdentifiers($mask);
		}
	
		return $tzlist;
	}
	
	public function loggedIn(){
		if(isset($_SESSION['auth'])){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function redirect($destination){
		unset($_SESSION['vars']);
		session_write_close();
		header("Location: ".$destination);
		ob_end_flush();
		exit();
	}
	
	public function replaceEmptyField($input){
		if(empty($input)){
			return "N/A";
		}
		else{
			return $input;
		}
	}

    public function scoreColor($score){
        if(empty($score)){
            return false;
        }
        elseif($score >= 80){
            return "text-success-bold";
        }
        elseif($score < 80){
            return "text-warning-bold";
        }
    }
	
	public function trimString($string,$length){
		if(strlen($string) > $length){
			$string = substr($string,0,$length) . "...";
			return $string;
		}
		else{
			return $string;
		}
	}
	
	public function verifyAdmin(){
		if(isset($_SESSION['cdcMasteryAdmin']) && $_SESSION['cdcMasteryAdmin'] == true){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function verifyTrainingManager(){
		if(isset($_SESSION['trainingManager']) && $_SESSION['trainingManager'] == true){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function verifySupervisor(){
		if(isset($_SESSION['supervisor']) && $_SESSION['supervisor'] == true){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function verifyEditor(){
		if(isset($_SESSION['editor']) && $_SESSION['editor'] == true){
			return true;
		}
		else{
			return false;
		}
	}
	
	function __destruct(){
		//nada
	}
}