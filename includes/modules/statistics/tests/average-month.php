<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$testAverageByMonth = $statsObj->getTestAverageByMonth();

if($testAverageByMonth){
    $x=0;
    foreach($testAverageByMonth as $testDate => $testCount){
        $testAverageByMonthData[$x]['label'] = $testDate;
        $testAverageByMonthData[$x]['data'] = $testCount;
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.","info");
    $cdcMastery->redirect("/about/statistics");
}

$testAverageData = "";
$firstRow = true;
$i=0;
foreach($testAverageByMonthData as $rowKey => $rowData){
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
                text: "Tests Average by Month"
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
                        <th>Month</th>
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($testAverageByMonthData as $rowKey => $rowData): ?>
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