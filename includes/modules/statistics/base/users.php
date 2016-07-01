<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 1/9/2016
 * Time: 6:51 PM
 */
$statsObj = new StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$baseList = $baseManager->listBases();

$chartUsersByBaseData = "";
$firstRow = true;
$i=0;
foreach($baseList as $baseUUID => $baseName){
    $userCount = $statsObj->getTotalUsersByBase($baseUUID);

    if($userCount > 0) {
        if ($firstRow == false) {
            $chartUsersByBaseData .= ",";
        }
        $tableData[$i]['baseUUID'] = $baseUUID;
        $tableData[$i]['baseName'] = $baseName;
        $tableData[$i]['userCount'] = $userCount;
        $chartUsersByBaseData .= "{ x: " . $i . ", toolTipContent: \"" . $baseName . ": {y}\", y: " . $userCount . " }";
        $firstRow = false;
        $i++;
    }
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "User Count by Base"
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
                        <?php echo $chartUsersByBaseData; ?>
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
                        <th>Users</th>
                    </tr>
                    <?php foreach($tableData as $tableRow): ?>
                        <tr>
                            <?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
                            <td><a href="/admin/base-overview/<?php echo $tableRow['baseUUID']; ?>"><?php echo $tableRow['baseName']; ?></a></td>
                            <?php else: ?>
                            <td><?php echo $tableRow['baseName']; ?></td>
                            <?php endif; ?>
                            <td><?php echo number_format($tableRow['userCount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>