<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 1/9/2016
 * Time: 6:51 PM
 */
$statsObj = new statistics($db,$log,$emailQueue,$memcache);

$dayInterval = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : 30;

$endDateObj = new DateTime();
$startDateObj = new DateTime();
$startDateObj->modify("-30 days");

$baseList = $bases->listUserBases($startDateObj,$endDateObj);

$chartUsersByBaseData = "";
$firstRow = true;
$i=0;
foreach($baseList as $baseUUID => $baseName){
    $userCount = $statsObj->getActiveUsersByBase($baseUUID,$dayInterval);

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
                text: "User Base Composition - Last <?php echo $dayInterval; ?> Days"
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
                Last
                <?php if($dayInterval != 30): ?><a href="/about/statistics/base/user-composition/30">30</a> | <?php else: ?>30 | <?php endif; ?>
                <?php if($dayInterval != 60): ?><a href="/about/statistics/base/user-composition/60">60</a> | <?php else: ?>60 | <?php endif; ?>
                <?php if($dayInterval != 90): ?><a href="/about/statistics/base/user-composition/90">90</a> | <?php else: ?>90 | <?php endif; ?>
                <?php if($dayInterval != 120): ?><a href="/about/statistics/base/user-composition/120">120</a> | <?php else: ?>120 | <?php endif; ?>
                <?php if($dayInterval != 180): ?><a href="/about/statistics/base/user-composition/180">180</a> | <?php else: ?>180 | <?php endif; ?>
                <?php if($dayInterval != 365): ?><a href="/about/statistics/base/user-composition/365">365</a> <?php else: ?>365 <?php endif; ?>
                Days
                <table>
                    <tr>
                        <th>Base Name</th>
                        <th>Active Users</th>
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