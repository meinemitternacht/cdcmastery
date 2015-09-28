<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 9/25/15
 * Time: 1:54 AM
 */
$baseUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$baseUUID){
    $baseUUID = $user->getUserBase();
    if(empty($baseUUID)){
        $sysMsg->addMessage("Your account settings do not specify a base.");
        $cdcMastery->redirect("/errors/500");
    }
}

if(isset($_POST['baseUUID']) && !empty($_POST['baseUUID'])){
    if($bases->loadBase($_POST['baseUUID'])){
        $baseUUID = $_POST['baseUUID'];
    }
    else{
        $sysMsg->addMessage("Invalid base specified.");
    }
}

$statistics = new statistics($db,$log,$emailQueue);
$baseUserObj = new user($db,$log,$emailQueue);
$userStatisticsObj = new userStatistics($db,$log,$roles);
$baseUsersUUIDList = $user->listUserUUIDByBase($baseUUID);
$baseUsers = $user->sortUserUUIDList($baseUsersUUIDList,"userLastName");
$baseTestCount = $statistics->getTotalTestsByBase($baseUUID);
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Base Overview for <?php echo $bases->getBaseName($baseUUID); ?></h2>
                </header>
            </section>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php if(!empty($baseUsers)): ?>
        <style type="text/css">
            table.overview-table tr td {
                display:table-cell;
                vertical-align:middle;
                height: 2.3em;
            }
        </style>
        <div class="9u">
            <section>
                <h2>Testing Data</h2>
                <?php if($baseTestCount > 0): ?>
                <div id="chart-container" style="height:400px">
                    &nbsp;
                </div>
                <?php endif; ?>
                <table class="overview-table">
                    <tr>
                        <th>User Name</th>
                        <th>Total Tests</th>
                        <th>Average Score</th>
                        <th>Last Score</th>
                        <th>Last Login</th>
                    </tr>
                    <?php
                    $i=0;
                    foreach($baseUsers as $baseUser):
                        $baseUserObj->loadUser($baseUser);
                        $userStatisticsObj->setUserUUID($baseUser);
                        $userAverage = round($userStatisticsObj->getAverageScore(),2);
                        $userLatestScore = $userStatisticsObj->getLatestTestScore();
                        $userTestCount = $userStatisticsObj->getTotalTests();

                        if($userTestCount > 0) {
                            $chartData[$i]['userName'] = $baseUserObj->getFullName();
                            $chartData[$i]['userAverage'] = $userAverage;
                            $chartData[$i]['userTestCount'] = $userTestCount;
                            $i++;
                        }
                        ?>
                        <tr>
                            <td><a href="/admin/profile/<?php echo $baseUserObj->getUUID(); ?>"><?php echo $baseUserObj->getFullName(); ?></a></td>
                            <td><?php echo $userTestCount; ?> <span class="text-float-right"><a href="/admin/users/<?php echo $baseUserObj->getUUID(); ?>/tests">[view]</a></span></td>
                            <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                            <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                            <td>
                                <abbr class="timeago" title="<?php echo ($baseUserObj->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($baseUserObj->getUserLastLogin(),$_SESSION['timeZone'],"c"); ?>">
                                    <?php echo ($baseUserObj->getUserLastLogin() == "Never") ? "Never" : $cdcMastery->outputDateTime($baseUserObj->getUserLastLogin(),$_SESSION['timeZone'],"j-M-Y \a\\t h:i A");  ?>
                                </abbr>
                            </td>
                        </tr>
                    <?php endforeach;?>
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
                                    text: "Base Testing Overview"
                                },
                                axisX:{
                                    valueFormatString: " ",
                                    tickLength: 0
                                },
                                data: [
                                    {
                                        /*** Change type "column" to "bar", "area", "line" or "pie"***/
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
        <div class="9u">
            <section>
                <p>There are no users with test data at this base.</p>
            </section>
        </div>
        <?php endif; ?>
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
                                $baseList = $bases->listBases();
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
            <div class="clearfix">&nbsp;</div>
            <section>
                <h2>Statistics</h2>
                <table>
                    <tr>
                        <th>Statistic</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Base Users</td>
                        <td><?php echo count($baseUsers); ?></td>
                    </tr>
                    <tr>
                        <td>Total Tests</td>
                        <td><?php echo $statistics->getTotalTestsByBase($baseUUID); ?></td>
                    </tr>
                    <tr>
                        <td>Average Test Score</td>
                        <td><?php echo $statistics->getAverageScoreByBase($baseUUID); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>
<div class="clearfix"><br></div>