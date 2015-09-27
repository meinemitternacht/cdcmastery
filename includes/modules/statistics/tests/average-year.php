<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue);

for($i=2012;$i<=date("Y",time());$i++){
    $dateTimeStartObj = new DateTime("January 1st ".$i);
    $dateTimeEndObj = new DateTime("December 31st ".$i);

    $testAverageByTimespanData[$i] = $statsObj->getTestAverageByTimespan($dateTimeStartObj,$dateTimeEndObj);
}

$testAverageData = "";
$firstRow = true;
$i=0;
foreach($testAverageByTimespanData as $testAverageYear => $testAverageScore){
    if ($firstRow == false) {
        $testAverageData .= ",";
    }

    $testAverageData .= "{ x: " . $i . ", label: \"" . $testAverageYear . "\", y: " . $testAverageScore . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Average Score by Year"
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
                        <th>Year</th>
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($testAverageByTimespanData as $testAverageYear => $testAverageScore): ?>
                        <tr>
                            <td><?php echo $testAverageYear; ?></td>
                            <td><?php echo $testAverageScore; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>