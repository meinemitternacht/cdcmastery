<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/23/15
 * Time: 2:29 AM
 */

$statistics = new statistics($db,$log,$emailQueue);
$answerManager = new answerManager($db, $log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);
$tmUser = new user($db,$log,$emailQueue);
$tmOverview = new trainingManagerOverview($db,$log,$userStatistics,$tmUser,$roles);

$workingAFSC = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
if(!$afsc->loadAFSC($workingAFSC)){
    $sysMsg->addMessage("That AFSC does not exist.");
    $cdcMastery->redirect("/errors/404");
}

if(isset($_SESSION['vars'][1])){
    $trainingManagerUUID = $_SESSION['vars'][1];
}
else{
    $trainingManagerUUID = $_SESSION['userUUID'];
}

if(!$cdcMastery->verifyTrainingManager() && !$cdcMastery->verifyAdmin()){
    $sysMsg->addMessage("You are not authorized to view the Training Manager Missed Questions Overview.");
    $cdcMastery->redirect("/errors/403");
}

if($roles->getRoleType($user->getUserRoleByUUID($trainingManagerUUID)) != "trainingManager"){
    $sysMsg->addMessage("That user is not a Training Manager.");
    $cdcMastery->redirect("/errors/500");
}

$questionManager->setAFSCUUID($workingAFSC);
$tmOverview->loadTrainingManager($trainingManagerUUID);

$userList = $tmOverview->getSubordinateUserList();
$supervisorList = $tmOverview->getSubordinateSupervisorList();

if(!$userList && !$supervisorList){
    $sysMsg->addMessage("You have no subordinate users.");
    $cdcMastery->redirect("/");
}
elseif(!empty($userList) && !empty($supervisorList)){
    $masterArray = array_merge($userList,$supervisorList);
}
elseif(!empty($userList)){
    $masterArray = $userList;
}
elseif(!empty($supervisorList)){
    $masterArray = $supervisorList;
}

$questionList = $tmOverview->getQuestionsMissedOverviewByAFSC($workingAFSC,$masterArray);
$questionShownList = $tmOverview->getQuestionsShownCountByAFSC($workingAFSC,$masterArray);
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
                    <h2>Top Questions Missed</h2>
                </header>
                <a href="/training/overview<?php echo (isset($_SESSION['vars'][1])) ? "/".$_SESSION['vars'][1] : ""; ?>" class="button">&laquo; Back</a>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <?php if(!empty($questionList)): ?>
                    <p>
                        <strong>Note:</strong> Click on the column headers to sort by that column.  This page of statistics is limited to the test data of the users that are your subordinates.  To
                        view site-wide statistics, <a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/list-questions">click here</a>.
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
                            <?php
                            $questionManager->loadQuestion($questionUUID);

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
                                <td><a href="/admin/cdc-data/<?php echo $questionManager->getAFSCUUID(); ?>/question/<?php echo $questionUUID; ?>/view"><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(),110);  ?></a></td>
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