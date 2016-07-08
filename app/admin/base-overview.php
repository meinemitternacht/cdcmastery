<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 9/25/15
 * Time: 1:54 AM
 */
$baseUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$baseUUID){
    $baseUUID = $userManager->getUserBase();
    if(empty($baseUUID)){
        $systemMessages->addMessage("Your account settings do not specify a base.", "danger");
        $cdcMastery->redirect("/errors/500");
    }
}

if(isset($_POST['baseUUID']) && !empty($_POST['baseUUID'])){
    if($baseManager->loadBase($_POST['baseUUID'])){
        $baseUUID = $_POST['baseUUID'];
    }
    else{
        $systemMessages->addMessage("Invalid base specified.", "warning");
    }
}

$filterDateObj = new DateTime();
$filterDateObj->modify("-6 month");

$statistics = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);
$baseUserObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
$userStatisticsObj = new CDCMastery\UserStatisticsModule($db, $systemLog, $roleManager, $memcache);
$baseUsersUUIDList = $userManager->listUserUUIDByBase($baseUUID);
$filteredBaseUsersUUIDList = $userManager->filterUserUUIDList($baseUsersUUIDList, "userLastActive", ">", $filterDateObj->format("Y-m-d H:i:s"));
$baseUsers = $userManager->sortUserUUIDList($filteredBaseUsersUUIDList, "userLastName");
$baseTestCount = $statistics->getTotalTestsByBase($baseUUID);
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Base Overview for <?php echo $baseManager->getBaseName($baseUUID); ?></h2>
                </header>
                <p>
                    <em>Note: This base overview will only display data for individuals who have been active during the previous six months.</em>
                </p>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="3u">
            <section>
                <h2>Actions</h2>
                <ul>
                    <li>
                        <form action="/admin/base-overview" method="POST">
                            <label for="baseUUID">Base</label>
                            <select id="baseUUID"
                                    name="baseUUID"
                                    class="input_full"
                                    size="1">
                                <?php
                                $baseList = $baseManager->listUserBases();
                                foreach($baseList as $baseListUUID => $baseName): ?>
                                    <?php if($baseUUID == $baseListUUID): ?>
                                        <option value="<?php echo $baseListUUID; ?>" SELECTED><?php echo $baseName; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $baseListUUID; ?>"><?php echo $baseName; ?></option>
                                    <?php endif;
                                endforeach;
                                ?>
                            </select>
                            <div class="clearfix">&nbsp;</div>
                            <input type="submit" value="Change Base">
                        </form>
                    </li>
                </ul>
            </section>
        </div>
        <div class="3u">
            <section>
                <h2>Statistics</h2>
                <table id="baseStatsTable">
                    <tr>
                        <th>Statistic</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Base Users</td>
                        <td><?php echo number_format(count($baseUsers)); ?></td>
                    </tr>
                    <tr>
                        <td>Total Tests</td>
                        <td><?php echo number_format($statistics->getTotalTestsByBase($baseUUID)); ?>*</td>
                    </tr>
                    <tr>
                        <td>Average Test Score</td>
                        <td><?php echo number_format($statistics->getAverageScoreByBase($baseUUID),2); ?>*</td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
    <div class="row">
        <?php if(!empty($baseUsers)): ?>
        <script>
            $(document).ready(function()
                {
                    $("#baseOverviewTable").tablesorter();
                }
            );
        </script>
        <style type="text/css">
            table.overview-table tr td {
                display:table-cell;
                vertical-align:middle;
                height: 2.3em;
            }
        </style>
        <div class="12u">
            <section>
                <h2>Testing Data</h2>
                <?php if($baseTestCount > 0): ?>
                <div id="chart-container" style="height:400px">
                    &nbsp;
                </div>
                <?php endif; ?>
                <em>Click on column headers to sort the table</em>
                <table class="overview-table" id="baseOverviewTable">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Total Tests</th>
                            <th>Average Score</th>
                            <th>Latest Score</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i=0;
                    foreach($baseUsers as $baseUser):
                        $baseUserObj->loadUser($baseUser);
                        $userStatisticsObj->setUserUUID($baseUser);
                        $userAverage = round($userStatisticsObj->getAverageScore(),2);
                        $userLatestScore = $userStatisticsObj->getLatestTestScore();
                        $userTestCount = $userStatisticsObj->getCompletedTests();

                        if($userTestCount > 0):
                            $chartData[$i]['userName'] = $baseUserObj->getUserLastName() . ", " . $baseUserObj->getUserFirstName() . " " . $baseUserObj->getUserRank();
                            $chartData[$i]['userAverage'] = $userAverage;
                            $chartData[$i]['userTestCount'] = $userTestCount;
                            $i++;
                            ?>
                            <tr>
                                <td><a href="/admin/profile/<?php echo $baseUserObj->getUUID(); ?>"><?php echo $baseUserObj->getUserLastName() . ", " . $baseUserObj->getUserFirstName() . " " . $baseUserObj->getUserRank(); ?></a></td>
                                <td><?php echo $userTestCount; ?></td>
                                <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                                <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                                <td>
                                    <time class="timeago" datetime="<?php echo ($baseUserObj->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($baseUserObj->getUserLastLogin(),$_SESSION['timeZone'],"c"); ?>">
                                        <?php echo ($baseUserObj->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($baseUserObj->getUserLastLogin(),$_SESSION['timeZone'],"j-M-Y \a\\t h:i A");  ?>
                                    </time>
                                </td>
                                <td><a href="/admin/users/<?php echo $baseUserObj->getUUID(); ?>/tests">[View Tests]</a></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach;?>
                    </tbody>
                </table>
                <?php
                if(isset($chartData)):
                    function compareArrayValues($a, $b) {
                        return ($b["userAverage"]*100) - ($a["userAverage"]*100);
                    }

                    usort($chartData, "compareArrayValues");

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
        <?php else: ?>
        <div class="12u">
            <section>
                <p>There are no users with test data at this base.</p>
            </section>
        </div>
        <?php endif; ?>
        <div class="12u">
            <section>
                <p>
                    <em>* denotes these statistics are for all users at this base, regardless of when they were last active</em>
                </p>
            </section>
        </div>
    </div>
</div>
<div class="clearfix"><br></div>