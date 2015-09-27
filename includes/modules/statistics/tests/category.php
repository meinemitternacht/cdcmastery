<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:13 PM
 */

$statsObj = new statistics($db,$log,$emailQueue);

$afscList = $afsc->listAFSCUUID();

$chartData = "";
$firstRow = true;
$i=0;

$testAFSCArray = $statsObj->getTestAFSCCount($afscList);

$testAFSCData = "";
$firstRow = true;
$i=0;
foreach($testAFSCArray as $afscUUID => $testCount){
    if ($firstRow == false) {
        $testAFSCData .= ",";
    }

    $testAFSCData .= "{ x: " . $i . ", toolTipContent: \"" . $afsc->getAFSCName($afscUUID) . ": {y}\", y: " . $testCount . " }";
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
                    /*** Change type "column" to "bar", "area", "line" or "pie"***/
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
                    </tr>
                    <?php foreach($testAFSCArray as $afscUUID => $testCount): ?>
                        <tr>
                            <td><?php echo $afsc->getAFSCName($afscUUID); ?></td>
                            <td><?php echo $testCount; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>