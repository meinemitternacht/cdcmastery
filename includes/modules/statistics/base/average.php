<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 7:28 PM
 */
$statsObj = new statistics($db,$log,$emailQueue,$memcache);

$baseList = $bases->listBases();

$chartScoreByBaseData = "";
$firstRow = true;
$i=0;
foreach($baseList as $baseUUID => $baseName){
    $averageScore = $statsObj->getAverageScoreByBase($baseUUID);

    if($averageScore > 0) {
        if ($firstRow == false) {
            $chartScoreByBaseData .= ",";
        }
        $tableData[$i]['baseUUID'] = $baseUUID;
        $tableData[$i]['baseName'] = $baseName;
        $tableData[$i]['averageScore'] = $averageScore;
        $chartScoreByBaseData .= "{ x: " . $i . ", toolTipContent: \"" . $baseName . ": {y}\", y: " . $averageScore . " }";
        $firstRow = false;
        $i++;
    }
}
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "Average Score by Base"
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
                    /*** Change type "column" to "bar", "area", "line" or "pie"***/
                    type: "column",
                    dataPoints: [
                        <?php echo $chartScoreByBaseData; ?>
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
                        <th>Average Score</th>
                    </tr>
                    <?php foreach($tableData as $tableRow): ?>
                        <tr>
                            <?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
                                <td><a href="/admin/base-overview/<?php echo $tableRow['baseUUID']; ?>"><?php echo $tableRow['baseName']; ?></a></td>
                            <?php else: ?>
                                <td><?php echo $tableRow['baseName']; ?></td>
                            <?php endif; ?>
                            <td><?php echo $tableRow['averageScore']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
</div>