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

$afscPassRateArray = $statsObj->getAFSCPassRates($afscList);

$afscPassRateData = "";
$firstRow = true;
$i=0;
foreach($afscPassRateArray as $afscUUID => $afscData){
    if($afscData['totalTests'] > 0) {
        if ($firstRow == false) {
            $afscPassRateData .= ",";
        }

        $afscPassRateData .= "{ x: " . $i . ", toolTipContent: \"" . $afsc->getAFSCName($afscUUID) . ": {y}%\", y: " . $afscData['passRate'] . " }";
        $firstRow = false;
        $i++;
    }
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "AFSC Pass Rate"
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
                        <?php echo $afscPassRateData; ?>
                    ]
                }
            ]
        });

        chart.render();
    }
</script>
<div class="container">
    <div class="row">
        <div class="4u">
            <?php include BASE_PATH . "/includes/modules/statistics/menu.php"; ?>
        </div>
        <div class="8u">
            <section>
                <div id="chart-container" style="height:400px">
                    &nbsp;
                </div>
                <table>
                    <tr>
                        <th>AFSC</th>
                        <th>Tests Taken</th>
                        <th>Tests Passed</th>
                        <th>Pass Rate</th>
                    </tr>
                    <?php foreach($afscPassRateArray as $afscUUID => $afscData): ?>
                        <?php if($afscData['totalTests'] > 0): ?>
                        <tr>
                            <td><?php echo $afsc->getAFSCName($afscUUID); ?></td>
                            <td><?php echo $afscData['totalTests']; ?></td>
                            <td><?php echo $afscData['passingTests']; ?></td>
                            <td><?php echo $afscData['passRate']; ?>%</td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>