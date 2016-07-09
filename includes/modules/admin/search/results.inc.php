<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/31/2016
 * Time: 5:58 PM
 */
$numSearchResults = $_SESSION['searchData']['searchResultCount'];
$searchResults = $_SESSION['searchData']['searchResults'];
$searchType = $_SESSION['searchData']['searchType'];

$pageNumber = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : 0;

if($searchType == "log"){
    $pageRows = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : 10;
}
else{
    $pageRows = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : 25;
}

if(!$searchResults || !$numSearchResults){
    $systemMessages->addMessage("There are no search results stored in the session.  Try your search again.", "info");
    $cdcMastery->redirect("/admin/search");
}

$totalPages = ceil($numSearchResults / $pageRows) - 1;

if($pageNumber <= 0){
    $rowOffset = 0;
}
else{
    $rowOffset = $pageNumber * $pageRows;
}

$paginatedResults = array_slice($searchResults,$rowOffset,$pageRows);
?>
<div class="container">
    <div class="row">
        <div class="3u">
            <div class="sub-menu">
                <ul>
                    <li><a href="/admin/search"><i class="icon-inline icon-20 ic-arrow-left"></i>New Search</a></li>
                </ul>
            </div>
            <div class="clearfix">&nbsp;</div>
            <div class="sub-menu">
                <ul>
                    <?php if($pageNumber > 0): ?><li><a href="/admin/search/results/0">&laquo; First</a></li><?php endif; ?>
                    <?php if($pageNumber > 1): ?><li><a href="/admin/search/results/<?php echo ($pageNumber - 1); ?>">&lt; Previous</a></li><?php endif; ?>
                    <?php if($pageNumber < ($totalPages - 1)): ?><li><a href="/admin/search/results/<?php echo ($pageNumber + 1); ?>">&gt; Next</a></li><?php endif; ?>
                    <?php if($pageNumber < $totalPages): ?><li><a href="/admin/search/results/<?php echo $totalPages; ?>">&raquo; Last</a></li><?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="9u">
            <section>
                <div class="text-right">
                    <strong>Showing results <?php echo ($rowOffset + 1); ?> - <?php if(($rowOffset + $pageRows) > $numSearchResults){ echo $numSearchResults; } else { echo ($rowOffset + $pageRows); } ?> out of <?php echo number_format($numSearchResults); ?> search result<?php echo ($numSearchResults > 1) ? "s" : ""; ?></strong>
                </div>
                <?php if($searchType == "user"): ?>
                    <table>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Rank</th>
                            <th>Base</th>
                            <th>Last Login</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($paginatedResults as $result): ?>
                            <?php
                            $userObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
                            $userObj->loadUser($result);
                            ?>
                            <tr>
                                <td><?php echo $userObj->getUserLastName(); ?></td>
                                <td><?php echo $userObj->getUserFirstName(); ?></td>
                                <td><?php echo $userObj->getUserRank(); ?></td>
                                <td><?php echo $baseManager->getBaseName($userObj->getUserBase()); ?></td>
                                <td><?php echo $cdcMastery->outputDateTime($userObj->getUserLastLogin(),$_SESSION['timeZone'],"j M Y H:i"); ?></td>
                                <td><?php echo $roleManager->getRoleName($userObj->getUserRole()); ?></td>
                                <td><a href="/admin/profile/<?php echo $result; ?>">Profile &raquo;</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php elseif($searchType == "log"): ?>
                    <?php $bgColor = "#ffffff;"; ?>
                    <?php foreach($paginatedResults as $searchResult): ?>
                        <?php $systemLog->loadEntry($searchResult); ?>
                        <?php $logDetails = $systemLog->fetchDetails($searchResult); ?>
                        <table class="logSearchTable">
                            <tr style="background-color:<?php echo $bgColor; ?>">
                                <td><strong>Log Action</strong></td>
                                <td><strong>Date</strong></td>
                                <td><strong>Action Performed By</strong></td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr style="background-color:<?php echo $bgColor; ?>">
                                <td><span class="<?php echo $systemLog->getRowStyle($systemLog->getAction()); ?>"><?php echo $systemLog->getAction(); ?></span></td>
                                <td><?php echo $cdcMastery->outputDateTime($systemLog->getTimestamp(), $_SESSION['timeZone']); ?></td>
                                <?php if(!in_array($systemLog->getUserUUID(), $cdcMastery->getStaticUserArray())): ?>
                                    <td><a href="/admin/users/<?php echo $systemLog->getUserUUID(); ?>" title="Manage User"><?php echo $userManager->getUserNameByUUID($systemLog->getUserUUID()); ?></a></td>
                                <?php else: ?>
                                    <td><?php echo $userManager->getUserNameByUUID($systemLog->getUserUUID()); ?></td>
                                <?php endif; ?>
                                <td><a href="/admin/log-detail/<?php echo $systemLog->getUUID(); ?>">View &raquo;</a></td>
                            </tr>
                        </table>
                        <?php if($logDetails): ?>
                            <?php $testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager); ?>
                            <table class="logSearchTable" style="width:auto;">
                                <tr style="background-color:<?php echo $bgColor; ?>">
                                    <td style="min-width: 8em;"><strong>Key</strong></td>
                                    <td><strong>Data</strong></td>
                                </tr>
                                <?php foreach($logDetails as $detailKey => $detailData):
                                    if($cdcMastery->is_serialized($detailData['data'])): ?>
                                        <tr>
                                            <td><?php echo $detailData['dataType']; ?></td>
                                            <td>
                                                <?php
                                                $data = unserialize($detailData['data']);
                                                if(is_array($data)){
                                                    $dataCount = count($data);
                                                    $i = 1;
                                                    foreach($data as $dataVal){
                                                        $linkStr = $systemLog->formatDetailData($dataVal);

                                                        if($i < $dataCount){
                                                            echo $linkStr . ", " . PHP_EOL;
                                                        }
                                                        else{
                                                            echo $linkStr . PHP_EOL;
                                                        }
                                                        $i++;
                                                    }
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    else:
                                        $linkStr = $systemLog->formatDetailData($detailData['data']);
                                        ?>
                                        <tr>
                                            <td><?php echo $detailData['dataType']; ?></td>
                                            <td><?php echo $linkStr; ?></td>
                                        </tr>
                                        <?php
                                    endif;
                                    ?>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>
                        <div style="width:100%;border-bottom: 1px dashed #ccc;margin-bottom:1em;">&nbsp;</div>
                        <?php $bgColor = ($bgColor == "#ffffff;") ? "#ccccc;" : "#fffff;"; ?>
                    <?php endforeach; ?>
                <?php elseif($searchType == "AFSCassociations"): ?>
                    <table>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Rank</th>
                            <th>Base</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($paginatedResults as $result): ?>
                            <?php
                            $userObj = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
                            $userObj->loadUser($result);
                            ?>
                            <tr>
                                <td><?php echo $userObj->getUserLastName(); ?></td>
                                <td><?php echo $userObj->getUserFirstName(); ?></td>
                                <td><?php echo $userObj->getUserRank(); ?></td>
                                <td><?php echo $baseManager->getBaseName($userObj->getUserBase()); ?></td>
                                <td><?php echo $userObj->getUserHandle(); ?></td>
                                <td><?php echo $roleManager->getRoleName($userObj->getUserRole()); ?></td>
                                <td><a href="/admin/profile/<?php echo $result; ?>">View Profile</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php elseif($searchType == "testHistory"): ?>
                    <table>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Score</th>
                            <th>Questions</th>
                            <th>AFSC</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($paginatedResults as $result): ?>
                            <?php
                            $testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
                            $testManager->loadTest($result);
                            ?>
                            <tr>
                                <td><?php echo $cdcMastery->formatDateTime($testManager->getTestTimeCompleted(),"j M Y H:i"); ?></td>
                                <td><a href="/admin/profile/<?php echo $testManager->getUserUUID(); ?>"><?php echo $userManager->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
                                <td><?php echo $testManager->getTestScore(); ?></td>
                                <td><?php echo $testManager->getTotalQuestions(); ?></td>
                                <td>
                                    <?php
                                    if(count($testManager->getAFSCList()) > 1){
                                        echo "Multiple";
                                    }
                                    else{
                                        $testManagerAFSCList = $testManager->getAFSCList();
                                        echo $afscManager->getAFSCName($testManagerAFSCList[0]);
                                    }
                                    ?>
                                </td>
                                <td><a href="/test/view/<?php echo $result; ?>">View Test</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
