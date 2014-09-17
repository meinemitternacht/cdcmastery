<?php
/*
 * This class provides an interface for taking tests and reviewing previous tests.
 * All other functions for changing previous test variables will be administrative functions.
 */

class testManager extends CDCMastery
{
	protected $db;
	protected $log;
	protected $afsc;
	protected $answer;
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
	
	public function __construct(mysqli $db, log $log, afsc $afsc){
		$this->db = $db;
		$this->log = $log;
		$this->afsc = $afsc;
		$this->answer = new answerManager($this->db, $this->log);
		$this->question = new questionManager($this->db, $this->log, $this->afsc, $this->answer);
	}
	
	/*
	 * testHistory Table Related Functions
	 */
	
	public function listUserTests($userUUID,$limit=false){
		if($limit && is_int($limit)){
			$stmt = $this->db->prepare("SELECT 	uuid,
												userUUID,
												afscList,
												totalQuestions,
												questionsMissed,
												testScore,
												testTimeStarted,
												testTimeCompleted,
												oldTestID
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY testTimeStarted DESC
										LIMIT 0, ?");
			
			$stmt->bind_param("si",$userUUID,$limit);
		}
		else{
			$stmt = $this->db->prepare("SELECT 	uuid,
												userUUID,
												afscList,
												totalQuestions,
												questionsMissed,
												testScore,
												testTimeStarted,
												testTimeCompleted,
												oldTestID
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY testTimeStarted DESC");
				
			$stmt->bind_param("s",$userUUID);
		}
		
		if($stmt->execute()){
			$stmt->bind_result(	$uuid,
								$resUserUUID,
								$afscList,
								$totalQuestions,
								$questionsMissed,
								$testScore,
								$testTimeStarted,
								$testTimeCompleted,
								$oldTestID);
			
			while($stmt->fetch()){
				$testArray[$uuid]['userUUID'] = $resUserUUID;
				$testArray[$uuid]['afscList'] = unserialize($afscList);
				$testArray[$uuid]['totalQuestions'] = $totalQuestions;
				$testArray[$uuid]['questionsMissed'] = $questionsMissed;
				$testArray[$uuid]['testScore'] = $testScore;
				$testArray[$uuid]['testTimeStarted'] = $testTimeStarted;
				$testArray[$uuid]['testTimeCompleted'] = $testTimeCompleted;
				$testArray[$uuid]['oldTestID'] = $oldTestID;
			}
			
			$stmt->close();
			
			if(!empty($testArray)){
				return $testArray;
			}
			else{
				$this->error = "There are no tests by this user in the database.";
				return false;
			}
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","testManager->listUserTests()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("USER UUID",$userUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return false;
		}
	}
	
	public function listTests($userUUID=false){
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
	
	public function deleteTest($testUUID){
		if($this->loadTest($testUUID)){
			$this->log->setDetail("TEST UUID",$testUUID);
			$this->log->setDetail("USER UUID",$this->getUserUUID());
			$this->log->setDetail("SCORE", $this->getTestScore());
			
			$stmt = $this->db->prepare("DELETE FROM testData WHERE testUUID = ?");
			$stmt->bind_param("s",$testUUID);
			
			$error = false;
			
			if(!$stmt->execute()){
				$this->log->setDetail("DELETE testData","FAILURE");
				$error = true;
			}
			else{
				$this->log->setDetail("DELETE testData","SUCCESS");
			}
			
			$stmt->close();
			
			$stmt = $this->db->prepare("DELETE FROM testHistory WHERE testUUID = ?");
			$stmt->bind_param("s",$testUUID);
			
			if(!$stmt->execute()){
				$this->log->setDetail("DELETE testHistory","FAILURE");
				$error = true;
			}
			else{
				$this->log->setDetail("DELETE testHistory","SUCCESS");
			}
			
			$stmt->close();
			
			if($error){
				$this->log->setAction("DELETE_TEST_ERROR");
				$this->log->saveEntry();
				return false;
			}
			else{
				$this->log->setAction("DELETE_TEST");
				$this->log->saveEntry();
				return true;
			}
		}
	}
	
	public function deleteTests($testUUIDArray){
		if(is_array($testUUIDArray) && !empty($testUUIDArray)){
			foreach($testUUIDArray as $testUUID){
				$this->deleteTest($testUUID);
			}
			
			return true;
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
	public function listUserIncompleteTests($userUUID,$limit=false){
		if($limit && is_int($limit)){
			$stmt = $this->db->prepare("SELECT 	testUUID,
												timeStarted,
												currentQuestion,
												questionsAnswered,
												totalQuestions,
												afscList,
												combinedTest
										FROM testManager
										WHERE userUUID = ?
										ORDER BY timeStarted DESC
										LIMIT 0, ?");
				
			$stmt->bind_param("si",$userUUID,$limit);
		}
		else{
			$stmt = $this->db->prepare("SELECT 	testUUID,
												timeStarted,
												currentQuestion,
												questionsAnswered,
												totalQuestions,
												afscList,
												combinedTest
										FROM testManager
										WHERE userUUID = ?
										ORDER BY timeStarted DESC");
	
			$stmt->bind_param("s",$userUUID);
		}
	
		if($stmt->execute()){
			$stmt->bind_result(	$testUUID,
								$timeStarted,
								$currentQuestion,
								$questionsAnswered,
								$totalQuestions,
								$afscList,
								$combinedTest);
				
			while($stmt->fetch()){
				$testArray[$testUUID]['timeStarted'] = $timeStarted;
				$testArray[$testUUID]['currentQuestion'] = $currentQuestion;
				$testArray[$testUUID]['questionsAnswered'] = $questionsAnswered;
				$testArray[$testUUID]['totalQuestions'] = $totalQuestions;
				$testArray[$testUUID]['afscList'] = unserialize($afscList);
				$testArray[$testUUID]['combinedTest'] = $combinedTest;
			}
				
			$stmt->close();
				
			if(!empty($testArray)){
				return $testArray;
			}
			else{
				$this->error = "There are no incomplete tests by this user in the database.";
				return false;
			}
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","testManager->listUserIncompleteTests()");
			$this->log->setDetail("ERROR",$stmt->error);
			$this->log->setDetail("USER UUID",$userUUID);
			$this->log->saveEntry();
				
			$stmt->close();
			return false;
		}
	}
	
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
				$serializedQuestionList = $incompleteQuestionList;
				$this->incompleteCurrentQuestion = $incompleteCurrentQuestion;
				$this->incompleteQuestionsAnswered = $incompleteQuestionsAnswered;
				$this->incompleteTotalQuestions = $incompleteTotalQuestions;
				$serializedAFSCList = $incompleteAFSCList;
				$this->incompleteUserUUID = $incompleteUserUUID;
				$this->incompleteCombinedTest = $incompleteCombinedTest;
			}
			
			$this->incompleteQuestionList = unserialize($serializedQuestionList);
			$this->incompleteAFSCList = unserialize($serializedAFSCList);
			
			return true;
		}
		else{
			return false;
		}
	}
	
	public function saveIncompleteTest(){		
		$serializedQuestionList = serialize($this->incompleteQuestionList);
		$serializedAFSCList = serialize($this->incompleteAFSCList);
		
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
										$serializedQuestionList,
										$this->incompleteCurrentQuestion,
										$this->incompleteQuestionsAnswered,
										$this->incompleteTotalQuestions,
										$serializedAFSCList,
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
	
	/*
	 * @todo delete from testData table
	 */
	/*
	public function deleteIncompleteTest($allIncompleteTests=false,$testUUID=false){
		if($allIncompleteTests){
			$error = false;
			
			$stmt = $this->db->prepare("DELETE FROM testManager WHERE userUUID = ?");
			$stmt->bind_param("s",$this->incompleteUserUUID);
			
			if(!$stmt->execute()){
				$this->log->setAction("MYSQL_ERROR");
				$this->log->setDetail("ERROR",$stmt->error);
				$this->log->setDetail("CALLING FUNCTION","testManager->deleteIncompleteTest()");
				$this->log->setDetail("allIncompleteTests","true");
				$this->log->saveEntry();
				$error = true;
			}
			else{
				$this->error = "Your incomplete tests were deleted successfully.";
			}
			
			if($error){
				$this->error = "A problem was encountered while deleting your incomplete tests from the database.  Please contact CDCMastery support for assistance.";
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
				
					$this->error = "A problem was encountered while deleting your incomplete test from the database.  Please contact CDCMastery support for assistance.";
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
					
						$this->error = "A problem was encountered while deleting your incomplete test from the database.  Please contact CDCMastery support for assistance.";
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
	*/
	public function newTest(){
		$this->setIncompleteUserUUID($_SESSION['userUUID']);
		$this->setIncompleteTestUUID($this->genUUID());
		$this->setIncompleteTimeStarted(date("Y-m-d H:i:s",time()));
		$this->setIncompleteCurrentQuestion(1);
		$this->setIncompleteTotalQuestions(0);
		$this->setIncompleteQuestionsAnswered(0);
		
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
	
	public function populateQuestions(){
		if(!$this->incompleteAFSCList){
			$this->error = "No AFSCs were selected.";
			return false;
		}
		else{
			if($this->incompleteCombinedTest){
				$afscString = "'" . implode("','",$this->incompleteAFSCList) . "'";
				
				$sqlQuery = "
				SELECT    
				    uuid
				FROM 
				    questionData
				WHERE
				    afscUUID IN (".$afscString.") ORDER BY Rand() LIMIT 0, ".$this->maxQuestions;
								
				$res = $this->db->query($sqlQuery);
				
				if($res->num_rows > 0){
					while($row = $res->fetch_assoc()){
						$this->addQuestion($row['uuid']);
					}
					
					return true;
				}
				else{
					$this->log->setAction("MYSQL_ERROR");
					$this->log->setDetail("CALLING FUNCTION", "testManager->populateQuestions()");
					$this->log->setDetail("COMBINED TEST", "TRUE");
					$this->log->setDetail("ERROR",$db->error);
					$this->log->saveEntry();
					
					$this->error = "Sorry, we were unable to populate a pool of questions for the test.";
					return false;
				}
			}
			else{
				$query = "
				SELECT
					uuid
				FROM
					questionData
				WHERE
					afscUUID = ? ORDER BY Rand() LIMIT 0, ?;";
				
				$stmt = $this->db->prepare($query);
				$stmt->bind_param("si",$this->incompleteAFSCList[0],$this->maxQuestions);
				
				if($stmt->execute()){
					$stmt->bind_result($questionUUID);
						
					while($stmt->fetch()){
						$this->addQuestion($questionUUID);
					}
					
					return true;
				}
				else{
					$this->log->setAction("MYSQL_ERROR");
					$this->log->setDetail("CALLING FUNCTION", "testManager->populateQuestions()");
					$this->log->setDetail("COMBINED TEST", "FALSE");
					$this->log->setDetail("ERROR",$stmt->error);
					$this->log->saveEntry();
						
					$this->error = "Sorry, we were unable to populate a pool of questions for the test.";
					return false;
				}
			}
		}
	}
	
	public function answerQuestion($questionUUID, $answerUUID){
		$previouslyAnsweredUUID = $this->queryQuestionPreviousAnswer($questionUUID);
		$rowUUID = $this->genUUID();
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
		$stmt->bind_param("ssss",$rowUUID,$this->incompleteTestUUID,$questionUUID,$answerUUID);
		
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
			if(!$previouslyAnsweredUUID){
				$this->incompleteQuestionsAnswered++;
			}
			
			return true;
		}
	}
	
	public function addAFSC($afscUUID){
		$this->incompleteAFSCList[] = $afscUUID;
		
		if(count($this->incompleteAFSCList) > 1){
			$this->setIncompleteCombinedTest(true);
		}
		else{
			$this->setIncompleteCombinedTest(false);
		}
		
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
		echo $questionUUID;
		$this->incompleteTotalQuestions++;
		return true;
	}
	
	public function removeQuestion($questionUUID){
		if(($key = array_search($questionUUID, $this->incompleteQuestionList)) !== false) {
			unset($this->incompleteQuestionList[$key]);
			$this->incompleteTotalQuestions--;
			return true;
		}
		else{
			return false;
		}
	}
	
	public function outputQuestionData($questionUUID){
		$questionFOUO = $this->question->queryQuestionFOUO($questionUUID);
		
		if($this->question->loadQuestion($questionUUID)){
			$output = "<div class=\"smallSectionTitle\">question</div>";
			$output .= "<div class=\"displayQuestion\">";
			$output .= $this->question->getQuestionText();
			$output .= "</div><br style=\"clear:both;\">";
			
			$this->answer->setFOUO($questionFOUO);
			$this->answer->setQuestionUUID($questionUUID);
			
			$answerArray = $this->answer->listAnswersByQuestion();
			
			if($answerArray){
				$output .= "<div class=\"smallSectionTitle\">answers</div>";
				$output .= "<div id=\"list-answers\">";
				$output .= "<ul>";
				$i=1;
				foreach($answerArray as $answerUUID => $answerData){
					$previouslyAnsweredUUID = $this->queryQuestionPreviousAnswer($questionUUID);
					
					if(!empty($previouslyAnsweredUUID) && $previouslyAnsweredUUID == $answerUUID){
						$answerClass = "list-answer-selected";
					}
					else{
						$answerClass = "list-answer-normal";
					}
					
					$output .= '<li p="'.$answerUUID.'" id="answer'.$i.'" class="'.$answerClass.'">'.$answerData['answerText'].'</li>';
					$i++;
				}
				$output .= "</ul>";
				$output .= "</div>";
				$output .= "<div class=\"testProgress\">
								Question <strong>".$this->incompleteCurrentQuestion."</strong> of 
								<strong>".$this->incompleteTotalQuestions."</strong>
							</div>";
				$output .= "<br style=\"clear:both;\">";
			}
			else{
				$output = "Sorry, we could not load the answers from the database.";
			}
		}
		else{
			$output = "Sorry, we could not load that question from the database.";
		}
		
		return $output;
	}
	
	public function queryQuestionPreviousAnswer($questionUUID){
		$stmt = $this->db->prepare("SELECT answerUUID FROM testData WHERE questionUUID = ? AND testUUID = ?");
		$stmt->bind_param("ss",$questionUUID, $this->incompleteTestUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($answerUUID);
			
			while($stmt->fetch()){
				$tempAnswerUUID = $answerUUID;
			}
			
			$stmt->close();
			
			if(isset($tempAnswerUUID)){
				return $tempAnswerUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->log->setAction("MYSQL_ERROR");
			$this->log->setDetail("CALLING FUNCTION","testManager->queryQuestionPreviousAnswer()");
			$this->log->setDetail("TEST UUID",$this->incompleteTestUUID);
			$this->log->setDetail("QUESTION UUID",$questionUUID);
			$this->log->saveEntry();
			
			$stmt->close();
			return false;
		}
	}
	
	/*
	 * Test Navigation
	 */
	
	public function navigateFirstQuestion(){
		$this->incompleteCurrentQuestion = 1;
		return true;
	}
	
	public function navigatePreviousQuestion(){
		if($this->incompleteCurrentQuestion > 1){
			$this->incompleteCurrentQuestion--;
		}
		else{
			$this->incompleteCurrentQuestion = 1;
		}
		
		return true;
	}
	
	public function navigateNextQuestion(){
		if($this->incompleteCurrentQuestion < $this->incompleteTotalQuestions){
			$this->incompleteCurrentQuestion++;
		}
		else{
			$this->incompleteCurrentQuestion = $this->incompleteTotalQuestions;
		}
		
		return true;
	}
	
	public function navigateLastQuestion(){
		$this->incompleteCurrentQuestion = $this->incompleteTotalQuestions;
		return true;
	}
	
	public function navigateSpecificQuestion($questionNumber){
		if(is_int($questionNumber)){
			if($questionNumber > $this->incompleteTotalQuestions){
				$this->incompleteCurrentQuestion = $this->incompleteTotalQuestions;
			}
			elseif($questionNumber < 1){
				$this->incompleteCurrentQuestion = 1;
			}
			else{
				$this->incompleteCurrentQuestion = $questionNumber;
			}
			
			return true;
		}
		else{
			return false;
		}
	}
	
	/*
	 * Getters and setters
	 */
	
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
	
	/*
	 * Miscellaneous
	 */
	
	public function getMigratedTestUUID($oldTestID){
		$stmt = $this->db->prepare("SELECT uuid FROM testHistory WHERE oldTestID = ?");
		$stmt->bind_param("s",$oldTestID);
		
		if($stmt->execute()){
			$stmt->bind_result($uuid);
			
			while($stmt->fetch()){
				$ret = $uuid;
			}
			
			if(isset($ret)){
				return $ret;
			}
			else{
				return false;
			}
		}
		else{
			$this->log->setAction("DATABASE_ERROR");
			$this->log->setDetail("MySQL Provided Error",$stmt->error);
			$this->log->setDetail("Calling Function","testManager->getMigratedTestUUID()");
			$this->log->setDetail("oldTestID",$oldTestID);
			$this->log->saveEntry();
		}
	}
	
	public function __destruct(){
		if(!empty($this->incompleteTestUUID)){
			$this->saveIncompleteTest();
		}
		
		parent::__destruct();
	}
}