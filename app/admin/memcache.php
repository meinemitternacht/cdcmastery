<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 2/2/2016
 * Time: 6:30 PM
 */
if(!$cdcMastery->verifyAdmin()){
    $systemMessages->addMessage("You are not authorized to view that page.", "danger");
    $cdcMastery->redirect("/errors/403");
}
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Memcache Statistics</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="6u">
            <section>
                <?php $memcacheStatus = $memcache->getstats(); ?>
                <?php
                function secondsToTime($seconds) {
                    $dtF = new DateTime("@0");
                    $dtT = new DateTime("@$seconds");
                    return $dtF->diff($dtT)->format('%ad %hh %im %ss');
                }

                $memcacheUptime = secondsToTime($memcacheStatus['uptime']);
                ?>
                <table>
                    <tr>
                        <td>Server version</td>
                        <td> <?php echo $memcacheStatus["version"]; ?></td>
                    </tr>
                    <tr>
                        <td>Process ID</td><td><?php echo $memcacheStatus["pid"]; ?></td>
                    </tr>
                    <tr>
                        <td>Process uptime</td>
                        <td><?php echo $memcacheUptime; ?></td>
                    </tr>
                    <tr>
                        <td>Process time (user)</td>
                        <td><?php echo number_format(intval($memcacheStatus["rusage_user"])); ?>s</td>
                    </tr>
                    <tr>
                        <td>Process time (system)</td>
                        <td><?php echo number_format(intval($memcacheStatus["rusage_system"])); ?>s</td>
                    </tr>
                    <tr>
                        <td>Items stored (total)</td>
                        <td><?php echo number_format($memcacheStatus["total_items"]); ?></td>
                    </tr>
                    <tr>
                        <td>Current connections</td>
                        <td><?php echo number_format($memcacheStatus["curr_connections"]); ?></td>
                    </tr>
                    <tr>
                        <td>Connections opened</td>
                        <td><?php echo number_format($memcacheStatus["total_connections"]); ?></td>
                    </tr>
                    <tr>
                        <td>Connection structures allocated</td>
                        <td><?php echo number_format($memcacheStatus["connection_structures"]); ?></td>
                    </tr>
                    <tr>
                        <td>Retrieval requests </td>
                        <td><?php echo number_format($memcacheStatus["cmd_get"]); ?></td>
                    </tr>
                    <tr>
                        <td>Storage requests </td>
                        <td><?php echo number_format($memcacheStatus["cmd_set"]); ?></td>
                    </tr>
                    <?php
                    $pctCacheHit = round(((real)$memcacheStatus["get_hits"]/(real)$memcacheStatus["cmd_get"] *100),2);
                    $pctCacheHit = round($pctCacheHit,2);
                    $pctCacheMiss = (100 - $pctCacheHit);
                    ?>
                    <tr>
                        <td>Objects found</td>
                        <td><?php echo number_format($memcacheStatus["get_hits"]); ?> (<?php echo $pctCacheHit; ?>%)</td>
                    </tr>
                    <tr>
                        <td>Objects not found</td>
                        <td><?php echo number_format($memcacheStatus["get_misses"]); ?> (<?php echo $pctCacheMiss; ?>%)</td>
                    </tr>
                    <?php
                    $MBRead = round((real)$memcacheStatus["bytes_read"]/(1024*1024),2);
                    $MBWrite = round((real)$memcacheStatus["bytes_written"]/(1024*1024),2);
                    $MBSize = round((real)$memcacheStatus["limit_maxbytes"]/(1024*1024),2);
                    ?>
                    <tr>
                        <td>Rx Bytes</td>
                        <td><?php echo $MBRead; ?> MB</td>
                    </tr>
                    <tr>
                        <td>Tx Bytes</td>
                        <td><?php echo $MBWrite; ?> MB</td>
                    </tr>
                    <tr>
                        <td>Storage pool size</td>
                        <td><?php echo $MBSize; ?> MB</td>
                    </tr>
                    <tr>
                        <td>Items removed</td>
                        <td><?php echo $memcacheStatus["evictions"]; ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>
