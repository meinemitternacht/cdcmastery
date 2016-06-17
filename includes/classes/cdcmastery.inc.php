<?php
class CDCMastery 
{
	public $aesKey;
	public $maxQuestions = 100;
    public $passingScore = 80;
	public $staticUserArray = Array('SYSTEM','ANONYMOUS');
	public $publicCacheTTL = 10800; /* Cache objects for three hours if not logged in */
	public $privateCacheTTL = 300; /* Cache objects for five minutes if logged in */
	
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
		if(strtolower($timestamp) == "never"){
			return "Never";
		}
		elseif(strtolower($timestamp) == "n/a"){
			return "N/A";
		}
		else{
			$sourceTimeZone = new DateTimeZone("UTC");
			$destinationTimeZone = new DateTimeZone($userTimeZone);

			$dateTimeObject = new DateTime($timestamp, $sourceTimeZone);
			$dateTimeObject->setTimezone($destinationTimeZone);

			return $dateTimeObject->format($format);
		}
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
		return ENCRYPTION_KEY;
	}
	
	public function getMaxQuestions(){
		return $this->maxQuestions;
	}

    public function getOrdinal($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            return $number. 'th';
        else
            return $number. $ends[$number % 10];
    }

    public function getPassingScore(){
        return $this->passingScore;
    }
	
	public function getStaticUserArray(){
		return $this->staticUserArray;
	}

	public function getPublicCacheTTL(){
		return $this->publicCacheTTL;
	}

	public function getPrivateCacheTTL(){
		return $this->privateCacheTTL;
	}

	public function getCacheTTL($storageLevel=false){
		if(!$storageLevel){
			if($this->loggedIn()){
				return $this->getPrivateCacheTTL();
			}
			else{
				return $this->getPublicCacheTTL();
			}
		}
		else{
			switch($storageLevel){
				case 1:
					return 30; /* 30 seconds */
					break;
				case 2:
					return 120; /* 2 minutes */
					break;
				case 3:
					return 300; /* 5 minutes */
					break;
				case 4:
					return 3600; /* 1 hour */
					break;
				case 5:
					return 10800; /* 3 hours */
					break;
				case 6:
					return 21600; /* 6 hours */
					break;
				case 99:
					return 1;
					break;
				default:
					return 300;
					break;
			}
		}
	}
	
	public function hashUserPassword($userPassword){
		return password_hash($userPassword,PASSWORD_BCRYPT,["cost" => 13]);
	}
	
	public function hashUserLegacyPassword($userLegacyPassword){
		return hash('sha1',$userLegacyPassword);
	}

	/**
	 * This program is free software. It comes without any warranty, to
	 * the extent permitted by applicable law. You can redistribute it
	 * and/or modify it under the terms of the Do What The Fuck You Want
	 * To Public License, Version 2, as published by Sam Hocevar. See
	 * http://sam.zoy.org/wtfpl/COPYING for more details.
	 */

	/**
	 * Tests if an input is valid PHP serialized string.
	 *
	 * Checks if a string is serialized using quick string manipulation
	 * to throw out obviously incorrect strings. Unserialize is then run
	 * on the string to perform the final verification.
	 *
	 * Valid serialized forms are the following:
	 * <ul>
	 * <li>boolean: <code>b:1;</code></li>
	 * <li>integer: <code>i:1;</code></li>
	 * <li>double: <code>d:0.2;</code></li>
	 * <li>string: <code>s:4:"test";</code></li>
	 * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
	 * <li>object: <code>O:8:"stdClass":0:{}</code></li>
	 * <li>null: <code>N;</code></li>
	 * </ul>
	 *
	 * @author		Chris Smith <code+php@chris.cs278.org>
	 * @copyright	Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
	 * @license		http://sam.zoy.org/wtfpl/ WTFPL
	 * @param		string	$value	Value to test for serialized form
	 * @param		mixed	$result	Result of unserialize() of the $value
	 * @return		boolean			True if $value is serialized data, otherwise false
	 */
	public function is_serialized($value, &$result = null)
	{
		if(!empty($value)) {
			// Bit of a give away this one
			if (!is_string($value)) {
				return false;
			}

			// Serialized false, return true. unserialize() returns false on an
			// invalid string or it could return false if the string is serialized
			// false, eliminate that possibility.
			if ($value === 'b:0;') {
				$result = false;

				return true;
			}

			$length = strlen($value);
			$end = '';

			switch ($value[0]) {
				case 's':
					if ($value[$length - 2] !== '"') {
						return false;
					}
				case 'b':
				case 'i':
				case 'd':
					// This looks odd but it is quicker than isset()ing
					$end .= ';';
				case 'a':
				case 'O':
					$end .= '}';

					if ($value[1] !== ':') {
						return false;
					}

					switch ($value[2]) {
						case 0:
						case 1:
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
						case 7:
						case 8:
						case 9:
							break;

						default:
							return false;
					}
				case 'N':
					$end .= ';';

					if ($value[$length - 1] !== $end[0]) {
						return false;
					}
					break;

				default:
					return false;
			}

			if (($result = @unserialize($value)) === false) {
				$result = null;

				return false;
			}

			return true;
		}
		else{
			return false;
		}
	}
	
	public function isTimeEmpty($time){
		if(empty($time) || is_null($time) || $time == "0000-00-00 00:00:00" || $time == "0000-00-00"){
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
		else{
			return false;
		}
    }

	/**
	 * @param $filepath
	 * @param int $lines
	 * @param bool $adaptive
	 * @return bool|string
	 *
	 * https://gist.github.com/lorenzos/1711e81a9162320fde20
	 */
	public function tailCustom($filepath, $lines = 1, $adaptive = true) {
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		fseek($f, -1, SEEK_END);

		if (fread($f, 1) != "\n") $lines -= 1;

		$output = '';
		$chunk = '';

		while (ftell($f) > 0 && $lines >= 0) {
			$seek = min(ftell($f), $buffer);
			fseek($f, -$seek, SEEK_CUR);
			$output = ($chunk = fread($f, $seek)) . $output;
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			$lines -= substr_count($chunk, "\n");
		}

		while ($lines++ < 0) {
			$output = substr($output, strpos($output, "\n") + 1);
		}

		fclose($f);
		return trim($output);
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