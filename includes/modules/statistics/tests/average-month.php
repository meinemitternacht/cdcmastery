<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);

$x=0;
for($i=2012;$i<=date("Y",time());$i++){
    for($j=1;$j<=12;$j++) {
        if($i==date("Y",time()) && $j > date("m",time())){
            continue;
        }
        else {
            $dateObj = DateTime::createFromFormat('!m', $j);
            $monthName = $dateObj->format('F');

            $dateTimeStartObj = new DateTime("first day of " . $monthName . " " . $i);
            $dateTimeEndObj = new DateTime("last day of " . $monthName . " " . $i);

            $averageData = $statsObj->getTestAverageByTimespan($dateTimeStartObj, $dateTimeEndObj);

            if($averageData > 0) {
                $testAverageByTimespanData[$x]['label'] = $monthName . " " . $i;
                $testAverageByTimespanData[$x]['data'] = $averageData;
                $x++;
            }
        }
    }
}

$testAverageData = "";
$firstRow = true;
$i=0;
foreach($testAverageByTimespanData as $rowKey => $rowData){
    if ($firstRow == false) {
        $testAverageData .= ",";
    }

    $testAverageData .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . "<br>Average: <strong>" . $rowData['data'] . "</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Average Score by Month"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
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
                    <?php foreach($testAverageByTimespanData as $rowKey => $rowData): ?>
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