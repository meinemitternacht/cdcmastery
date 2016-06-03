<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/6/2016
 * Time: 9:52 AM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$loginsByDay = $statsObj->getLoginsByDay();

if($loginsByDay){
    $x=0;
    foreach($loginsByDay as $loginsDate => $loginsValue){
        $loginsData[$x]['label'] = $loginsDate;
        $loginsData[$x]['data'] = $loginsValue;
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.","info");
    $cdcMastery->redirect("/about/statistics");
}

$loginsDataString = "";
$firstRow = true;
$i=0;
foreach($loginsData as $rowKey => $rowData){
    if ($firstRow == false) {
        $loginsDataString .= ",";
    }

    $loginsDataString .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} log-ins</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "User Log-ins by Day"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "area",
                    dataPoints: [<?php echo $loginsDataString; ?>]
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
                        <th>Log-ins</th>
                    </tr>
                    <?php foreach($loginsData as $rowKey => $rowData): ?>
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