<?php

class testManager extends CDCMastery
{
	public $db;
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
	public $testArchived;		//int 1
	
	/*
	 * testData
	 */
	
	public $testData;			//two dimensional array
	
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
	
	public $incompletePercentComplete;
	
	public $testArchiveArray;			//Array for holding Test UUID's
	
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

	public function listUserTests($userUUID,$limit=false,$historyOrderBy=false){
		if($limit && is_int($limit)){
			if($historyOrderBy){
				$stmt = $this->db->prepare("SELECT 	uuid,
												userUUID,
												afscList,
												totalQuestions,
												questionsMissed,
												testScore,
												testTimeStarted,
												testTimeCompleted,
												testArchived
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY afscList, testTimeCompleted DESC
										LIMIT 0, ?");
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
												testArchived
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY testTimeCompleted DESC
										LIMIT 0, ?");
			}
			
			$stmt->bind_param("si",$userUUID,$limit);
		}
		else{
			if($historyOrderBy){
				$stmt = $this->db->prepare("SELECT 	uuid,
												userUUID,
												afscList,
												totalQuestions,
												questionsMissed,
												testScore,
												testTimeStarted,
												testTimeCompleted,
												testArchived
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY afscList, testTimeCompleted DESC");
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
												testArchived
										FROM testHistory
										WHERE userUUID = ?
										ORDER BY testTimeCompleted DESC");
			}
				
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
								$testArchived);
			
			while($stmt->fetch()){
				$testArray[$uuid]['userUUID'] = $resUserUUID;
				$testArray[$uuid]['afscList'] = unserialize($afscList);
				$testArray[$uuid]['totalQuestions'] = $totalQuestions;
				$testArray[$uuid]['questionsMissed'] = $questionsMissed;
				$testArray[$uuid]['testScore'] = $testScore;
				$testArray[$uuid]['testTimeStarted'] = $testTimeStarted;
				$testArray[$uuid]['testTimeCompleted'] = $testTimeCompleted;
				$testArray[$uuid]['testArchived'] = $testArchived;
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
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USER_LIST_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("USER UUID",$userUUID);
			$this->log->saveEntry();

			return false;
		}
	}
	
	public function listArchivableTests(){
		$this->listArchivableTestsAFSC();
		$this->listArchivableTestsDate();
		
		if(!empty($this->testArchiveArray)){
			return $this->testArchiveArray;
		}
		else{
			return false;
		}
	}

	public function listArchivableTestsAFSC(){
		$res = $this->db->query("SELECT uuid FROM afscList WHERE afscHidden = '1'");

		$afscArray = Array();

		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$afscArray[] = $row['uuid'];
			}
		}
		$res->close();

		if(isset($afscArray) && sizeof($afscArray) > 0) {
			foreach($afscArray as $afscUUID) {
				$query = "SELECT uuid
									FROM testHistory
									WHERE afscList LIKE '%" . $afscUUID . "%'
										AND testArchived IS NULL";
				$res = $this->db->query($query);

				if ($res->num_rows > 0) {
					while ($row = $res->fetch_assoc()) {
						$this->testArchiveArray[] = $row['uuid'];
					}
				}

				$res->close();
			}

			return true;
		}
		else{
			$res->close();
			return false;
		}
	}

	public function listArchivableTestsDate(){
		$testTimeObj = new DateTime();
		$testTimeObj->modify("-2 years");
		$testTimeStart = $testTimeObj->format("Y-m-d");

		$query = "SELECT uuid
					FROM testHistory
					WHERE DATE(testTimeStarted) < '".$testTimeStart."'
					AND testArchived IS NULL";

		$res = $this->db->query($query);

		if($res->num_rows > 0){
			while($row = $res->fetch_assoc()){
				$this->testArchiveArray[] = $row['uuid'];
			}

			$res->close();

			return true;
		}
		else{
			$res->close();
			return false;
		}
	}
	
	public function listTests(){
		$res = $this->db->query("SELECT uuid,
										userUUID,
										afscList,
										totalQuestions,
										questionsMissed,
										testScore,
										testTimeStarted,
										testTimeCompleted,
										testArchived
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
				$testArray[$row['uuid']]['testArchived'] = $row['testArchived'];
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
											testArchived
									FROM testHistory
									WHERE uuid = ?");
		$stmt->bind_param("s",$uuid);
		
		if($stmt->execute()){
			$stmt->bind_result($uuid,$userUUID,$afscList,$totalQuestions,$questionsMissed,$testScore,$testTimeStarted,$testTimeCompleted,$testArchived);
			$stmt->fetch();
			$stmt->close();

			$this->uuid = $uuid;
			$this->userUUID = $userUUID;
			$this->afscList = unserialize($afscList);
			$this->totalQuestions = $totalQuestions;
			$this->questionsMissed = $questionsMissed;
			$this->testScore = $testScore;
			$this->testTimeStarted = $testTimeStarted;
			$this->testTimeCompleted = $testTimeCompleted;
			$this->testArchived = $testArchived;
			
			if(empty($this->uuid)){
				$this->error = "That test does not exist.";
				return false;
			}
			else {
				return true;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_TEST_LOAD");
			$this->log->setDetail("Test UUID",$uuid);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

    public function saveTest($logSuccess=true){
        $serializedAFSCList = serialize($this->afscList);

        $stmt = $this->db->prepare("INSERT INTO testHistory ( uuid,
                                                              userUUID,
                                                              afscList,
                                                              totalQuestions,
                                                              questionsMissed,
                                                              testScore,
                                                              testTimeStarted,
                                                              testTimeCompleted,
															  testArchived)
                                    VALUES (?,?,?,?,?,?,?,?,?)
                                    ON DUPLICATE KEY UPDATE uuid=VALUES(uuid),
                                                            userUUID=VALUES(userUUID),
                                                            afscList=VALUES(afscList),
                                                            totalQuestions=VALUES(totalQuestions),
                                                            questionsMissed=VALUES(questionsMissed),
                                                            testScore=VALUES(testScore),
                                                            testTimeStarted=VALUES(testTimeStarted),
                                                            testTimeCompleted=VALUES(testTimeCompleted),
                                                            testArchived=VALUES(testArchived)");

        $stmt->bind_param("sssiiissi",$this->uuid,$this->userUUID,$serializedAFSCList,$this->totalQuestions,$this->questionsMissed,$this->testScore,$this->testTimeStarted,$this->testTimeCompleted,$this->testArchived);

        if($stmt->execute()){
			$stmt->close();
            if($logSuccess) {
                $this->log->setAction("SAVE_TEST");
                $this->log->setDetail("Test UUID", $this->uuid);
                $this->log->setDetail("User UUID", $this->userUUID);
                $this->log->saveEntry();
            }

            return true;
        }
        else{
			$this->error = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_SAVE_TEST");
            $this->log->setDetail("Test UUID",$this->uuid);
            $this->log->setDetail("User UUID",$this->userUUID);
            $this->log->setDetail("AFSC List",$serializedAFSCList);
            $this->log->setDetail("Total Questions",$this->totalQuestions);
            $this->log->setDetail("Questions Missed",$this->questionsMissed);
            $this->log->setDetail("Test Score",$this->testScore);
            $this->log->setDetail("Test Time Started",$this->testTimeStarted);
            $this->log->setDetail("Test Time Completed",$this->testTimeCompleted);
			$this->log->setDetail("Test Archived",$this->testArchived);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
            $this->log->saveEntry();

            return false;
        }
    }
	
	public function loadTestData($testUUID){
		$this->testData = Array();
		
		$stmt = $this->db->prepare("SELECT	questionUUID,
											answerUUID
									FROM testData
									WHERE testUUID = ?");
		$stmt->bind_param("s",$testUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionUUID,$answerUUID);
				
			while($stmt->fetch()){
				$this->testData[$questionUUID] = $answerUUID;
			}
				
			$stmt->close();
				
			if(empty($this->testData)){
				$this->error = "There is no data for that test.";
				return false;
			}
			else{
				return true;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_TEST_LOAD_DATA");
			$this->log->setDetail("Test UUID",$testUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

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
			
			$stmt = $this->db->prepare("DELETE FROM testHistory WHERE uuid = ?");
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
				$this->log->setAction("ERROR_TEST_DELETE");
				$this->log->saveEntry();
				return false;
			}
			else{
				$this->log->setAction("TEST_DELETE");
				$this->log->saveEntry();
				return true;
			}
		}
		else{
			return false;
		}
	}
	
	public function deleteTests(array $testUUIDArray){
        foreach($testUUIDArray as $testUUID){
            $this->deleteTest($testUUID);
        }

        return true;
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
	
	public function getTestData(){
        if(empty($this->testData)){
            if($this->incompleteTestUUID){
                if($this->loadTestData($this->incompleteTestUUID)){
                    return $this->testData;
                }
                else{
                    return false;
                }
            }
            elseif($this->uuid){
                if($this->loadTestData($this->uuid)){
                    return $this->testData;
                }
                else{
                    return false;
                }
            }
			else{
				return false;
			}
        }
        else{
            return $this->testData;
        }
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

    public function getTestUUIDList($userUUID){
        $stmt = $this->db->prepare("SELECT uuid
                                    FROM testHistory
                                    WHERE userUUID = ?");

        $stmt->bind_param("s",$userUUID);

        if($stmt->execute()) {
                $stmt->bind_result($testUUID);

                while ($stmt->fetch()) {
                    $testUUIDList[] = $testUUID;
                }

				$stmt->close();

                if(!empty($testUUIDList)){
                    return $testUUIDList;
                }
                else{
                    return false;
                }
        }
        else{
			$this->error = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_GET_TEST_UUID_LIST");
            $this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->saveEntry();

            return false;
        }
    }

	public function getTestArchived(){
		return $this->testArchived;
	}
	
	public function setUUID($uuid){
		$this->uuid = $uuid;
		return true;
	}

    public function setUserUUID($userUUID){
        $this->userUUID = $userUUID;
        return true;
    }

    public function setAFSCList(array $afscList){
        $this->afscList = $afscList;
        return true;
    }

    public function setTotalQuestions($totalQuestions){
        $this->totalQuestions = $totalQuestions;
        return true;
    }

    public function setQuestionsMissed($questionsMissed){
        $this->questionsMissed = $questionsMissed;
        return true;
    }

    public function setTestScore($testScore){
        $this->testScore = $testScore;
        return true;
    }

    public function setTestTimeStarted($testTimeStarted){
        $this->testTimeStarted = $testTimeStarted;
        return true;
    }

    public function setTestTimeCompleted($testTimeCompleted){
        $this->testTimeCompleted = $testTimeCompleted;
        return true;
    }

	public function setTestArchived($testArchived){
		$this->testArchived = $testArchived;
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
			$stmt->bind_result($testUUID,$timeStarted,$currentQuestion,$questionsAnswered,$totalQuestions,$afscList,$combinedTest);
				
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
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USER_LIST_INCOMPLETE_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("ERROR",$this->error);
			$this->log->setDetail("USER UUID",$userUUID);
			$this->log->saveEntry();

			return false;
		}
	}

	public function listIncompleteTestsByUser($userUUID){
		$stmt = $this->db->prepare("SELECT testUUID FROM testManager WHERE userUUID = ?");
		$stmt->bind_param("s",$userUUID);

		if($stmt->execute()){
			$stmt->bind_result($testUUID);

			while($stmt->fetch()){
				$testArray[] = $testUUID;
			}

			$stmt->close();

			if(empty($testArray)){
				return false;
			}
			else{
				return $testArray;
			}
		}
        else{
			$this->error = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_LIST_INCOMPLETE_TESTS");
            $this->log->setDetail("MySQL Error",$this->error);
            $this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->saveEntry();

            return false;
        }
	}
	
	public function listIncompleteTests($uuidOnly = false){
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
				if($uuidOnly){
					$testArray[] = $row['testUUID'];
				}
				else{
					$testArray[$row['testUUID']]['timeStarted'] = $row['timeStarted'];
					$testArray[$row['testUUID']]['questionList'] = $row['questionList'];
					$testArray[$row['testUUID']]['currentQuestion'] = $row['currentQuestion'];
					$testArray[$row['testUUID']]['questionsAnswered'] = $row['questionsAnswered'];
					$testArray[$row['testUUID']]['totalQuestions'] = $row['totalQuestions'];
					$testArray[$row['testUUID']]['afscList'] = $row['afscList'];
					$testArray[$row['testUUID']]['userUUID'] = $row['userUUID'];
					$testArray[$row['testUUID']]['combinedTest'] = $row['combinedTest'];
				}
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
			$stmt->bind_result($incompleteTestUUID,$incompleteTimeStarted,$incompleteQuestionList,$incompleteCurrentQuestion,$incompleteQuestionsAnswered,$incompleteTotalQuestions,$incompleteAFSCList,$incompleteUserUUID,$incompleteCombinedTest);
			$stmt->fetch();
			$stmt->close();

			$this->incompleteTestUUID = $incompleteTestUUID;
			$this->incompleteTimeStarted = $incompleteTimeStarted;
			$serializedQuestionList = $incompleteQuestionList;
			$this->incompleteCurrentQuestion = $incompleteCurrentQuestion;
			$this->incompleteQuestionsAnswered = $incompleteQuestionsAnswered;
			$this->incompleteTotalQuestions = $incompleteTotalQuestions;
			$serializedAFSCList = $incompleteAFSCList;
			$this->incompleteUserUUID = $incompleteUserUUID;
			$this->incompleteCombinedTest = $incompleteCombinedTest;

            if(!empty($this->incompleteTestUUID)) {
                $this->incompleteQuestionList = unserialize($serializedQuestionList);
                $this->incompleteAFSCList = unserialize($serializedAFSCList);

                return true;
            }
            else{
                return false;
            }
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_LOAD_INCOMPLETE_TEST");
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->setDetail("Test UUID",$uuid);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

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
			$stmt->close();

			$this->log->setAction("ERROR_INCOMPLETE_TEST_SAVE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->setDetail("INCOMPLETE TEST UUID",$this->incompleteTestUUID);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			$stmt->close();
			return true;
		}
	}
	
	public function deleteTestData($testUUID,$logAction=true){
		$stmt = $this->db->prepare("DELETE FROM testData WHERE testUUID = ?");
		$stmt->bind_param("s",$testUUID);
		
		if(!$stmt->execute()){
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_TEST_DATA_DELETE");
			$this->log->setDetail("Test UUID",$testUUID);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
		else{
			$stmt->close();

			if($logAction) {
				$this->log->setAction("TEST_DATA_DELETE");
				$this->log->setDetail("Test UUID", $testUUID);
				$this->log->saveEntry();
			}

			return true;
		}
	}
	
	public function deleteIncompleteTest($allIncompleteTests=false,$testUUID=false,$userUUID=false,$deleteData=true,$logSuccess=true){
		$this->incompleteTestUUID = false;
		
		if(!$userUUID){
			$userUUID = $this->incompleteUserUUID;
		}
		
		if($allIncompleteTests){
			$incompleteTestList = $this->listIncompleteTestsByUser($userUUID);
			
			$error = false;
			
			$stmt = $this->db->prepare("DELETE FROM testManager WHERE userUUID = ?");
			$stmt->bind_param("s",$userUUID);
			
			if(!$stmt->execute()){
				$this->error = $stmt->error;
				$stmt->close();

				$this->log->setAction("ERROR_INCOMPLETE_TEST_DELETE");
				$this->log->setDetail("ERROR",$this->error);
				$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
				$this->log->setDetail("allIncompleteTests","true");
				$this->log->saveEntry();
				$error = true;
			}
			else{
				$stmt->close();

				foreach($incompleteTestList as $incompleteTestUUID){
					if(!$this->deleteTestData($incompleteTestUUID,false)){
						$error = true;
					}
				}

                if($logSuccess) {
                    $this->log->setAction("INCOMPLETE_TEST_DELETE_ALL");
                    $this->log->setDetail("Test UUID Array", serialize($incompleteTestList));
                    $this->log->saveEntry();
                }
			}
			
			if($error){
				return false;
			}
			else{
				return true;
			}
		}
		else{
			if($testUUID){
				$stmt = $this->db->prepare("DELETE FROM testManager WHERE testUUID = ?");
				$stmt->bind_param("s",$testUUID);
				
				if(!$stmt->execute()){
					$this->error = $stmt->error;
					$stmt->close();

					$this->log->setAction("ERROR_INCOMPLETE_TEST_DELETE");
					$this->log->setDetail("ERROR",$this->error);
					$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
					$this->log->setDetail("allIncompleteTests","false");
					$this->log->setDetail("Test UUID",$testUUID);
					$this->log->saveEntry();
				
					return false;
				}
				else{
                    if($logSuccess) {
                        $this->log->setAction("INCOMPLETE_TEST_DELETE");
                        $this->log->setDetail("Test UUID", $testUUID);
                        $this->log->saveEntry();
                    }
					
					$stmt->close();

                    if($deleteData) {
						if (!$this->deleteTestData($testUUID,$logSuccess)) {
							return false;
						} else {
							return true;
						}
                    }
                    else{
                        return true;
                    }
				}
			}
			else{
				return false;
			}
		}
	}
	
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
				    afscUUID IN (".$afscString.")";
								
				$res = $this->db->query($sqlQuery);
				
				if($res->num_rows > 0){
					while($row = $res->fetch_assoc()){
						$randomQuestionArray[] = $row['uuid'];
					}

					if(isset($randomQuestionArray) && is_array($randomQuestionArray) && count($randomQuestionArray) > 0) {
						$randomQuestionArrayCount = count($randomQuestionArray);

						if ($randomQuestionArrayCount < $this->maxQuestions) {
							$sliceLength = $randomQuestionArrayCount;
						}
						else {
							$sliceLength = $this->maxQuestions;
						}

						shuffle($randomQuestionArray);
						$randomQuestionArray = array_slice($randomQuestionArray, 0, $sliceLength);

						foreach ($randomQuestionArray as $randomQuestion) {
							$this->addQuestion($randomQuestion);
						}

						return true;
					}
					else{
						$this->log->setAction("ERROR_TEST_POPULATE_QUESTIONS");
						$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
						$this->log->setDetail("COMBINED TEST", "FALSE");
						$this->log->setDetail("ERROR","randomQuestionArray was not an array, wasn't set, or wasn't greater than 0");
						$this->log->saveEntry();

						$this->error = "Sorry, we were unable to populate a pool of questions for the test.";

						return false;
					}
				}
				else{
					$this->log->setAction("ERROR_TEST_POPULATE_QUESTIONS");
					$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
					$this->log->setDetail("COMBINED TEST", "TRUE");
					$this->log->setDetail("ERROR",$this->db->error);
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
					afscUUID = ?";
				
				$stmt = $this->db->prepare($query);
				$stmt->bind_param("s",$this->incompleteAFSCList[0]);
				
				if($stmt->execute()){
					$stmt->bind_result($questionUUID);
						
					while($stmt->fetch()){
						$randomQuestionArray[] = $questionUUID;
					}

					$stmt->close();

					if(isset($randomQuestionArray) && is_array($randomQuestionArray) && count($randomQuestionArray) > 0) {
						$randomQuestionArrayCount = count($randomQuestionArray);

						if($randomQuestionArrayCount < $this->maxQuestions){
							$sliceLength = $randomQuestionArrayCount;
						}
						else{
							$sliceLength = $this->maxQuestions;
						}

						shuffle($randomQuestionArray);
						$randomQuestionArray = array_slice($randomQuestionArray, 0, $sliceLength);

						foreach ($randomQuestionArray as $randomQuestion) {
							$this->addQuestion($randomQuestion);
						}

						return true;
					}
					else{
						$this->log->setAction("ERROR_TEST_POPULATE_QUESTIONS");
						$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
						$this->log->setDetail("COMBINED TEST", "FALSE");
						$this->log->setDetail("ERROR","randomQuestionArray was not an array, wasn't set, or wasn't greater than 0");
						$this->log->saveEntry();

						$this->error = "Sorry, we were unable to populate a pool of questions for the test.";

						return false;
					}
				}
				else{
					$this->error = $stmt->error;
					$stmt->close();

					$this->log->setAction("ERROR_TEST_POPULATE_QUESTIONS");
					$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
					$this->log->setDetail("Combined Test", "FALSE");
					$this->log->setDetail("MySQL Error",$this->error);
					$this->log->saveEntry();
						
					$this->error = "Sorry, we were unable to populate a pool of questions for the test.";
					return false;
				}
			}
		}
	}
	
	public function answerQuestion($testUUID, $questionUUID, $answerUUID){
		/**
		 * Fix for issue where double-clicking answers will enter false data into testing data table.  Ensure answer provided matches current question.
		 */
		$this->answer->loadAnswer($answerUUID);
		if($this->answer->getQuestionUUID() == $questionUUID) {
			$previouslyAnsweredUUID = $this->queryQuestionPreviousAnswer($questionUUID);
			$stmt = $this->db->prepare("INSERT INTO testData (	testUUID,
															questionUUID,
															answerUUID )
													VALUES (?,?,?)
													ON DUPLICATE KEY UPDATE
															answerUUID=VALUES(answerUUID)");
			$stmt->bind_param("sss", $testUUID, $questionUUID, $answerUUID);

			if (!$stmt->execute()) {
				$this->error = $stmt->error;
				$stmt->close();

				$this->log->setAction("ERROR_TEST_STORE_ANSWER");
				$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
				$this->log->setDetail("ERROR", $this->error);
				$this->log->setDetail("QUESTION UUID", $questionUUID);
				$this->log->setDetail("ANSWER UUID", $answerUUID);
				$this->log->setDetail("TEST UUID", $testUUID);
				$this->log->saveEntry();

				$this->error = "A problem occurred saving your test data to the database. Please contact CDCMastery support for assistance.";

				return false;
			} else {
				$stmt->close();

				if (!$previouslyAnsweredUUID) {
					$this->incompleteQuestionsAnswered++;
				}

				return true;
			}
		}
		else{
			return false;
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

    public function outputQuestionData($questionUUID,$testCompleted = false){
		$questionFOUO = $this->question->queryQuestionFOUO($questionUUID);

		if($this->question->loadQuestion($questionUUID)){
            $output = "";

            if($testCompleted){
                $output .= "<div class=\"testCompletedText systemMessages\">You have completed this test.
                            <a href=\"/test/score/".$this->incompleteTestUUID."\">Click Here</a> to score your test.</div>";
            }

			$output .= "<div class=\"smallSectionTitle\">question</div>";
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
					
					$output .= '<li p="'.$answerUUID.'" id="answer'.$i.'" class="'.$answerClass.'" style="cursor: pointer;">'.$answerData['answerText'].'</li>';
					$i++;
				}
				$output .= "</ul>";
				$output .= "</div>";
				$output .= "<div class=\"testProgress\">
								Question <strong>".$this->incompleteCurrentQuestion."</strong> of 
								<strong>".$this->incompleteTotalQuestions."</strong>
								<br>
								AFSC: <strong>".$this->afsc->getAFSCName($this->question->getAFSCUUID())."</strong>
							</div>";
				$output .= "<br style=\"clear:both;\">";
				if($questionFOUO){
					$output .= "<div class=\"text-center\" style=\"font-weight: 900\">FOR OFFICIAL USE ONLY</div>";
				}
				$output .= "<br style=\"clear:both;\"><div class=\"text-center\"><a href=\"/report/question/".$questionUUID."\">Report This Question</a></div>";
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
			$stmt->fetch();
			$stmt->close();
			
			if($answerUUID){
				return $answerUUID;
			}
			else{
				return false;
			}
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_TEST_RETRIEVE_PREVIOUS_ANSWER");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("Test UUID",$this->incompleteTestUUID);
			$this->log->setDetail("Question UUID",$questionUUID);
			$this->log->setDetail("MySQL Error",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}
	
	/*
	 * Test Navigation
	 */
	
	public function navigateFirstQuestion(){
		$this->incompleteCurrentQuestion = 1;
        $this->saveIncompleteTest();
		return true;
	}
	
	public function navigatePreviousQuestion(){
		if($this->incompleteCurrentQuestion > 1){
			$this->incompleteCurrentQuestion--;
		}
		else{
			$this->incompleteCurrentQuestion = 1;
		}

        $this->saveIncompleteTest();
		return true;
	}
	
	public function navigateNextQuestion(){
		if($this->incompleteCurrentQuestion < $this->incompleteTotalQuestions){
			$this->incompleteCurrentQuestion++;
		}
		else{
			$this->incompleteCurrentQuestion = $this->incompleteTotalQuestions;
		}

        $this->saveIncompleteTest();
		return true;
	}
	
	public function navigateLastQuestion(){
		$this->incompleteCurrentQuestion = $this->incompleteTotalQuestions;
        $this->saveIncompleteTest();
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

            $this->saveIncompleteTest();
			
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
	
	public function getIncompletePercentComplete(){
		if($this->incompleteTotalQuestions && $this->incompleteQuestionsAnswered){
			$percentComplete = (($this->incompleteQuestionsAnswered / $this->incompleteTotalQuestions) * 100);
			
			return intval($percentComplete,2) . "%";
		}
		else{
			return "0%";
		}
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
		parent::__destruct();
	}
}