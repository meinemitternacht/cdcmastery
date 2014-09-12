<?php
/*
 * This class provides an interface for taking tests and reviewing previous tests.
 * All other functions for changing previous test variables will be administrative functions.
*/

class testManager extends CDCMastery
{
	protected $db;
	protected $log;
	protected $question;
	
	public $error;
	public $message;
	
	/*
	 * testHistory Table
	 */
	public $uuid;				//varchar 40
	public $userUUID;			//varchar 40
	public $afscList;			//mediumtext
	public $totalQuestions;		//int 5
	public $questionsMissed;	//int 5
	public $testScore;			//int 5
	public $testTimeStarted;	//datetime
	public $testTimeCompleted;	//datetime
	public $oldTestID;			//varchar 40
	
	/*
	 * testManager Table
	 */
	public $incompleteTestUUID;			//varchar 40
	public $incompleteTimeStarted;		//datetime
	public $incompleteQuestionList;		//mediumtext
	public $incompleteCurrentQuestion;	//int 5
	public $incompleteQuestionsAnswered;//int 5
	public $incompleteTotalQuestions;	//int 5
	public $incompleteAFSCList;			//mediumtext
	public $incompleteUserUUID;			//varchar 40
	public $incompleteCombinedTest;		//bool
	
	public function __construct(mysqli $db, log $log, question $question){
		$this->db = $db;
		$this->log = $log;
		$this->question = $question;
	}
	
	/*
	 * testHistory Table Related Functions
	 */
	
	public function listTests(){
		$res = $this->db->query("SELECT uuid,
										userUUID,
										afscList,
										totalQuestions,
										questionsMissed,
										testScore,
										testTimeStarted,
										testTimeCompleted,
										oldTestID 
									FROM testHistory 
									ORDER BY testTimeStarted ASC");
		
		$testArray = Array();
		
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$testArray[$row['uuid']]['userUUID'] = $row['userUUID'];
				$testArray[$row['uuid']]['afscList'] = $row['afscList'];
				$testArray[$row['uuid']]['totalQuestions'] = $row['totalQuestions'];
				$testArray[$row['uuid']]['questionsMissed'] = $row['questionsMissed'];
				$testArray[$row['uuid']]['testScore'] = $row['testScore'];
				$testArray[$row['uuid']]['testTimeStarted'] = $row['testTimeStarted'];
				$testArray[$row['uuid']]['testTimeCompleted'] = $row['testTimeCompleted'];
				$testArray[$row['uuid']]['oldTestID'] = $row['oldTestID'];
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
			return $testArray;
		}
	}
	
	public function loadTest($uuid){
		$stmt = $this->db->prepare("SELECT	uuid,
											userUUID,
											afscList,
											totalQuestions,
											questionsMissed,
											testScore,
											testTimeStarted,
											testTimeCompleted,
											oldTestID
									FROM testHistory
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result( $uuid,
								$userUUID,
								$afscList,
								$totalQuestions,
								$questionsMissed,
								$testScore,
								$testTimeStarted,
								$testTimeCompleted,
								$oldTestID );
			
			while($stmt->fetch()){
				$this->uuid = $uuid;
				$this->userUUID = $userUUID;
				$this->afscList = unserialize($afscList);
				$this->totalQuestions = $totalQuestions;
				$this->questionsMissed = $questionsMissed;
				$this->testScore = $testScore;
				$this->testTimeStarted = $testTimeStarted;
				$this->testTimeCompleted = $testTimeCompleted;
				$this->oldTestID = $oldTestID;
				
				$ret = true;
			}
			
			$stmt->close();
			
			if(empty($this->uuid)){
				$this->error = "That test does not exist.";
				$ret = false;
			}
			
			return $ret;
		}
		else{
			return false;
		}
	}
	
	public function getUUID(){
		return $this->uuid;
	}
	
	public function getUserUUID(){
		return $this->userUUID;
	}
	
	public function getAFSCList(){
		return $this->afscList;
	}
	
	public function getTotalQuestions(){
		return $this->totalQuestions;
	}
	
	public function getQuestionsMissed(){
		return $this->questionsMissed;
	}
	
	public function getTestScore(){
		return $this->testScore;
	}
	
	public function getTestTimeStarted(){
		return $this->testTimeStarted;
	}
	
	public function getTestTimeCompleted(){
		return $this->testTimeCompleted;
	}
	
	public function getOldTestID(){
		return $this->oldTestID;
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}
	
	/*
	 * testManager Table Related Functions
	 */
	
	public function listIncompleteTests(){
		$res = $this->db->query("SELECT testUUID,
										timeStarted,
										questionList,
										currentQuestion,
										questionsAnswered,
										totalQuestions,
										afscList,
										userUUID,
										combinedTest
									FROM testManager
									ORDER BY timeStarted ASC");
	
		$testArray = Array();
	
		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$testArray[$row['testUUID']]['timeStarted'] = $row['timeStarted'];
				$testArray[$row['testUUID']]['questionList'] = $row['questionList'];
				$testArray[$row['testUUID']]['currentQuestion'] = $row['currentQuestion'];
				$testArray[$row['testUUID']]['questionsAnswered'] = $row['questionsAnswered'];
				$testArray[$row['testUUID']]['totalQuestions'] = $row['totalQuestions'];
				$testArray[$row['testUUID']]['afscList'] = $row['afscList'];
				$testArray[$row['testUUID']]['userUUID'] = $row['userUUID'];
				$testArray[$row['testUUID']]['combinedTest'] = $row['combinedTest'];
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
			return $testArray;
		}
	}
	
	public function loadIncompleteTest($uuid){
		$stmt = $this->db->prepare("SELECT	testUUID,
											timeStarted,
											questionList,
											currentQuestion,
											questionsAnswered,
											totalQuestions,
											afscList,
											userUUID,
											combinedTest
										FROM testManager
										WHERE testUUID = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result(	$incompleteTestUUID,
								$incompleteTimeStarted,
								$incompleteQuestionList,
								$incompleteCurrentQuestion,
								$incompleteQuestionsAnswered,
								$incompleteTotalQuestions,
								$incompleteAFSCList,
								$incompleteUserUUID,
								$incompleteCombinedTest);
			
			while($stmt->fetch()){
				$this->incompleteTestUUID = $incompleteTestUUID;
				$this->incompleteTimeStarted = $incompleteTimeStarted;
				$this->incompleteQuestionList = unserialize($incompleteQuestionList);
				$this->incompleteCurrentQuestion = $incompleteCurrentQuestion;
				$this->incompleteQuestionsAnswered = $incompleteQuestionsAnswered;
				$this->incompleteTotalQuestions = $incompleteTotalQuestions;
				$this->incompleteAFSCList = unserialize($incompleteAFSCList);
				$this->incompleteUserUUID = $incompleteUserUUID;
				$this->incompleteCombinedTest = $incompleteCombinedTest;
			}
			
			return true;
		}
		else{
			return false;
		}
	}
	
	public function saveIncompleteTest(){
		$stmt = $this->db->prepare("INSERT INTO testManager (	testUUID,
																timeStarted,
																questionList,
																currentQuestion,
																questionsAnswered,
																totalQuestions,
																afscList,
																userUUID,
																combinedTest)
													VALUES (?,?,?,?,?,?,?,?,?)
													ON DUPLICATE KEY UPDATE
																testUUID=VALUES(testUUID),
																timeStarted=VALUES(timeStarted),
																questionList=VALUES(questionList),
																currentQuestion=VALUES(currentQuestion),
																questionsAnswered=VALUES(questionsAnswered),
																totalQuestions=VALUES(totalQuestions),
																afscList=VALUES(afscList),
																userUUID=VALUES(userUUID),
																combinedTest=VALUES(combinedTest)");
		$stmt->bind_param("sssiiissi",	$this->incompleteTestUUID,
										$this->incompleteTimeStarted,
										serialize($this->incompleteQuestionList),
										$this->incompleteCurrentQuestion,
										$this->incompleteQuestionsAnswered,
										$this->incompleteTotalQuestions,
										serialize($this->incompleteAFSCList),
										$this->incompleteUserUUID,
										$this->incompleteCombinedTest);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","testManager->saveIncompleteTest()");
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->setDetail("INCOMPLETE TEST UUID",$this->incompleteTestUUID);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			return true;
		}
	}
	
	public function deleteIncompleteTest($allIncompleteTests=false,$testUUID=false){
		if($allIncompleteTests){
			$stmt = $this->db->prepare("DELETE FROM testManager WHERE userUUID = ?");
			$stmt->bind_param("s",$this->incompleteUserUUID);
			
			if(!$stmt->execute()){
				$this->log->setAction("MYSQL_ERROR");
				$this->log->setDetail("ERROR",$stmt->error);
				$this->log->setDetail("CALLING FUNCTION","testManager->deleteIncompleteTest()");
				$this->log->setDetail("allIncompleteTests","true");
				$this->log->saveEntry();
				
				$this->error = "A problem was encountered when deleting your incomplete tests from the database.  Please contact CDCMastery support for assistance.";
				return false;
			}
			else{
				$this->message[] = "Your incomplete tests were deleted successfully.";
				return true;
			}
		}
		else{
			if($testUUID){
				$stmt = $this->db->prepare("DELETE FROM testManager WHERE testUUID = ?");
				$stmt->bind_param("s",$testUUID);
					
				if(!$stmt->execute()){
					$this->log->setAction("MYSQL_ERROR");
					$this->log->setDetail("ERROR",$stmt->error);
					$this->log->setDetail("CALLING FUNCTION","testManager->deleteIncompleteTest()");
					$this->log->setDetail("allIncompleteTests","false");
					$this->log->setDetail("testUUID",$testUUID);
					$this->log->saveEntry();
				
					$this->error = "A problem was encountered when deleting your incomplete test from the database.  Please contact CDCMastery support for assistance.";
					return false;
				}
				else{
					$this->message[] = "Your incomplete test was deleted successfully.";
					return true;
				}
			}
			else{
				if($this->incompleteTestUUID){
					$stmt = $this->db->prepare("DELETE FROM testManager WHERE testUUID = ?");
					$stmt->bind_param("s",$this->incompleteTestUUID);
						
					if(!$stmt->execute()){
						$this->log->setAction("MYSQL_ERROR");
						$this->log->setDetail("ERROR",$stmt->error);
						$this->log->setDetail("CALLING FUNCTION","testManager->deleteIncompleteTest()");
						$this->log->setDetail("allIncompleteTests","false");
						$this->log->setDetail("testUUID",$this->incompleteTestUUID);
						$this->log->saveEntry();
					
						$this->error = "A problem was encountered when deleting your incomplete test from the database.  Please contact CDCMastery support for assistance.";
						return false;
					}
					else{
						$this->message[] = "Your incomplete test was deleted successfully.";
						return true;
					}
				}
				else{
					$this->error = "You must specify a test to delete.";
					return true;
				}
			}
		}
	}
	
	public function newTest(){
		$this->setIncompleteUserUUID($_SESSION['userUUID']);
		$this->setIncompleteTestUUID($this->genUUID());
		$this->setIncompleteTimeStarted($timeStarted);
		
		return true;
	}
	
	public function resumeTest($testUUID){
		if(!$this->loadIncompleteTest($testUUID)){
			return false;
		}
		else{
			return true;
		}
	}
	
	public function answerQuestion($questionUUID, $answerUUID){
		$stmt = $this->db->prepare("INSERT INTO testData (	uuid,
															testUUID,
															questionUUID,
															answerUUID )
													VALUES (?,?,?,?)
													ON DUPLICATE KEY UPDATE
															uuid=VALUES(uuid),
															testUUID=VALUES(testUUID),
															questionUUID=VALUES(questionUUID),
															answerUUID=VALUES(answerUUID)");
		$stmt->bind_param("ssss",$this->genUUID(),$this->incompleteTestUUID,$questionUUID,$answerUUID);
		
		if(!$stmt->execute()){
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","testManager->answerQuestion()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("QUESTION UUID",$questionUUID);
			$this->log->setDetail("ANSWER UUID",$answerUUID);
			$this->log->saveEntry();
			
			$this->error = "A problem occurred saving your test data to the database. Please contact CDCMastery support for assistance.";
			
			return false;
		}
		else{
			return true;
		}
	}
	
	public function addAFSC($afscUUID){
		$this->incompleteAFSCList[] = $afscUUID;
		return true;
	}
	
	public function removeAFSC($afscUUID){
		if(($key = array_search($afscUUID, $this->incompleteAFSCList)) !== false) {
		    unset($this->incompleteAFSCList[$key]);
		    return true;
		}
		else{
			return false;
		}
	}
	
	public function addQuestion($questionUUID){
		$this->incompleteQuestionList[] = $questionUUID;
		return true;
	}
	
	public function removeQuestion($questionUUID){
		if(($key = array_search($questionUUID, $this->incompleteQuestionList)) !== false) {
			unset($this->incompleteQuestionList[$key]);
			return true;
		}
		else{
			return false;
		}
	}
	
	public function getIncompleteTestUUID(){
		return $this->incompleteTestUUID;
	}
	
	public function getIncompleteTimeStarted(){
		return $this->incompleteTimeStarted;
	}
	
	public function getIncompleteQuestionList(){
		return $this->incompleteQuestionList;
	}
	
	public function getIncompleteCurrentQuestion(){
		return $this->incompleteCurrentQuestion;
	}
	
	public function getIncompleteQuestionsAnswered(){
		return $this->incompleteQuestionsAnswered;
	}
	
	public function getIncompleteTotalQuestions(){
		return $this->incompleteTotalQuestions;
	}
	
	public function getIncompleteAFSCList(){
		return $this->incompleteAFSCList;
	}
	
	public function getIncompleteUserUUID(){
		return $this->incompleteUserUUID;
	}
	
	public function getIncompleteCombinedTest(){
		return $this->incompleteCombinedTest;
	}
	
	public function setIncompleteTestUUID($testUUID){
		$this->incompleteTestUUID = $testUUID;
		return true;
	}
	
	public function setIncompleteTimeStarted($timeStarted){
		$this->incompleteTimeStarted = $timeStarted;
		return true;
	}
	
	public function setIncompleteCurrentQuestion($currentQuestion){
		$this->incompleteCurrentQuestion = $currentQuestion;
		return true;
	}
	
	public function setIncompleteQuestionsAnswered($questionsAnswered){
		$this->incompleteQuestionsAnswered = $questionsAnswered;
		return true;
	}
	
	public function setIncompleteTotalQuestions($totalQuestions){
		$this->incompleteTotalQuestions = $totalQuestions;
		return true;
	}
	
	public function setIncompleteUserUUID($userUUID){
		$this->incompleteUserUUID = $userUUID;
		return true;
	}
	
	public function setIncompleteCombinedTest($combinedTest){
		$this->incompleteCombinedTest = $combinedTest;
		return true;
	}
	
	public function __destruct(){
		if(!empty($this->incompleteTestUUID)){
			$this->saveIncompleteTest();
		}
		
		parent::__destruct();
	}
}