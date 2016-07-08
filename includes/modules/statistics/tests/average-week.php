<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);
$testAverageByWeek = $statsObj->getTestAverageByWeek();

if($testAverageByWeek){
    $x=0;
    foreach($testAverageByWeek as $testDate => $testCount){
        $testAverageByWeekData[$x]['label'] = $testDate;
        $testAverageByWeekData[$x]['data'] = $testCount;
        $x++;
    }
}
else{
    $systemMessages->addMessage("That statistic contains no data.", "info");
    $cdcMastery->redirect("/about/statistics");
}

$testAverageData = "";
$firstRow = true;
$i=0;
foreach($testAverageByWeekData as $rowKey => $rowData){
    if ($firstRow == false) {
        $testAverageData .= ",";
    }

    $testAverageData .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>Average Score: {y}</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Tests Average by Week"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "spline",
                    dataPoints: [<?php echo $testAverageData; ?>]
                }
            ]
        });

        chart.render();
    }
</script>
<div class="container">
    <div class="row">
        <div class="3u">
            <?php include BASE_PATH . "/includes/modules/statistics/menu.php"; ?>
        </div>
        <div class="9u">
            <section>
                <div id="chart-container" style="height:400px">
                    &nbsp;
                </div>
                <table>
                    <tr>
                        <th>Week</th>
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($testAverageByWeekData as $rowKey => $rowData): ?>
                        <tr>
                            <td><?php echo $rowData['label']; ?></td>
                            <td><?php echo $rowData['data']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>