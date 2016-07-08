<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/21/15
 * Time: 1:21 AM
 */

if(isset($_SESSION['vars'][0])){
    $supervisorUUID = $_SESSION['vars'][0];
}
else{
    $supervisorUUID = $_SESSION['userUUID'];
}

if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
    $systemMessages->addMessage("You are not authorized to use the Supervisor Overview.", "danger");
    $cdcMastery->redirect("/errors/403");
}

if($roleManager->getRoleType($userManager->getUserRoleByUUID($supervisorUUID)) != "supervisor"){
    $systemMessages->addMessage("That user is not a Supervisor.", "warning");
    $cdcMastery->redirect("/errors/500");
}

$supUser = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$supOverview = new CDCMastery\SupervisorOverview($db, $systemLog, $userStatistics, $supUser, $roleManager);

$supOverview->loadSupervisor($supervisorUUID);

$subordinateUsers = $userManager->sortUserUUIDList($supOverview->getSubordinateUserList(), "userLastName");

if(empty($subordinateUsers)):
    $systemMessages->addMessage("You do not have any subordinate users.  Please associate users with your account using the form below.", "info");
    $cdcMastery->redirect("/supervisor/subordinates");
endif;

$totalUserTestCount = $supOverview->getTotalUserTests();
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Supervisor Overview</h2>
                </header>
            </section>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php if(!empty($subordinateUsers)): ?>
            <style type="text/css">
                table.overview-table tr td {
                    display:table-cell;
                    vertical-align:middle;
                    height: 2.3em;
                }
            </style>
            <div class="9u">
                <section>
                    <h2>User Data</h2>
                    <?php if($totalUserTestCount > 0): ?>
                        <div id="chart-container" style="height:400px">
                            &nbsp;
                        </div>
                    <?php endif; ?>
                    <table class="overview-table">
                        <tr>
                            <th>User Name</th>
                            <th>Total Tests</th>
                            <th>Average Score</th>
                            <th>Latest Score</th>
                            <th>Last Login</th>
                        </tr>
                        <?php
                        $i=0;
                        foreach($subordinateUsers as $subordinateUser):
                            if($supUser->loadUser($subordinateUser)){
                                $userStatistics->setUserUUID($subordinateUser);
                                $userAverage = round($userStatistics->getAverageScore(),2);
                                $userLatestScore = $userStatistics->getLatestTestScore();
                                $userTestCount = $userStatistics->getTotalTests();

                                if($userTestCount > 0) {
                                    $chartData[$i]['userName'] = $supUser->getFullName();
                                    $chartData[$i]['userAverage'] = $userAverage;
                                    $chartData[$i]['userTestCount'] = $userTestCount;
                                    $i++;
                                }
                                ?>
                                <tr>
                                    <td><a href="/supervisor/profile/<?php echo $supUser->getUUID(); ?>"><?php echo $supUser->getFullName(); ?></a></td>
                                    <td><?php echo $userStatistics->getTotalTests(); ?> <span class="text-float-right"><a href="/supervisor/history/<?php echo $supUser->getUUID(); ?>">[view]</a></span></td>
                                    <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                                    <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                                    <td>
                                        <time class="timeago" datetime="<?php echo ($supUser->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($supUser->getUserLastLogin(),$_SESSION['timeZone'],"c"); ?>">
                                            <?php echo ($supUser->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($supUser->getUserLastLogin(),$_SESSION['timeZone'],"j-M-Y \a\\t h:i A");  ?>
                                        </time>
                                    </td>
                                </tr>
                            <?php
                            }
                        endforeach; ?>
                    </table>
                    <?php
                    if(isset($chartData)):
                        $chartOutputData = "";
                        $firstRow = true;
                        $i=0;
                        foreach($chartData as $rowKey => $rowData){
                            if ($firstRow == false) {
                                $chartOutputData .= ",";
                            }

                            $chartOutputData .= "{ x: " . $i . ", toolTipContent: \"<strong>" . $rowData['userName'] . "</strong><br>Average: <strong>{y}</strong><br>Tests: <strong>" . $rowData['userTestCount'] . "</strong>\", y: " . $rowData['userAverage'] . " }";
                            $firstRow = false;
                            $i++;
                        }
                        ?>
                        <script type="text/javascript">
                            window.onload = function () {
                                var chart = new CanvasJS.Chart("chart-container", {

                                    title:{
                                        text: "Testing Overview"
                                    },
                                    axisX:{
                                        valueFormatString: " ",
                                        tickLength: 0
                                    },
                                    data: [
                                        {
                                            type: "column",
                                            dataPoints: [<?php echo $chartOutputData; ?>]
                                        }
                                    ]
                                });

                                chart.render();
                            }
                        </script>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
        <div class="3u">
            <section>
                <h2>Actions</h2>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/supervisor/subordinates" title="Modify Subordinates">Modify Subordinates</a></li>
                        <li><a href="/supervisor/generate-test" title="Generate Test">Create Paper Tests</a></li>
                    </ul>
                </div>
            </section>
            <div class="clearfix">&nbsp;</div>
            <section>
                <h2>Statistics</h2>
                <table>
                    <tr>
                        <th>Statistic</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Subordinate Users</td>
                        <td><?php echo count($supOverview->getSubordinateUserList()); ?></td>
                    </tr>
                    <tr>
                        <td>Total User Tests</td>
                        <td><?php echo $totalUserTestCount ?></td>
                    </tr>
                    <tr>
                        <td>Average User Test Score</td>
                        <td><?php echo $supOverview->getAverageUserTestScore(); ?></td>
                    </tr>
                </table>
            </section>
            <?php
            $superAFSCUUIDArray = $supOverview->getUserAFSCAssociations();

            if(is_array($superAFSCUUIDArray) && !empty($superAFSCUUIDArray)): ?>
                <div class="clearfix">&nbsp;</div>
                <section>
                    <h2>Top Questions Missed</h2>
                    <ul>
                        <?php foreach($superAFSCUUIDArray as $superAFSCUUID): ?>
                            <li><a href="/supervisor/top-missed/<?php echo $superAFSCUUID; echo (!empty($_SESSION['vars'][0])) ? "/".$_SESSION['vars'][0] : ""; ?>"><?php echo $afscManager->getAFSCName($superAFSCUUID); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="clearfix"><br></div>