<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$testCountByDay = $statsObj->getTestCountByDay();

if($testCountByDay){
    $x=0;
    foreach($testCountByDay as $testDate => $testCount){
        $testCountByTimespanData[$x]['label'] = $testDate;
        $testCountByTimespanData[$x]['data'] = $testCount;
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.");
    $cdcMastery->redirect("/about/statistics");
}

$testCountData = "";
$firstRow = true;
$i=0;
$dataTotal=0;
foreach($testCountByTimespanData as $rowKey => $rowData){
    if ($firstRow == false) {
        $testCountData .= ",";
    }

    $testCountData .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} tests</strong>\", y: " . $rowData['data'] . " }";
    $dataTotal += $rowData['data'];
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Tests Taken by Day"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "area",
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
                        <th>Day</th>
                        <th>Tests Taken</th>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo number_format($dataTotal); ?></strong></td>
                    </tr>
                    <?php foreach($testCountByTimespanData as $rowKey => $rowData): ?>
                        <tr>
                            <td><?php echo $rowData['label']; ?></td>
                            <td><?php echo number_format($rowData['data']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>