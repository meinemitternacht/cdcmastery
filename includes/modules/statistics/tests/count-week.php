<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue);

$x=0;
for($i=2012;$i<=date("Y",time());$i++){
    for($j=1;$j<=52;$j++) {
        if($i==date("Y",time()) && $j > date("W",time())){
            continue;
        }
        else {
            $dateTimeStartObj = new DateTime();
            $dateTimeEndObj = new DateTime();

            $dateTimeStartObj->setISODate($i,$j);
            $dateTimeEndObj->setISODate($i,$j,7);

            $countData = $statsObj->getTestCountByTimespan($dateTimeStartObj, $dateTimeEndObj);

            $startLabel = $dateTimeStartObj->format("j M Y");
            $endLabel = $dateTimeEndObj->format("j M Y");
            $fullLabel = $startLabel . " - " . $endLabel;

            if($countData > 0) {
                $testCountByTimespanData[$x]['label'] = $fullLabel;
                $testCountByTimespanData[$x]['data'] = $countData;
                $x++;
            }
        }
    }
}

$testCountData = "";
$firstRow = true;
$i=0;
foreach($testCountByTimespanData as $rowKey => $rowData){
    if ($firstRow == false) {
        $testCountData .= ",";
    }

    $testCountData .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} tests</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Tests Taken by Week"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    /*** Change type "column" to "bar", "area", "line" or "pie"***/
                    type: "scatter",
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
                    <?php foreach($testCountByTimespanData as $rowKey => $rowData): ?>
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