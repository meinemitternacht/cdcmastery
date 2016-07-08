<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 8:15 PM
 */

$statsObj = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);

$userCountData = "{ label: \"Normal Users\", y: " . $statsObj->getTotalRoleUser() . " }";
$userCountData .= ",{ label: \"Supervisors\", y: " . $statsObj->getTotalRoleSupervisor() . " }";
$userCountData .= ",{ label: \"Training Managers\", y: " . $statsObj->getTotalRoleTrainingManager() . " }";
$userCountData .= ",{ label: \"Administrators\", y: " . $statsObj->getTotalRoleAdministrator() . " }";
$userCountData .= ",{ label: \"Super Administrators\", y: " . $statsObj->getTotalRoleSuperAdministrator() . " }";
$userCountData .= ",{ label: \"Editors\", y: " . $statsObj->getTotalRoleEditor() . " }";
?>
<script type="text/javascript">
    window.onload = function () {
        var chart = new CanvasJS.Chart("chart-container", {

            title:{
                text: "User Count by Group"
            },
            data: [
                {
                    type: "column",
                    dataPoints: [<?php echo $userCountData; ?>]
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
                        <th>Group</th>
                        <th>Accounts</th>
                    </tr>
                    <tr>
                        <td><strong>Normal Users</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleUser()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Supervisors</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleSupervisor()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Training Managers</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleTrainingManager()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Administrators</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleAdministrator()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Super Administrators</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleSuperAdministrator()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Editors</strong></td>
                        <td><?php echo number_format($statsObj->getTotalRoleEditor()); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>