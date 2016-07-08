<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 7:28 PM
 */
$statsObj = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$baseList = $baseManager->listBases();

$chartTestsByBaseData = "";
$firstRow = true;
$i=0;
foreach($baseList as $baseUUID => $baseName){
    $testCount = $statsObj->getTotalTestsByBase($baseUUID);

    if($testCount > 10) {
        if ($firstRow == false) {
            $chartTestsByBaseData .= ",";
        }
        $tableData[$i]['baseUUID'] = $baseUUID;
        $tableData[$i]['baseName'] = $baseName;
        $tableData[$i]['testCount'] = $testCount;
        $chartTestsByBaseData .= "{ x: " . $i . ", toolTipContent: \"" . $baseName . ": {y}\", y: " . $testCount . " }";
        $firstRow = false;
        $i++;
    }
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Test Count by Base"
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
                    type: "column",
                    dataPoints: [
                        <?php echo $chartTestsByBaseData; ?>
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
                        <th>Base Name</th>
                        <th>Tests</th>
                    </tr>
                    <?php foreach($tableData as $tableRow): ?>
                        <tr>
                            <?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
                            <td><a href="/admin/base-overview/<?php echo $tableRow['baseUUID']; ?>"><?php echo $tableRow['baseName']; ?></a></td>
                            <?php else: ?>
                            <td><?php echo $tableRow['baseName']; ?></td>
                            <?php endif; ?>
                            <td><?php echo number_format($tableRow['testCount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>