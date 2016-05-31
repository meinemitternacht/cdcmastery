<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/6/2016
 * Time: 9:52 AM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$errorsByDay = $statsObj->getSystemErrorsByDay();

if($errorsByDay){
    $x=0;
    $dataTotal=0;
    foreach($errorsByDay as $errorDate => $errorValue){
        $errorData[$x]['label'] = $errorDate;
        $errorData[$x]['data'] = $errorValue;
        $dataTotal += $errorValue;
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.");
    $cdcMastery->redirect("/about/statistics");
}

$errorDataString = "";
$firstRow = true;
$i=0;
foreach($errorData as $rowKey => $rowData){
    if ($firstRow == false) {
        $errorDataString .= ",";
    }

    $errorDataString .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} errors</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Errors Logged by Day"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "area",
                    dataPoints: [<?php echo $errorDataString; ?>]
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
                        <th>Errors Logged</th>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo number_format($dataTotal); ?></strong></td>
                    </tr>
                    <?php foreach($errorData as $rowKey => $rowData): ?>
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