<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/23/15
 * Time: 2:29 AM
 */

$statistics = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);
$answerManager = new CDCMastery\AnswerManager($db, $systemLog);
$questionManager = new CDCMastery\QuestionManager($db, $systemLog, $afscManager, $answerManager);
$superUser = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$superOverview = new CDCMastery\SupervisorOverview($db, $systemLog, $userStatistics, $superUser, $roleManager);

$workingAFSC = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
if(!$afscManager->loadAFSC($workingAFSC)){
    $systemMessages->addMessage("That AFSC does not exist.", "warning");
    $cdcMastery->redirect("/errors/404");
}

if(isset($_SESSION['vars'][1])){
    $supervisorUUID = $_SESSION['vars'][1];
}
else{
    $supervisorUUID = $_SESSION['userUUID'];
}

if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
    $systemMessages->addMessage("You are not authorized to view the Supervisor Missed Questions Overview.", "danger");
    $cdcMastery->redirect("/errors/403");
}

if($roleManager->getRoleType($userManager->getUserRoleByUUID($supervisorUUID)) != "supervisor"){
    $systemMessages->addMessage("That user is not a Supervisor.", "warning");
    $cdcMastery->redirect("/errors/500");
}

$questionManager->setAFSCUUID($workingAFSC);
$superOverview->loadSupervisor($supervisorUUID);

$userList = $superOverview->getSubordinateUserList();

if(!$userList){
    $systemMessages->addMessage("You have no subordinate users. Please associate users with your account by using the form below.", "info");
    $cdcMastery->redirect("/supervisor/subordinates");
}

$questionList = $superOverview->getQuestionsMissedOverviewByAFSC($workingAFSC,$userList);
$questionShownList = $superOverview->getQuestionsShownCountByAFSC($workingAFSC,$userList);
?>
<script>
    $(document).ready(function()
        {
            $("#questionListTable").tablesorter({sortList: [[3,1]]});
        }
    );
</script>
<!--[if !IE]><!-->
<style type="text/css">
    @media
    only screen and (max-width: 760px),
    (min-device-width: 768px) and (max-device-width: 1024px)  {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        tr { border: 1px solid #ccc; }
        td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 3%;
        }
        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 20%;
            padding-right: 10px;
            white-space: nowrap;
        }
    }
    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body {
            padding: 0;
            margin: 0;
            width: 320px; }
    }
    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
        body {
            width: 495px;
        }
    }
</style>
<!--<![endif]-->
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Top Questions Missed for <?php echo $afscManager->getAFSCName($workingAFSC); ?></h2>
                </header>
                <a href="/supervisor/overview<?php echo (isset($_SESSION['vars'][1])) ? "/".$_SESSION['vars'][1] : ""; ?>" class="button">&laquo; Back</a>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <?php if(!empty($questionList)): ?>
                    <p>
                        <strong>Note:</strong> Click on the column headers to sort by that column.  This page of statistics is limited to the test data of the users that are your subordinates.
                    </p>
                    <table id="questionListTable" class="tablesorter">
                        <thead>
                        <tr>
                            <th>Question Text (Truncated)</th>
                            <th title="How many times this question has been incorrectly answered.">Times Missed</th>
                            <th title="How many times this question has been shown on a test.">Times Shown</th>
                            <th title="Percent of the time this question has been answered incorrectly.">% Missed</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($questionList as $questionUUID => $questionTimesMissed): ?>
                            <?php $questionManager->loadQuestion($questionUUID); ?>
                            <?php
                            if(isset($questionShownList[$questionUUID])){
                                if(($questionShownList[$questionUUID] > 0)){
                                    $percentMissed = round((($questionTimesMissed / $questionShownList[$questionUUID]) * 100),2);
                                }
                                else{
                                    $percentMissed = 0;
                                }
                            }
                            ?>
                            <tr>
                                <td title="<?php echo addslashes($questionManager->getQuestionText()); ?>"><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(),110);  ?></td>
                                <td><?php echo $questionTimesMissed; ?></td>
                                <td><?php echo $questionShownList[$questionUUID]; ?></td>
                                <?php if($percentMissed <= 20): ?>
                                    <td class="text-success"><?php echo $percentMissed; ?>%</td>
                                <?php elseif(($percentMissed > 20) && ($percentMissed <= 40)): ?>
                                    <td class="text-caution"><?php echo $percentMissed; ?>%</td>
                                <?php else: ?>
                                    <td class="text-warning"><?php echo $percentMissed; ?>%</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    None of your subordinates have taken a test with this AFSC, or they have not missed any questions.
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>