<?php
class CDCMastery 
{
	public function __construct(){
		
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
	
	function formatDateTime($dateTime, $format="F j, Y, g:i a"){
		return date($format,strtotime($dateTime));
	}
	
	function genUUID() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	
				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),
	
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,
	
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,
	
				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
	
	function hashUserPassword( $userPassword ){
		return hash('sha512',$userPassword);
	}
	
	function isTimeEmpty($time){
		if($time == "0000-00-00 00:00:00" || $time == "0000-00-00"){
			return true;
		}
		else{
			return false;
		}
	}
	
	function redirect($destination){
		unset($_SESSION['vars']);
		header("Location: ".$destination);
		ob_end_flush();
		exit();
	}
	
	function replaceEmptyField($input){
		if(empty($input)){
			return "N/A";
		}
		else{
			return $input;
		}
	}
	
	function trimString($string,$length){
		if(strlen($string) > $length){
			$string = substr($string,0,$length) . "...";
			return $string;
		}
		else{
			return $string;
		}
	}
	
	function verifyAdmin(){
		if(isset($_SESSION['cdcMasteryAdmin']) && $_SESSION['cdcMasteryAdmin'] == true){
			return true;
		}
		else{
			return false;
		}
	}
}