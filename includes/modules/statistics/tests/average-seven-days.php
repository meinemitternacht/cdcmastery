<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$testAverageLastSevenArray = $statsObj->getTestAverageLastSeven();

$testAverageData = "";
$firstRow = true;
$i=0;

$arrayCount = count($testAverageLastSevenArray);
for($i=0;$i<$arrayCount;$i++){
    if ($firstRow == false) {
        $testAverageData .= ",";
    }

    $testAverageData .= "{ x: " . $i . ", label: \"" . $testAverageLastSevenArray[$i]['dateTime'] . "\", y: " . $testAverageLastSevenArray[$i]['averageScore'] . " }";
    $firstRow = false;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Average Score - Last Seven Days"
            },
            data: [
                {
                    /*** Change type "column" to "bar", "area", "line" or "pie"***/
                    type: "column",
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
                        <th>Date</th>
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($testAverageLastSevenArray as $testAverageTableData): ?>
                        <tr>
                            <td><?php echo $testAverageTableData['dateTime']; ?></td>
                            <td><?php echo $testAverageTableData['averageScore']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>