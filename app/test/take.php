<?php
if(isset($_SESSION['vars'][0])):
	/*
	 * Entry point for test in progress, or after resuming a test
	 */
	$testUUID = $_SESSION['vars'][0];
	
	/*
	 * Check if test is complete
	 */
else:
	/*
	 * Entry point for a new test
	 */
	if(!empty($_POST)){
		
	}
	else{
		
	}
endif;