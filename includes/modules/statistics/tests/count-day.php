<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new statistics($db,$log,$emailQueue);

function isLeapYear($year)
{
    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)));
}

$x=0;
for($i=2012;$i<=date("Y",time());$i++){
    $startDate = new DateTime("$i-01-01 00:00:00");
    $totalDays = isLeapYear($i) ? 366 : 365;
    for($j=1;$j<=$totalDays;$j++) {
        if ($i == date("Y", time()) && $j > date("z", time())) {
            continue;
        } else {
            $startDate->modify("+1 day");

            $dateTimeStartObj = new DateTime($startDate->format("Y-m-d 00:00:00"));
            $dateTimeEndObj = new DateTime($startDate->format("Y-m-d 23:59:59"));

            $countData = $statsObj->getTestCountByTimespan($dateTimeStartObj, $dateTimeEndObj);

            if($j < 10){
                $julian = "00" . $j;
            }
            elseif($j < 100){
                $julian = "0" . $j;
            }
            else{
                $julian = $j;
            }

            if($countData > 0){
                $testCountByTimespanData[$x]['label'] = $dateTimeStartObj->format("Y/m/d");
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