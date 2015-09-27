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

    $testCountByTimespanData[$i] = $statsObj->getTestCountByTimespan($dateTimeStartObj,$dateTimeEndObj);
}

$testCountData = "";
$firstRow = true;
$i=0;
foreach($testCountByTimespanData as $testYear => $testCount){
    if ($firstRow == false) {
        $testCountData .= ",";
    }

    $testCountData .= "{ x: " . $i . ", label: \"" . $testYear . "\", toolTipContent: \"".$testYear.":<br><strong>{y} tests</strong>\", y: " . $testCount . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Tests Taken by Year"
            },
            data: [
                {
                    /*** Change type "column" to "bar", "area", "line" or "pie"***/
                    type: "column",
                    dataPoints: [<?php echo $testCountData; ?>]
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
                        <th>Tests Taken</th>
                    </tr>
                    <?php foreach($testCountByTimespanData as $testYear => $testCount): ?>
                        <tr>
                            <td><?php echo $testYear; ?></td>
                            <td><?php echo $testCount; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>