<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/1/2015
 * Time: 8:26 AM
 */

$statsObj = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$testCountByHour = $statsObj->getTestsByHourOfDay();

$chartData = "";
$firstRow = true;
$i=0;
$dataTotal=0;
foreach($testCountByHour as $hourString => $testCount){
    if ($firstRow == false) {
        $chartData .= ",";
    }

    $chartData .= "{ x: " . $hourString . ", toolTipContent: \"<strong>" . $hourString . ":00 - " . $hourString . ":59" . "</strong><br>Tests: {y}\", y: " . $testCount . " }";
    $dataTotal += $testCount;
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Test Count by Hour of Day"
            },
            axisX:{
                interval: 1
            },
            data: [
                {
                    type: "area",
                    dataPoints: [
                        <?php echo $chartData; ?>
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
                        <th>Hour of Day</th>
                        <th>Tests Taken</th>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo number_format($dataTotal); ?></strong></td>
                    </tr>
                    <?php foreach($testCountByHour as $hourString => $testCount): ?>
                        <tr>
                            <td><?php echo $hourString . ":00 - " . $hourString . ":59"; ?></td>
                            <td><?php echo number_format($testCount); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>