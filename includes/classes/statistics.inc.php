<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/23/2015
 * Time: 8:10 PM
 */

class statistics extends CDCMastery {

    protected $db;
    protected $log;
    protected $emailQueue;
    protected $memcache;

    public $error;

    public $afscPassRateArray;
    public $testAFSCCount;
    public $testsAverageScoreArrayLastSeven;
    public $testsAverageScoreByTimespan;
    public $testsByHourOfDay;
    public $testsByDayOfMonth;
    public $testCountByDay;
    public $testCountByMonth;
    public $testCountByTimespan;

    public $baseActionsCount;
    public $totalTestsByBase;
    public $totalUsersByBase;
    public $averageScoreByBase;

    public $totalTests;
    public $totalCompletedTests;
    public $totalIncompleteTests;
    public $totalArchivedTests;
    public $totalQuestionsAnswered;
    public $totalDatabaseQuestionsAnswered;

    public $totalAFSCCategories;
    public $totalFOUOAFSCCategories;
    public $totalAFSCAssociations;

    public $totalQuestions;
    public $totalQuestionsArchived;
    public $totalQuestionsFOUO;

    public $totalAnswers;
    public $totalAnswersArchived;
    public $totalAnswersFOUO;

    public $totalUsers;
    public $totalRoleUser;
    public $totalRoleTrainingManager;
    public $totalRoleSupervisor;
    public $totalRoleAdministrator;
    public $totalRoleSuperAdministrator;
    public $totalRoleEditor;

    public $totalLogEntries;
    public $totalLogDetails;
    public $totalLoginErrors;

    public $logActionCount;
    public $logActionCountByHourOfDay;
    public $groupedLogActionCount;

    public $totalOfficeSymbols;

    public $userRegistrationsCount;
    public $userRegistrationsCountDay;
    public $inactiveUsers;

    public $usersActiveToday;
    public $usersActiveThisWeek;
    public $usersActiveThisMonth;
    public $usersActiveThisYear;
    public $usersActiveFifteenMinutes;

    public $totalQuestionOccurrences;
    public $totalAnswerOccurrences;
    public $totalQuestionAnswerPairOccurrences;

    public $totalAFSCFlashCardCategories;
    public $totalGlobalFlashCardCategories;
    public $totalPrivateFlashCardCategories;

    public function __construct(mysqli $db, log $log, emailQueue $emailQueue, Memcache $memcache){
        $this->db = $db;
        $this->log = $log;
        $this->emailQueue = $emailQueue;
        $this->memcache = $memcache;
    }

    /*
	 * Cache Functions
	 */
    public function deleteStatsCacheVal($functionName,$var1=false,$var2=false,$var3=false){
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

        $cacheHash = md5($hashVal);

        if($this->memcache->delete($cacheHash)){
            return true;
        }
        else{
            return false;
        }
    }

    public function setStatsCacheVal($functionName,$cacheValue,$cacheTTL,$var1=false,$var2=false,$var3=false){
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

        $cacheHash = md5($hashVal);
        $this->memcache->delete($cacheHash);
        if($this->memcache->add($cacheHash,$cacheValue,NULL,$cacheTTL)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getStatsCacheVal($functionName,$var1=false,$var2=false,$var3=false){
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

        $cacheHash = md5($hashVal);

        return $this->memcache->get($cacheHash);
    }

    public function getAFSCPassRates($afscUUIDList){
        if($this->getStatsCacheVal(__FUNCTION__,implode("-",$afscUUIDList))){
            return $this->getStatsCacheVal(__FUNCTION__,implode("-",$afscUUIDList));
        }
        else{
            if(!$this->queryAFSCPassRates($afscUUIDList)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->afscPassRateArray,$this->getCacheTTL(6),implode("-",$afscUUIDList));
                return $this->afscPassRateArray;
            }
        }
    }

    public function queryAFSCPassRates($afscUUIDList){
        if(is_array($afscUUIDList) && count($afscUUIDList) > 0) {
            foreach($afscUUIDList as $afscUUID) {
                $query = "SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE '%a:1%' AND afscList LIKE '%".$afscUUID."%' AND testScore > " . $this->getPassingScore();
                $res = $this->db->query($query);

                if($res->num_rows > 0){
                    while($row = $res->fetch_assoc()) {
                        $this->afscPassRateArray[$afscUUID]['passingTests'] = $row['count'];
                    }
                    $res->close();
                }
            }

            foreach($afscUUIDList as $afscUUID){
                $res = $this->db->query("SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE '%a:1%' AND afscList LIKE '%".$afscUUID."%'");

                if($res->num_rows > 0){
                    while($row = $res->fetch_assoc()) {
                        $this->afscPassRateArray[$afscUUID]['totalTests'] = $row['count'];
                        if (($this->afscPassRateArray[$afscUUID]['passingTests'] > 0) && ($this->afscPassRateArray[$afscUUID]['totalTests'] > 0)) {
                            $this->afscPassRateArray[$afscUUID]['passRate'] = round(($this->afscPassRateArray[$afscUUID]['passingTests'] / $this->afscPassRateArray[$afscUUID]['totalTests']) * 100, 2);
                        } else {
                            $this->afscPassRateArray[$afscUUID]['passRate'] = 0;
                        }
                    }

                    $res->close();
                }
            }

            if(isset($this->afscPassRateArray) && is_array($this->afscPassRateArray) && count($this->afscPassRateArray) > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestAFSCCount($afscUUIDList){
        if($this->getStatsCacheVal(__FUNCTION__,implode("-",$afscUUIDList))){
            return $this->getStatsCacheVal(__FUNCTION__,implode("-",$afscUUIDList));
        }
        else{
            if(!$this->queryTestAFSCCount($afscUUIDList)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testAFSCCount,$this->getCacheTTL(6),implode("-",$afscUUIDList));
                return $this->testAFSCCount;
            }
        }
    }

    public function queryTestAFSCCount($afscUUIDList){
        if(is_array($afscUUIDList) && count($afscUUIDList) > 0) {
            foreach($afscUUIDList as $afscUUID) {
                $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE afscList LIKE ?");
                $afscUUIDParam = "%".$afscUUID."%";
                $stmt->bind_param("s",$afscUUIDParam);

                if($stmt->execute()){
                    $stmt->bind_result($count);

                    while($stmt->fetch()){
                        $this->testAFSCCount[$afscUUID] = $count;
                    }

                    $stmt->close();
                }
                else{
                    $this->error = $stmt->error;
                    $this->log->setAction("MYSQL_ERROR");
                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                    $this->log->setDetail("MYSQL ERROR",$this->error);
                    $this->log->saveEntry();
                    $stmt->close();

                    return false;
                }
            }

            if(isset($this->testAFSCCount) && is_array($this->testAFSCCount) && count($this->testAFSCCount) > 0){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestAverageLastSeven(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTestAverageLastSeven()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testsAverageScoreArrayLastSeven,$this->getCacheTTL(6));
                return $this->testsAverageScoreArrayLastSeven;
            }
        }
    }

    public function queryTestAverageLastSeven(){
        $j=0;
        for($i=7;$i>0;$i--) {
            $dateObj = new DateTime('now');
            $dateObj->modify('-'.$i.' days');

            $startDateTime = $dateObj->format('Y-m-d 00:00:00');
            $endDateTime = $dateObj->format('Y-m-d 23:59:59');

            $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

            $stmt->bind_param("ss",$startDateTime,$endDateTime);

            if($stmt->execute()){
                $stmt->bind_result($averageScore);

                while($stmt->fetch()){
                    if(!$averageScore){
                        $averageScore = "0";
                    }
                    $this->testsAverageScoreArrayLastSeven[$j]['averageScore'] = round($averageScore,2);
                    $this->testsAverageScoreArrayLastSeven[$j]['dateTime'] = $dateObj->format('j-M-Y');
                }

                $stmt->close();
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
            $j++;
        }

        if(isset($this->testsAverageScoreArrayLastSeven) && is_array($this->testsAverageScoreArrayLastSeven) && count($this->testsAverageScoreArrayLastSeven) > 0){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestAverageByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        if($this->getStatsCacheVal(__FUNCTION__,$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"))){
            return $this->getStatsCacheVal(__FUNCTION__,$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"));
        }
        else{
            if(!$this->queryTestAverageByTimespan($dateTimeStart, $dateTimeEnd)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testsAverageScoreByTimespan,$this->getCacheTTL(5),$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"));
                return $this->testsAverageScoreByTimespan;
            }
        }
    }

    public function queryTestAverageByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        $startDateTime = $dateTimeStart->format('Y-m-d 00:00:00');
        $endDateTime = $dateTimeEnd->format('Y-m-d 23:59:59');

        $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

        $stmt->bind_param("ss",$startDateTime,$endDateTime);

        if($stmt->execute()){
            $stmt->bind_result($averageScore);

            while($stmt->fetch()){
                if(!$averageScore){
                    $averageScore = "0";
                }
                $this->testsAverageScoreByTimespan = round($averageScore,2);
            }

            $stmt->close();
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }

        if(isset($this->testsAverageScoreByTimespan)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestsByHourOfDay(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTestsByHourOfDay()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testsByHourOfDay,$this->getCacheTTL(5));
                return $this->testsByHourOfDay;
            }
        }
    }

    public function queryTestsByHourOfDay(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE testTimeCompleted LIKE CONCAT('____-__-__%', ? , ':__:__')");

        for($i=0;$i<24;$i++){
            $hourString = ($i < 10) ? "0".$i : $i;
            $stmt->bind_param("s",$hourString);

            if($stmt->execute()){
                $stmt->bind_result($testCount);
                $stmt->fetch();

                $this->testsByHourOfDay[$hourString] = $testCount;
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }

        if(!empty($this->testsByHourOfDay)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestsByDayOfMonth(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTestsByDayOfMonth()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testsByDayOfMonth,$this->getCacheTTL(5));
                return $this->testsByDayOfMonth;
            }
        }
    }

    public function queryTestsByDayOfMonth(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE testTimeCompleted LIKE CONCAT('____-__-%', ? , '%')");

        for($i=1;$i<32;$i++){
            $dayString = ($i < 10) ? "0".$i : $i;
            $stmt->bind_param("s",$dayString);

            if($stmt->execute()){
                $stmt->bind_result($testCount);
                $stmt->fetch();

                $this->testsByDayOfMonth[$dayString] = $testCount;
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }

        if(!empty($this->testsByDayOfMonth)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTestCountByDay(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTestCountByDay()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testCountByDay,$this->getCacheTTL(5));
                return $this->testCountByDay;
            }
        }
    }

    public function queryTestCountByDay(){
        $res = $this->db->query("SELECT DATE(testHistory.testTimeStarted) AS testDate,
                                    COUNT(*) AS testCount
                                    FROM testHistory
                                      GROUP BY DATE(testHistory.testTimeCompleted)
                                      ORDER BY testDate");

        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $this->testCountByDay[$row['testDate']] = $row['testCount'];
            }

            if(isset($this->testCountByDay) && !empty($this->testCountByDay)){
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestCountByMonth(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTestCountByMonth()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testCountByMonth,$this->getCacheTTL(6));
                return $this->testCountByMonth;
            }
        }
    }

    public function queryTestCountByMonth(){
        $res = $this->db->query("SELECT DATE_FORMAT(testHistory.testTimeCompleted, '%Y-%m') AS testDate, COUNT(*) AS testCount
                                    FROM testHistory
                                      GROUP BY testDate
                                      ORDER BY testDate");

        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $this->testCountByMonth[$row['testDate']] = $row['testCount'];
            }

            if(isset($this->testCountByMonth) && !empty($this->testCountByMonth)){
                $res->close();
                return true;
            }
            else{
                $this->error = $this->db->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();

                $res->close();

                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTestCountByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        if($this->getStatsCacheVal(__FUNCTION__,$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"))){
            return $this->getStatsCacheVal(__FUNCTION__,$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"));
        }
        else{
            if(!$this->queryTestCountByTimespan($dateTimeStart, $dateTimeEnd)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->testCountByTimespan,$this->getCacheTTL(4),$dateTimeStart->format("YmdHis"),$dateTimeEnd->format("YmdHis"));
                return $this->testCountByTimespan;
            }
        }
    }

    public function queryTestCountByTimespan(DateTime $dateTimeStart, DateTime $dateTimeEnd){
        $startDateTime = $dateTimeStart->format('Y-m-d 00:00:00');
        $endDateTime = $dateTimeEnd->format('Y-m-d 23:59:59');

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE testTimeCompleted BETWEEN ? AND ?");

        $stmt->bind_param("ss",$startDateTime,$endDateTime);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                if(!$count){
                    $count = "0";
                }
                $this->testCountByTimespan = $count;
            }

            $stmt->close();
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }

        if(isset($this->testCountByTimespan)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getTotalTestsByBase($baseUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$baseUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$baseUUID);
        }
        else{
            if(!$this->queryTotalTestsByBase($baseUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalTestsByBase,$this->getCacheTTL(6),$baseUUID);
                return $this->totalTestsByBase;
            }
        }
    }

    public function queryTotalTestsByBase($baseUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory`
                                        LEFT JOIN `userData` ON `userData`.`uuid` = `testHistory`.`userUUID`
                                        WHERE `userData`.`userBase` = ?");

        $stmt->bind_param("s",$baseUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalTestsByBase = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalUsersByBase($baseUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$baseUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$baseUUID);
        }
        else{
            if(!$this->queryTotalUsersByBase($baseUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalUsersByBase,$this->getCacheTTL(6),$baseUUID);
                return $this->totalUsersByBase;
            }
        }
    }

    public function queryTotalUsersByBase($baseUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `userData`
                                        WHERE `userData`.`userBase` = ?");

        $stmt->bind_param("s",$baseUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalUsersByBase = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getAverageScoreByBase($baseUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$baseUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$baseUUID);
        }
        else{
            if(!$this->queryAverageScoreByBase($baseUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->averageScoreByBase,$this->getCacheTTL(6),$baseUUID);
                return round($this->averageScoreByBase,2);
            }
        }
    }

    public function queryAverageScoreByBase($baseUUID){
        $stmt = $this->db->prepare("SELECT AVG(testScore) AS averageScore FROM `testHistory`
                                        LEFT JOIN `userData` ON `userData`.`uuid` = `testHistory`.`userUUID`
                                        WHERE `userData`.`userBase` = ?");

        $stmt->bind_param("s",$baseUUID);

        if($stmt->execute()){
            $stmt->bind_result($averageScore);

            while($stmt->fetch()){
                $this->averageScoreByBase = $averageScore;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalTests(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->getTotalCompletedTests()){
                return 0;
            }
            elseif(!$this->getTotalIncompleteTests()){
                return 0;
            }
            else{
                $this->totalTests = $this->totalCompletedTests + $this->totalIncompleteTests;
                $this->setStatsCacheVal(__FUNCTION__,$this->totalTests,$this->getCacheTTL(2));
                return $this->totalTests;
            }
        }
    }

    public function getTotalIncompleteTests(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalIncompleteTests()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalIncompleteTests,$this->getCacheTTL(2));
                return $this->totalIncompleteTests;
            }
        }
    }

    public function queryTotalIncompleteTests(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testManager`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalIncompleteTests = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalArchivedTests(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalArchivedTests()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalArchivedTests,$this->getCacheTTL(2));
                return $this->totalArchivedTests;
            }
        }
    }

    public function queryTotalArchivedTests(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory` WHERE testArchived IS NOT NULL");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalArchivedTests = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalCompletedTests(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalCompletedTests()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalCompletedTests,$this->getCacheTTL(2));
                return $this->totalCompletedTests;
            }
        }
    }

    public function queryTotalCompletedTests(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `testHistory`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalCompletedTests = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsAnswered(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalQuestionsAnswered()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestionsAnswered,$this->getCacheTTL(2));
                return $this->totalQuestionsAnswered;
            }
        }
    }

    public function queryTotalQuestionsAnswered(){
        $stmt = $this->db->prepare("SELECT SUM(totalQuestions) AS sumQuestions FROM `testHistory`");

        if($stmt->execute()){
            $stmt->bind_result($sumQuestions);

            while($stmt->fetch()){
                $this->totalQuestionsAnswered = $sumQuestions;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalDatabaseQuestionsAnswered(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalDatabaseQuestionsAnswered()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalDatabaseQuestionsAnswered,$this->getCacheTTL(3));
                return $this->totalDatabaseQuestionsAnswered;
            }
        }
    }

    public function queryTotalDatabaseQuestionsAnswered(){
        $stmt = $this->db->prepare("SELECT SUM(totalQuestions) AS sumQuestions FROM `testHistory` WHERE testArchived IS NULL");

        if($stmt->execute()){
            $stmt->bind_result($sumQuestions);

            while($stmt->fetch()){
                $this->totalDatabaseQuestionsAnswered = $sumQuestions;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAFSCCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAFSCCategories()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAFSCCategories,$this->getCacheTTL(6));
                return $this->totalAFSCCategories;
            }
        }
    }

    public function queryTotalAFSCCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `afscList`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAFSCCategories = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAFSCAssociations(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAFSCAssociations()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAFSCAssociations,$this->getCacheTTL(6));
                return $this->totalAFSCAssociations;
            }
        }
    }

    public function queryTotalAFSCAssociations(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `userAFSCAssociations`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAFSCAssociations = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalFOUOAFSCCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalFOUOAFSCCategories()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalFOUOAFSCCategories,$this->getCacheTTL(6));
                return $this->totalFOUOAFSCCategories;
            }
        }
    }

    public function queryTotalFOUOAFSCCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `afscList` WHERE afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalFOUOAFSCCategories = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestions(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalQuestions()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestions,$this->getCacheTTL(6));
                return $this->totalQuestions;
            }
        }
    }

    public function queryTotalQuestions(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionData`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestions = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsArchived(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalQuestionsArchived()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestionsArchived,$this->getCacheTTL(6));
                return $this->totalQuestionsArchived;
            }
        }
    }

    public function queryTotalQuestionsArchived(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionDataArchived`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestionsArchived = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionsFOUO(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalQuestionsFOUO()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestionsFOUO,$this->getCacheTTL(6));
                return $this->totalQuestionsFOUO;
            }
        }
    }

    public function queryTotalQuestionsFOUO(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `questionData` LEFT JOIN afscList ON questionData.afscUUID = afscList.uuid WHERE afscList.afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalQuestionsFOUO = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswers(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAnswers()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAnswers,$this->getCacheTTL(6));
                return $this->totalAnswers;
            }
        }
    }

    public function queryTotalAnswers(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerData`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswers = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswersArchived(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAnswersArchived()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAnswersArchived,$this->getCacheTTL(6));
                return $this->totalAnswersArchived;
            }
        }
    }

    public function queryTotalAnswersArchived(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerDataArchived`");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswersArchived = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswersFOUO(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAnswersFOUO()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAnswersFOUO,$this->getCacheTTL(6));
                return $this->totalAnswersFOUO;
            }
        }
    }

    public function queryTotalAnswersFOUO(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM `answerData` LEFT JOIN questionData ON questionData.uuid = answerData.questionUUID LEFT JOIN afscList ON questionData.afscUUID = afscList.uuid WHERE afscList.afscFOUO = 1");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalAnswersFOUO = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalUsers(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalUsers()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalUsers,$this->getCacheTTL(3));
                return $this->totalUsers;
            }
        }
    }

    public function queryTotalUsers(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalUsers = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleUser(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleUser()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleUser,$this->getCacheTTL(3));
                return $this->totalRoleUser;
            }
        }
    }

    public function queryTotalRoleUser(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Users");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleUser = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleTrainingManager(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleTrainingManager()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleTrainingManager,$this->getCacheTTL(3));
                return $this->totalRoleTrainingManager;
            }
        }
    }

    public function queryTotalRoleTrainingManager(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Training Managers");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleTrainingManager = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleSupervisor(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleSupervisor()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleSupervisor,$this->getCacheTTL(3));
                return $this->totalRoleSupervisor;
            }
        }
    }

    public function queryTotalRoleSupervisor(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Supervisors");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleSupervisor = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleAdministrator(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleAdministrator()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleAdministrator,$this->getCacheTTL(3));
                return $this->totalRoleAdministrator;
            }
        }
    }

    public function queryTotalRoleAdministrator(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Administrators");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleAdministrator = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleSuperAdministrator(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleSuperAdministrator()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleSuperAdministrator,$this->getCacheTTL(3));
                return $this->totalRoleSuperAdministrator;
            }
        }
    }

    public function queryTotalRoleSuperAdministrator(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Super Administrators");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleSuperAdministrator = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalRoleEditor(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalRoleEditor()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalRoleEditor,$this->getCacheTTL(3));
                return $this->totalRoleEditor;
            }
        }
    }

    public function queryTotalRoleEditor(){
        $roleManager = new roles($this->db,$this->log,$this->emailQueue);

        $roleUUID = $roleManager->getRoleUUIDByName("Question Editors");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE userRole = ?");
        $stmt->bind_param("s",$roleUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalRoleEditor = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLogEntries(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalLogEntries()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalLogEntries,$this->getCacheTTL(3));
                return $this->totalLogEntries;
            }
        }
    }

    public function queryTotalLogEntries(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLogEntries = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLogDetails(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalLogDetails()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalLogDetails,$this->getCacheTTL(3));
                return $this->totalLogDetails;
            }
        }
    }

    public function queryTotalLogDetails(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLogData");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLogDetails = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalLoginErrors(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalLoginErrors()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalLoginErrors,$this->getCacheTTL(3));
                return $this->totalLoginErrors;
            }
        }
    }

    public function queryTotalLoginErrors(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE action LIKE '%ERROR_LOGIN%' OR action LIKE '%LOGIN_ERROR%'");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalLoginErrors = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getLogCountByAction($logAction){
        if($this->getStatsCacheVal(__FUNCTION__,$logAction)){
            return $this->getStatsCacheVal(__FUNCTION__,$logAction);
        }
        else{
            if(!$this->queryLogCountByAction($logAction)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->logActionCount[$logAction],$this->getCacheTTL(5),$logAction);
                return $this->logActionCount[$logAction];
            }
        }
    }

    public function queryLogCountByAction($logAction){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM systemLog WHERE action LIKE ?");
        $stmt->bind_param("s",$logAction);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->logActionCount[$logAction] = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getLogActionCountByHourOfDay($logAction){
        if($this->getStatsCacheVal(__FUNCTION__,$logAction)){
            return $this->getStatsCacheVal(__FUNCTION__,$logAction);
        }
        else{
            if(!$this->queryLogActionCountByHourOfDay($logAction)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->logActionCountByHourOfDay,$this->getCacheTTL(4),$logAction);
                return $this->logActionCountByHourOfDay;
            }
        }
    }

    public function queryLogActionCountByHourOfDay($logAction){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS logCount FROM `systemLog` WHERE action = ? AND timestamp LIKE CONCAT('____-__-__%', ? , ':__:__')");

        for($i=0;$i<24;$i++){
            $hourString = ($i < 10) ? "0".$i : $i;
            $stmt->bind_param("ss",$logAction,$hourString);

            if($stmt->execute()){
                $stmt->bind_result($logCount);
                $stmt->fetch();

                $this->logActionCountByHourOfDay[$hourString] = $logCount;
            }
            else{
                $this->error = $stmt->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }

        if(!empty($this->testsByHourOfDay)){
            return true;
        }
        else{
            return false;
        }
    }

    public function getGroupedLogActionCount(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryGroupedLogActionCount()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->groupedLogActionCount,$this->getCacheTTL(4));
                return $this->groupedLogActionCount;
            }
        }
    }

    public function queryGroupedLogActionCount(){
        $res = $this->db->query("SELECT `systemLog`.`action`, COUNT(uuid) AS count FROM `systemLog` GROUP BY `systemLog`.`action` ORDER BY `systemLog`.`action` ASC");

        if($res->num_rows > 0){
            $this->groupedLogActionCount = $res->fetch_all();

            if(!empty($this->groupedLogActionCount)){
                $res->close();
                return true;
            }
            else{
                $this->error = $this->db->error;
                $this->log->setAction("MYSQL_ERROR");
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("MYSQL ERROR",$this->error);
                $this->log->saveEntry();
                $res->close();

                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getTotalOfficeSymbols(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalOfficeSymbols()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalOfficeSymbols,$this->getCacheTTL(6));
                return $this->totalOfficeSymbols;
            }
        }
    }

    public function queryTotalOfficeSymbols(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM officeSymbolList");

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->totalOfficeSymbols = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getRegistrationsByTimespan(DateTime $dateTimeStartObj, DateTime $dateTimeEndObj){
        if($this->getStatsCacheVal(__FUNCTION__,$dateTimeStartObj->format("YmdHis"),$dateTimeEndObj->format("YmdHis"))){
            return $this->getStatsCacheVal(__FUNCTION__,$dateTimeStartObj,$dateTimeEndObj);
        }
        else{
            if(!$this->queryRegistrationsByTimespan($dateTimeStartObj,$dateTimeEndObj)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->userRegistrationsCount,$this->getCacheTTL(6),$dateTimeStartObj->format("YmdHis"),$dateTimeEndObj->format("YmdHis"));
                return $this->userRegistrationsCount;
            }
        }
    }

    public function queryRegistrationsByTimespan(DateTime $dateTimeStartObj, DateTime $dateTimeEndObj){
        $dateTimeStart = $dateTimeStartObj->format("Y-m-d 00:00:00");
        $dateTimeEnd = $dateTimeEndObj->format("Y-m-d 23:59:59");

        $stmt = $this->db->prepare("SELECT DATE(userDateRegistered) AS registerDate, COUNT(*) AS count FROM `userData` WHERE (userDateRegistered BETWEEN ? AND ?) OR (userDateRegistered BETWEEN ? AND ?) GROUP BY DATE(userDateRegistered) ORDER BY userDateRegistered DESC");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->userRegistrationsCount = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getRegistrationsByDay(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryRegistrationsByDay()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->userRegistrationsCountDay,$this->getCacheTTL(6));
                return $this->userRegistrationsCountDay;
            }
        }
    }

    public function queryRegistrationsByDay(){
        $res = $this->db->query("SELECT DATE(userDateRegistered) AS registerDate, COUNT(*) AS count FROM `userData` GROUP BY DATE(userDateRegistered) ORDER BY userDateRegistered ASC");

        if($res->num_rows > 0){
            $this->userRegistrationsCountDay = $res->fetch_all();

            $res->close();
            return true;
        }
        else{
            $this->error = $this->db->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $res->close();

            return false;
        }
    }

    public function getInactiveUsers(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryInactiveUsers()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->inactiveUsers,$this->getCacheTTL(6));
                return $this->inactiveUsers;
            }
        }
    }

    public function queryInactiveUsers(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count  FROM `userData` WHERE `userLastLogin` IS NULL OR `userLastLogin` < DATE_SUB(NOW(), INTERVAL 12 MONTH);");
        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->inactiveUsers = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getUsersActiveToday(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryUsersActiveToday()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->usersActiveToday,$this->getCacheTTL(3));
                return $this->usersActiveToday;
            }
        }
    }

    public function queryUsersActiveToday(){
        $dateTimeStartObj = new DateTime("now");
        $dateTimeStart = $dateTimeStartObj->format("Y-m-d 00:00:00");

        $dateTimeEndObj = new DateTime("now");
        $dateTimeEnd = $dateTimeEndObj->format("Y-m-d 23:59:59");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE (userLastActive BETWEEN ? AND ?) OR (userLastLogin BETWEEN ? AND ?)");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->usersActiveToday = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getUsersActiveThisWeek(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryUsersActiveThisWeek()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->usersActiveThisWeek,$this->getCacheTTL(5));
                return $this->usersActiveThisWeek;
            }
        }
    }

    public function queryUsersActiveThisWeek(){
        $dateTimeStartObj = new DateTime("now");
        $dateTimeStartObj->setISODate($dateTimeStartObj->format("Y"),$dateTimeStartObj->format("W"));
        $dateTimeStartObj->modify("-1 day");
        $dateTimeStart = $dateTimeStartObj->format("Y-m-d 00:00:00");

        $dateTimeEndObj = new DateTime("now");
        $dateTimeEndObj->setISODate($dateTimeEndObj->format("Y"),$dateTimeEndObj->format("W"),6);
        $dateTimeEnd = $dateTimeEndObj->format("Y-m-d 23:59:59");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE (userLastActive BETWEEN ? AND ?) OR (userLastLogin BETWEEN ? AND ?)");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->usersActiveThisWeek = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getUsersActiveThisMonth(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryUsersActiveThisMonth()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->usersActiveThisMonth,$this->getCacheTTL(5));
                return $this->usersActiveThisMonth;
            }
        }
    }

    public function queryUsersActiveThisMonth(){
        $dateObj = new DateTime("now");

        $dateTimeStartObj = new DateTime("first day of " . $dateObj->format('F') . " " . $dateObj->format("Y"));
        $dateTimeEndObj = new DateTime("last day of " . $dateObj->format('F') . " " . $dateObj->format("Y"));

        $dateTimeStart = $dateTimeStartObj->format("Y-m-d 00:00:00");
        $dateTimeEnd = $dateTimeEndObj->format("Y-m-d 23:59:59");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE (userLastActive BETWEEN ? AND ?) OR (userLastLogin BETWEEN ? AND ?)");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->usersActiveThisMonth = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getUsersActiveThisYear(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryUsersActiveThisYear()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->usersActiveThisYear,$this->getCacheTTL(5));
                return $this->usersActiveThisYear;
            }
        }
    }

    public function queryUsersActiveThisYear(){
        $dateTimeStartObj = new DateTime("now");
        $dateTimeEndObj = new DateTime("now");

        $dateTimeStart = $dateTimeStartObj->format("Y-01-01 00:00:00");
        $dateTimeEnd = $dateTimeEndObj->format("Y-12-31 23:59:59");

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM userData WHERE (userLastActive BETWEEN ? AND ?) OR (userLastLogin BETWEEN ? AND ?)");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($count);

            while($stmt->fetch()){
                $this->usersActiveThisYear = $count;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getUsersActiveFifteenMinutes(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryUsersActiveFifteenMinutes()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->usersActiveFifteenMinutes,$this->getCacheTTL(1));
                return $this->usersActiveFifteenMinutes;
            }
        }
    }

    public function queryUsersActiveFifteenMinutes(){
        $dateTimeStartObj = new DateTime("now");
        $dateTimeEndObj = new DateTime("now");

        $dateTimeStartObj->modify("-15 minutes");

        $dateTimeStart = $dateTimeStartObj->format("Y-m-d H:i:s");
        $dateTimeEnd = $dateTimeEndObj->format("Y-m-d H:i:s");

        $stmt = $this->db->prepare("SELECT uuid FROM userData WHERE (userLastActive BETWEEN ? AND ?) OR (userLastLogin BETWEEN ? AND ?) ORDER BY userLastActive DESC");
        $stmt->bind_param("ssss",$dateTimeStart,$dateTimeEnd,$dateTimeStart,$dateTimeEnd);

        if($stmt->execute()){
            $stmt->bind_result($uuid);

            while($stmt->fetch()){
                $this->usersActiveFifteenMinutes[] = $uuid;
            }

            $stmt->close();
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionOccurrences($questionUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$questionUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$questionUUID);
        }
        else{
            if(!$this->queryTotalQuestionOccurrences($questionUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestionOccurrences,$this->getCacheTTL(5),$questionUUID);
                return $this->totalQuestionOccurrences;
            }
        }
    }

    public function queryTotalQuestionOccurrences($questionUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData WHERE questionUUID = ?");

        $stmt->bind_param("s", $questionUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalQuestionOccurrences = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAnswerOccurrences($answerUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$answerUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$answerUUID);
        }
        else{
            if(!$this->queryTotalAnswerOccurrences($answerUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAnswerOccurrences,$this->getCacheTTL(5),$answerUUID);
                return $this->totalAnswerOccurrences;
            }
        }
    }

    public function queryTotalAnswerOccurrences($answerUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData WHERE answerUUID = ?");

        $stmt->bind_param("s", $answerUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalAnswerOccurrences = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalQuestionAnswerPairOccurrences($questionUUID,$answerUUID){
        if($this->getStatsCacheVal(__FUNCTION__,$questionUUID,$answerUUID)){
            return $this->getStatsCacheVal(__FUNCTION__,$questionUUID,$answerUUID);
        }
        else{
            if(!$this->queryTotalQuestionAnswerPairOccurrences($questionUUID,$answerUUID)){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalQuestionAnswerPairOccurrences,$this->getCacheTTL(5),$questionUUID,$answerUUID);
                return $this->totalQuestionAnswerPairOccurrences;
            }
        }
    }

    public function queryTotalQuestionAnswerPairOccurrences($questionUUID,$answerUUID){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM testData WHERE questionUUID = ? AND answerUUID = ?");

        $stmt->bind_param("ss",$questionUUID, $answerUUID);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalQuestionAnswerPairOccurrences = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalAFSCFlashCardCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalAFSCFlashCardCategories()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalAFSCFlashCardCategories,$this->getCacheTTL(6));
                return $this->totalAFSCFlashCardCategories;
            }
        }
    }

    public function queryTotalAFSCFlashCardCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM flashCardCategories WHERE categoryType = 'afsc'");

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalAFSCFlashCardCategories = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalGlobalFlashCardCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalGlobalFlashCardCategories()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalGlobalFlashCardCategories,$this->getCacheTTL(6));
                return $this->totalGlobalFlashCardCategories;
            }
        }
    }

    public function queryTotalGlobalFlashCardCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM flashCardCategories WHERE categoryType = 'global'");

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalGlobalFlashCardCategories = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalPrivateFlashCardCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            if(!$this->queryTotalPrivateFlashCardCategories()){
                return 0;
            }
            else{
                $this->setStatsCacheVal(__FUNCTION__,$this->totalPrivateFlashCardCategories,$this->getCacheTTL(6));
                return $this->totalPrivateFlashCardCategories;
            }
        }
    }

    public function queryTotalPrivateFlashCardCategories(){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM flashCardCategories WHERE categoryType = 'private'");

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            $this->totalPrivateFlashCardCategories = $count;
            return true;
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function getTotalFlashCardCategories(){
        if($this->getStatsCacheVal(__FUNCTION__)){
            return $this->getStatsCacheVal(__FUNCTION__);
        }
        else{
            $totalFlashCardCategories = $this->getTotalAFSCFlashCardCategories() + $this->getTotalGlobalFlashCardCategories() + $this->getTotalPrivateFlashCardCategories();
            $this->setStatsCacheVal(__FUNCTION__,$totalFlashCardCategories,$this->getCacheTTL(6));
            return $totalFlashCardCategories;
        }
    }

    public function __destruct(){
        parent::__destruct();
    }
}