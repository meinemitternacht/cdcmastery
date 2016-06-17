<?php

class userStatistics extends CDCMastery
{
	protected $db;
	protected $log;
	protected $roles;
	protected $memcache;
	
	public $error;
	
	public $userUUID;
	
	/*
	 * System Statistics
	 */
	public $logEntries;
    public $ipAddressList;
	
	/*
	 * Testing Statistics
	 */
	public $averageScore;
	public $completedTests;
	public $incompleteTests;
	public $totalTests;
	public $questionsAnswered;
	public $questionsMissed;
    public $latestTestScore;
	public $questionsMissedAcrossTests;
	
	/*
	 * Subordinate Users
	 */
	public $supervisorAssociations;
	public $trainingManagerAssociations;
	
	/*
	 * Parent Users
	 */
	public $userSupervisors;
	public $userTrainingManagers;
	
	/*
	 * AFSC Associations
	 */
	public $afscAssociations;
	public $pendingAFSCAssociations;
	
	/*
	 * Row Counts
	 */
	public $countAFSCAssociations;
	public $countPendingAFSCAssociations;
	public $countSupervisorSubordinates;
	public $countTrainingManagerSubordinates;
	public $countUserSupervisors;
	public $countUserTrainingManagers;

	public function __construct(mysqli $db, log $log, roles $roles, Memcache $memcache){
		$this->db = $db;
		$this->log = $log;
		$this->roles = $roles;
		$this->memcache = $memcache;
	}

	public function getCacheHash($functionName,$var1=false,$var2=false,$var3=false,$ignoreCurrentUser=false){
		if($var1 !== false){
			if($var2 !== false){
				if($var3 !== false){
					$hashVal = $functionName . $var1 . $var2 . $var3;
				}
				else{
					$hashVal = $functionName . $var1 . $var2;
				}
			}
			else{
				$hashVal = $functionName . $var1;
			}
		}
		else{
			$hashVal = $functionName;
		}

		if(!$ignoreCurrentUser){
			if(!empty($this->userUUID)){
				$hashVal = $hashVal . $this->userUUID;
			}
		}

		$cacheHash = md5($hashVal);

		return $cacheHash;
	}

	/*
	 * Cache Functions
	 */
	public function deleteUserStatsCacheVal($functionName,$var1=false,$var2=false,$var3=false,$ignoreCurrentUser=false){
		if($this->memcache->delete($this->getCacheHash($functionName,$var1,$var2,$var3,$ignoreCurrentUser))){
			return true;
		}
		else{
			return false;
		}
	}

	public function setUserStatsCacheVal($functionName,$cacheValue,$cacheTTL,$var1=false,$var2=false,$var3=false,$ignoreCurrentUser=false){
		$cacheHash = $this->getCacheHash($functionName,$var1,$var2,$var3,$ignoreCurrentUser);
		$this->memcache->delete($cacheHash);
		if($this->memcache->add($cacheHash,$cacheValue,NULL,$cacheTTL)){
			return true;
		}
		else{
			return false;
		}
	}

	public function getUserStatsCacheVal($functionName,$var1=false,$var2=false,$var3=false,$ignoreCurrentUser=false){
		$cacheHash = $this->getCacheHash($functionName,$var1,$var2,$var3,$ignoreCurrentUser);

		return $this->memcache->get($cacheHash);
	}
	
	/*
	 * Entry Functions
	 */
	
	public function getLogEntries(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryLogEntries()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->logEntries,$this->getCacheTTL(4));
					return $this->logEntries;
				}
			}
		}
	}
	
	public function getAverageScore(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryAverageScore()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->averageScore,$this->getCacheTTL(4));
					return $this->averageScore;
				}
			}
		}
	}
	
	public function getCompletedTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryCompletedTests()){
				return false;
			}
			else{
				$this->setUserStatsCacheVal(__FUNCTION__,$this->completedTests,$this->getCacheTTL(4));
				return $this->completedTests;
			}
		}
	}

	/**
	 * @return bool|int
	 */
	public function getIncompleteTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if(!$this->queryIncompleteTests()){
				return false;
			}
			else{
				return $this->incompleteTests;
			}
		}
	}

    public function getIPAddresses(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryIPAddresses()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->ipAddressList,$this->getCacheTTL(4));
					return $this->ipAddressList;
				}
			}
		}
    }

    public function getLatestTestScore(){
        $this->latestTestScore = 0;
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryLatestTestScore()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->latestTestScore,$this->getCacheTTL(4));
					return $this->latestTestScore;
				}
			}
		}
    }
	
	public function getTotalTests(){
		return ($this->getCompletedTests() + $this->getIncompleteTests());
	}
	
	public function getQuestionsAnswered(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryQuestionsAnswered()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->questionsAnswered,$this->getCacheTTL(2));
					return $this->questionsAnswered;
				}
			}
		}
	}
	
	public function getQuestionsMissed(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryQuestionsMissed()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->questionsMissed,$this->getCacheTTL(4));
					return $this->questionsMissed;
				}
			}
		}
	}

	public function getQuestionsMissedAcrossTests(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryQuestionsMissedAcrossTests()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->questionsMissedAcrossTests,$this->getCacheTTL(4));
					return $this->questionsMissedAcrossTests;
				}
			}
		}
	}

	/**
	 * @return array|bool
	 */
	
	public function getSupervisorAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if($this->roles->verifyUserRole($this->userUUID) == "supervisor") {
					if (!$this->querySupervisorAssociations()) {
						return false;
					} else {
						$this->setUserStatsCacheVal(__FUNCTION__, $this->supervisorAssociations, $this->getCacheTTL(99));
						return $this->supervisorAssociations;
					}
				}
				else{
					return false;
				}
			}
		}
	}
	
	public function getTrainingManagerAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if($this->roles->verifyUserRole($this->userUUID) == "trainingManager") {
					if (!$this->queryTrainingManagerAssociations()) {
						return false;
					} else {
						$this->setUserStatsCacheVal(__FUNCTION__, $this->trainingManagerAssociations, $this->getCacheTTL(99));
						return $this->trainingManagerAssociations;
					}
				}
				else{
					return false;
				}
			}
		}
	}
	
	public function getUserSupervisors(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryUserSupervisors()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->userSupervisors,$this->getCacheTTL(99));
					return $this->userSupervisors;
				}
			}
		}
	}
	
	public function getUserTrainingManagers(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryUserTrainingManagers()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->userTrainingManagers,$this->getCacheTTL(99));
					return $this->userTrainingManagers;
				}
			}
		}
	}
	
	public function getAFSCAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryAFSCAssociations()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->afscAssociations,$this->getCacheTTL(99));
					return $this->afscAssociations;
				}
			}
		}
	}
	
	public function getPendingAFSCAssociations(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryPendingAFSCAssociations()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->pendingAFSCAssociations,$this->getCacheTTL(99));
					return $this->pendingAFSCAssociations;
				}
			}
		}
	}
	
	public function getAFSCAssociationCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountAFSCAssociations()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countAFSCAssociations,$this->getCacheTTL(99));
					return $this->countAFSCAssociations;
				}
			}
		}
	}
	
	public function getPendingAFSCAssociationCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountPendingAFSCAssociations()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countPendingAFSCAssociations,$this->getCacheTTL(99));
					return $this->countPendingAFSCAssociations;
				}
			}
		}
	}
	
	public function getSupervisorSubordinateCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountSupervisorSubordinates()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countSupervisorSubordinates,$this->getCacheTTL(99));
					return $this->countSupervisorSubordinates;
				}
			}
		}
	}
	
	public function getTrainingManagerSubordinateCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountTrainingManagerSubordinates()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countTrainingManagerSubordinates,$this->getCacheTTL(99));
					return $this->countTrainingManagerSubordinates;
				}
			}
		}
	}
	
	public function getUserSupervisorCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountUserSupervisors()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countUserSupervisors,$this->getCacheTTL(99));
					return $this->countUserSupervisors;
				}
			}
		}
	}
	
	public function getUserTrainingManagerCount(){
		if(!$this->userUUID){
			return false;
		}
		else{
			if($this->getUserStatsCacheVal(__FUNCTION__)){
				return $this->getUserStatsCacheVal(__FUNCTION__);
			}
			else{
				if(!$this->queryCountUserTrainingManagers()){
					return false;
				}
				else{
					$this->setUserStatsCacheVal(__FUNCTION__,$this->countUserTrainingManagers,$this->getCacheTTL(99));
					return $this->countUserTrainingManagers;
				}
			}
		}
	}
	
	/*
	 * Queries
	 */

	public function queryQuestionOccurrences($userUUID,$questionUUID){
		if($this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$questionUUID,false,true)){
			return $this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$questionUUID,false,true);
		}
		else{
			$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData LEFT JOIN testHistory ON testHistory.uuid = testData.testUUID WHERE testHistory.userUUID = ? AND questionUUID = ?");

			$stmt->bind_param("ss",$userUUID, $questionUUID);

			if($stmt->execute()){
				$stmt->bind_result($count);
				$stmt->fetch();
				$stmt->close();
				$this->setUserStatsCacheVal(__FUNCTION__,$count,$this->getCacheTTL(3),$userUUID,$questionUUID,false,true);

				return $count;
			}
			else{
				return false;
			}
		}
	}

	public function queryAnswerOccurrences($userUUID,$answerUUID){
		if($this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$answerUUID,false,true)){
			return $this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$answerUUID,false,true);
		}
		else{
			$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData LEFT JOIN testHistory ON testHistory.uuid = testData.testUUID WHERE testHistory.userUUID = ? AND answerUUID = ?");

			$stmt->bind_param("ss",$userUUID, $answerUUID);

			if($stmt->execute()){
				$stmt->bind_result($count);
				$stmt->fetch();
				$stmt->close();
				$this->setUserStatsCacheVal(__FUNCTION__,$count,$this->getCacheTTL(3),$userUUID,$answerUUID,false,true);

				return $count;
			}
			else{
				return false;
			}
		}
	}

	public function queryIPAddresses(){
        $stmt = $this->db->prepare("SELECT DISTINCT(ip) FROM systemLog WHERE userUUID = ?");
        $stmt->bind_param("s",$this->userUUID);

        if($stmt->execute()){
            $stmt->bind_result($ipAddress);

            while($stmt->fetch()){
                $this->ipAddressList[] = $ipAddress;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("ERROR_USERSTATS_QUERY_IP_ADDRESSES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }
	
	public function queryLogEntries(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			
			while($stmt->fetch()){
				$this->logEntries = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_LOG_ENTRIES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryAverageScore(){
		$stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($averageScore);
		
			while($stmt->fetch()){
				$this->averageScore = round($averageScore,2);
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_AVERAGE_SCORE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryCompletedTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			
			while($stmt->fetch()){
				$this->completedTests = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COMPLETED_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
			
			return false;
		}
	}
	
	public function queryIncompleteTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testManager WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->incompleteTests = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_INCOMPLETE_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}

    public function queryLatestTestScore(){
        $stmt = $this->db->prepare("SELECT testScore FROM `testHistory` WHERE userUUID = ? ORDER BY testTimeCompleted DESC LIMIT 1");
		$stmt->bind_param("s",$this->userUUID);

		if($stmt->execute()){
            $stmt->bind_result($testScore);

            while($stmt->fetch()){
                $this->latestTestScore = $testScore;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("ERROR_USERSTATS_QUERY_LATEST_TEST_SCORE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }
	
	public function queryQuestionsAnswered(){
		$stmt = $this->db->prepare("SELECT SUM(totalQuestions) AS questionsAnswered FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsAnswered);
		
			while($stmt->fetch()){
				$this->questionsAnswered = $questionsAnswered;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_ANSWERED");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryQuestionsMissed(){
		$stmt = $this->db->prepare("SELECT SUM(questionsMissed) AS questionsMissed FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsMissed);
		
			while($stmt->fetch()){
				$this->questionsMissed = $questionsMissed;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_MISSED");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}

	public function queryQuestionsMissedAcrossTests(){
		$query = "SELECT
						testData.questionUUID,
						COUNT(testData.answerUUID) AS wrongAnswerCount
					FROM testData
						LEFT JOIN answerData ON answerData.uuid=testData.answerUUID
						LEFT JOIN testHistory ON testHistory.uuid=testData.testUUID
					WHERE testHistory.userUUID = ?
						AND answerData.answerCorrect=0
					GROUP BY testData.questionUUID
					HAVING COUNT(testData.answerUUID) > 0
					ORDER BY wrongAnswerCount DESC
					LIMIT 0, 20";

		$stmt = $this->db->prepare($query);

		$stmt->bind_param("s",$this->userUUID);

		if($stmt->execute()){
			$stmt->bind_result($questionUUID,$wrongAnswerCount);

			while($stmt->fetch()){
				$this->questionsMissedAcrossTests[$questionUUID] = $wrongAnswerCount;
			}

			$stmt->close();

			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_MISSED_ACROSS_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();

			return false;
		}
	}
	
	public function querySupervisorAssociations(){
		$stmt = $this->db->prepare("SELECT userUUID FROM userSupervisorAssociations WHERE supervisorUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($userUUID);
			
			while($stmt->fetch()){
				$this->supervisorAssociations[] = $userUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_SUPERVISOR_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryTrainingManagerAssociations(){
		$stmt = $this->db->prepare("SELECT userUUID FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($userUUID);
		
			while($stmt->fetch()){
				$this->trainingManagerAssociations[] = $userUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_TRAINING_MANAGER_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
			
			return false;
		}
	}
	
	public function queryUserSupervisors(){
		$stmt = $this->db->prepare("SELECT supervisorUUID FROM userSupervisorAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($supervisorUUID);
		
			while($stmt->fetch()){
				$this->userSupervisors[] = $supervisorUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_USER_SUPERVISORS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryUserTrainingManagers(){
		$stmt = $this->db->prepare("SELECT trainingManagerUUID FROM userTrainingManagerAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($trainingManagerUUID);
		
			while($stmt->fetch()){
				$this->userTrainingManagers[] = $trainingManagerUUID;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_USER_TRAINING_MANAGERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT afscUUID, afscName
                                    FROM userAFSCAssociations
                                      LEFT JOIN afscList
                                      ON afscList.uuid = userAFSCAssociations.afscUUID
                                    WHERE userAuthorized = 1
                                    AND userUUID = ?
                                    ORDER BY afscList.afscName ASC");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($afscUUID, $afscName);
		
			while($stmt->fetch()){
				$this->afscAssociations[$afscUUID] = $afscName;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryPendingAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT afscUUID, afscName
                                    FROM userAFSCAssociations
                                      LEFT JOIN afscList
                                      ON afscList.uuid = userAFSCAssociations.afscUUID
                                    WHERE userAuthorized = 0
                                    AND userUUID = ?
                                    ORDER BY afscList.afscName ASC");
		$stmt->bind_param("s",$this->userUUID);
	
		if($stmt->execute()){
			$stmt->bind_result($afscUUID,$afscName);
	
			while($stmt->fetch()){
				$this->pendingAFSCAssociations[$afscUUID] = $afscName;
			}
	
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_PENDING_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
	
			return false;
		}
	}
	
	public function queryCountAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 1 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countAFSCAssociations = $count;
			}
		
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountPendingAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 0 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countPendingAFSCAssociations = $count;
			}
		
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_PENDING_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountSupervisorSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE supervisorUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
				
			while($stmt->fetch()){
				$this->countSupervisorSubordinates = $count;
			}
			
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_SUPERVISOR_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountTrainingManagerSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countTrainingManagerSubordinates = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_TRAINING_MANAGER_SUBORDINATES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
				
			return false;
		}
	}
	
	public function queryCountUserSupervisors(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countUserSupervisors = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_USER_SUPERVISORS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function queryCountUserTrainingManagers(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
		
			while($stmt->fetch()){
				$this->countUserTrainingManagers = $count;
			}
				
			$stmt->close();
			return true;
		}
		else{
			$this->error = $stmt->error;
			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_USER_TRAINING_MANAGERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			$stmt->close();
		
			return false;
		}
	}
	
	public function getUserUUID(){
		return $this->userUUID;
	}
	
	public function setUserUUID($userUUID){
		$this->userUUID = $userUUID;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}