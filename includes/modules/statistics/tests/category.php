<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:13 PM
 */

$statsObj = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$afscList = $afscManager->listAFSCUUID(false);

$chartData = "";
$firstRow = true;
$i=0;

$testAFSCArray = $statsObj->getTestAFSCCount($afscList);

$testAFSCData = "";
$firstRow = true;
$i=0;
foreach($testAFSCArray as $afscUUID => $dataRow){
    if ($firstRow == false) {
        $testAFSCData .= ",";
    }

    $testAFSCData .= "{ x: " . $i . ", toolTipContent: \"" . $afscManager->getAFSCName($afscUUID) . ": {y}\", y: " . $dataRow['count'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Test Count by AFSC"
            },
            axisY:{
                valueFormatString: " ",
                tickLength: 0
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "column",
                    dataPoints: [
                        <?php echo $testAFSCData; ?>
                    ]
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
                        <th>AFSC</th>
                        <th>Tests Taken</th>
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($testAFSCArray as $afscUUID => $afscDataRow): ?>
                        <tr>
                            <td><?php echo $afscManager->getAFSCName($afscUUID); ?></td>
                            <td><?php echo number_format($afscDataRow['count']); ?></td>
                            <td><?php if($afscDataRow['average'] > 0) { echo number_format($afscDataRow['average'],2) . "%"; } else { echo "&nbsp;"; } ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>