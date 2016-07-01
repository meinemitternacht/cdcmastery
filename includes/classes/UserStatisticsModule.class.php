<?php

class UserStatisticsModule extends CDCMastery
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

	/**
	 * User question and answer occurrences
	 */
	public $questionOccurrences;
	public $answerOccurrences;

	/**
	 * userStatistics constructor.
	 * @param mysqli $db
	 * @param SystemLog $log
	 * @param RoleManager $roles
	 * @param Memcache $memcache
	 */

	public function __construct(mysqli $db, SystemLog $log, RoleManager $roles, Memcache $memcache){
		$this->db = $db;
		$this->log = $log;
		$this->roles = $roles;
		$this->memcache = $memcache;
	}

    /**
     * Cache Functions
     */

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
	
	/**
	 * Entry Functions
	 */

	/**
	 * @return bool|int
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

	/**
	 * @return bool|double
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return bool|double
	 */
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

	/**
	 * @return bool|int
	 */
	public function getTotalTests(){
		return ($this->getCompletedTests() + $this->getIncompleteTests());
	}

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return array|bool
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
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

	/**
	 * @return bool|int
	 */
	public function getQuestionOccurrences($userUUID,$questionUUID){
		if($this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$questionUUID)){
			return $this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$questionUUID);
		}
		else{
			if(!$this->queryQuestionOccurrences($userUUID,$questionUUID)){
				return false;
			}
			else{
				$this->setUserStatsCacheVal(__FUNCTION__,$this->questionOccurrences[$userUUID][$questionUUID],$this->getCacheTTL(),$userUUID,$questionUUID);
				return $this->questionOccurrences[$userUUID][$questionUUID];
			}
		}
	}

	/**
	 * @return bool|int
	 */
	public function getAnswerOccurrences($userUUID,$answerUUID){
		if($this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$answerUUID)){
			return $this->getUserStatsCacheVal(__FUNCTION__,$userUUID,$answerUUID);
		}
		else{
			if(!$this->queryAnswerOccurrences($userUUID,$answerUUID)){
				return false;
			}
			else{
				$this->setUserStatsCacheVal(__FUNCTION__,$this->answerOccurrences[$userUUID][$answerUUID],$this->getCacheTTL(),$userUUID,$answerUUID);
				return $this->answerOccurrences[$userUUID][$answerUUID];
			}
		}
	}
	
	/**
	 * Queries
	 */

	/**
	 * @param $userUUID
	 * @param $questionUUID
	 * @return bool
	 */
	public function queryQuestionOccurrences($userUUID,$questionUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData LEFT JOIN testHistory ON testHistory.uuid = testData.testUUID WHERE testHistory.userUUID = ? AND questionUUID = ?");

		$stmt->bind_param("ss",$userUUID,$questionUUID);

		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->questionOccurrences[$userUUID][$questionUUID] = $count;

			return true;
		}
		else{
			$sqlError = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_QUERY_QUESTION_OCCURRENCES");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Question UUID",$questionUUID);
			$this->log->setDetail("MySQL Error",$sqlError);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @param $userUUID
	 * @param $answerUUID
	 * @return bool
	 */
	public function queryAnswerOccurrences($userUUID,$answerUUID){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData LEFT JOIN testHistory ON testHistory.uuid = testData.testUUID WHERE testHistory.userUUID = ? AND answerUUID = ?");

		$stmt->bind_param("ss",$userUUID, $answerUUID);

		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->answerOccurrences[$userUUID][$answerUUID] = $count;

			return true;
		}
		else{
			$sqlError = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_QUERY_ANSWER_OCCURRENCES");
			$this->log->setDetail("User UUID",$userUUID);
			$this->log->setDetail("Answer UUID",$answerUUID);
			$this->log->setDetail("MySQL Error",$sqlError);
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @return bool
	 */
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
            $stmt->close();

            $this->log->setAction("ERROR_USERSTATS_QUERY_IP_ADDRESSES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();

            return false;
        }
    }

	/**
	 * @return bool
	 */
	public function queryLogEntries(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->logEntries = $count;
			return true;
		}
		else{
			$this->error = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_LOG_ENTRIES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function queryAverageScore(){
		$stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($averageScore);
            $stmt->fetch();
            $stmt->close();

            $this->averageScore = round($averageScore,2);
			return true;
		}
		else{
			$this->error = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_AVERAGE_SCORE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
				
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function queryCompletedTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->completedTests = $count;
			return true;
		}
		else{
			$this->error = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COMPLETED_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function queryIncompleteTests(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testManager WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->incompleteTests = $count;
			return true;
		}
		else{
			$this->error = $stmt->error;
            $stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_INCOMPLETE_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
				
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryLatestTestScore(){
        $stmt = $this->db->prepare("SELECT testScore FROM `testHistory` WHERE userUUID = ? ORDER BY testTimeCompleted DESC LIMIT 1");
		$stmt->bind_param("s",$this->userUUID);

		if($stmt->execute()){
            $stmt->bind_result($testScore);
            $stmt->fetch();
            $stmt->close();

            $this->latestTestScore = $testScore;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $stmt->close();

            $this->log->setAction("ERROR_USERSTATS_QUERY_LATEST_TEST_SCORE");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();

            return false;
        }
    }

	/**
	 * @return bool
     */
	public function queryQuestionsAnswered(){
		$stmt = $this->db->prepare("SELECT SUM(totalQuestions) AS questionsAnswered FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsAnswered);
            $stmt->fetch();
            $stmt->close();

            $this->questionsAnswered = $questionsAnswered;
			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_ANSWERED");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryQuestionsMissed(){
		$stmt = $this->db->prepare("SELECT SUM(questionsMissed) AS questionsMissed FROM testHistory WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($questionsMissed);
            $stmt->fetch();
            $stmt->close();

            $this->questionsMissed = $questionsMissed;
			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

            $this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_MISSED");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @return bool
     */
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
            $stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_QUESTIONS_MISSED_ACROSS_TESTS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();

			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_SUPERVISOR_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_TRAINING_MANAGER_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
			
			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_USER_SUPERVISORS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
	
			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_USER_TRAINING_MANAGERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
	
			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
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
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_PENDING_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
	
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 1 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countAFSCAssociations = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountPendingAFSCAssociations(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 0 AND userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countPendingAFSCAssociations = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_PENDING_AFSC_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountSupervisorSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE supervisorUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countSupervisorSubordinates = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_SUPERVISOR_ASSOCIATIONS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountTrainingManagerSubordinates(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE trainingManagerUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countTrainingManagerSubordinates = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_TRAINING_MANAGER_SUBORDINATES");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
				
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountUserSupervisors(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userSupervisorAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countUserSupervisors = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_USER_SUPERVISORS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return bool
     */
	public function queryCountUserTrainingManagers(){
		$stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userTrainingManagerAssociations WHERE userUUID = ?");
		$stmt->bind_param("s",$this->userUUID);
		
		if($stmt->execute()){
			$stmt->bind_result($count);
			$stmt->fetch();
			$stmt->close();

			$this->countUserTrainingManagers = $count;

			return true;
		}
		else{
			$this->error = $stmt->error;
			$stmt->close();

			$this->log->setAction("ERROR_USERSTATS_QUERY_COUNT_USER_TRAINING_MANAGERS");
			$this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
			$this->log->setDetail("MYSQL ERROR",$this->error);
			$this->log->saveEntry();
		
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function getUserUUID(){
		return $this->userUUID;
	}

	/**
	 * @param $userUUID
	 * @return bool
	 */
	public function setUserUUID($userUUID){
		$this->userUUID = $userUUID;
		return true;
	}
	
	public function __destruct(){
		parent::__destruct();
	}
}