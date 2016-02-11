<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/1/2015
 * Time: 8:59 AM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$testsByDayOfMonth = $statsObj->getTestsByDayOfMonth();

$chartData = "";
$firstRow = true;
$i=0;
foreach($testsByDayOfMonth as $dayString => $testCount){
    if ($firstRow == false) {
        $chartData .= ",";
    }

    $chartData .= "{ x: " . $dayString . ", toolTipContent: \"<strong>" . $cdcMastery->getOrdinal($dayString) . "</strong><br>Tests: {y}\", y: " . $testCount . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Test Count by Day of Month"
            },
            axisX:{
                interval: 1
            },
            data: [
                {
                    type: "column",
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
                        <th>Day of Month</th>
                        <th>Tests Taken</th>
                    </tr>
                    <?php foreach($testsByDayOfMonth as $dayString => $testCount): ?>
                        <tr>
                            <td><?php echo $cdcMastery->getOrdinal($dayString); ?></td>
                            <td><?php echo $testCount; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>