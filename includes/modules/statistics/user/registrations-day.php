<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/6/2016
 * Time: 9:52 AM
 */

$statsObj = new statistics($db,$log,$emailQueue,$memcache);
$registrationsByDay = $statsObj->getRegistrationsByDay();

if($registrationsByDay){
    $x=0;
    foreach($registrationsByDay as $registrationArray){
        $registrationData[$x]['label'] = $registrationArray[0];
        $registrationData[$x]['data'] = $registrationArray[1];
        $x++;
    }
}
else{
    $sysMsg->addMessage("That statistic contains no data.");
    $cdcMastery->redirect("/about/statistics");
}

$registrationDataString = "";
$firstRow = true;
$i=0;
foreach($registrationData as $rowKey => $rowData){
    if ($firstRow == false) {
        $registrationDataString .= ",";
    }

    $registrationDataString .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['label'] . ":<br><strong>{y} registrations</strong>\", y: " . $rowData['data'] . " }";
    $firstRow = false;
    $i++;
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Registrations by Day"
            },
            axisX:{
                valueFormatString: " ",
                tickLength: 0
            },
            data: [
                {
                    type: "area",
                    dataPoints: [<?php echo $registrationDataString; ?>]
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
                        <th>Registrations</th>
                    </tr>
                    <?php foreach($registrationData as $rowKey => $rowData): ?>
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