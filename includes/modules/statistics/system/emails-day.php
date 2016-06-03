<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/6/2016
 * Time: 9:52 AM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$emailsByDay = $statsObj->getEmailsByDay();

if($emailsByDay){
    $x=0;
    $dataTotal=0;
    foreach($emailsByDay as $emailDate => $emailValue){
        $emailData[$x]['label'] = $emailDate;
        $emailData[$x]['data'] = $emailValue;
        $dataTotal += $emailValue;
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.","info");
    $cdcMastery->redirect("/about/statistics");
}

$emailDataString = "";
$firstRow = true;
$i=0;
foreach($emailData as $rowKey => $rowData){
    if ($firstRow == false) {
        $emailDataString .= ",";
    }

    $emailDataString .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} messages</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "E-mail Messages Sent by Day"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "area",
                    dataPoints: [<?php echo $emailDataString; ?>]
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
                        <th>E-mail Messages</th>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo number_format($dataTotal); ?></strong></td>
                    </tr>
                    <?php foreach($emailData as $rowKey => $rowData): ?>
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