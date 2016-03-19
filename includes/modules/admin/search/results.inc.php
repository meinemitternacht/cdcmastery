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
    $sysMsg->addMessage("There are no search results stored in the session.  Try your search again.");
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
                            $userObj = new user($db,$log,$emailQueue);
                            $userObj->loadUser($result);
                            ?>
                            <tr>
                                <td><?php echo $userObj->getUserLastName(); ?></td>
                                <td><?php echo $userObj->getUserFirstName(); ?></td>
                                <td><?php echo $userObj->getUserRank(); ?></td>
                                <td><?php echo $bases->getBaseName($userObj->getUserBase()); ?></td>
                                <td><?php echo $cdcMastery->outputDateTime($userObj->getUserLastLogin(),$_SESSION['timeZone'],"j M Y H:i"); ?></td>
                                <td><?php echo $roles->getRoleName($userObj->getUserRole()); ?></td>
                                <td><a href="/admin/profile/<?php echo $result; ?>">Profile &raquo;</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php elseif($searchType == "log"): ?>
                    <?php $bgColor = "#ffffff;"; ?>
                    <?php foreach($paginatedResults as $searchResult): ?>
                        <?php $log->loadEntry($searchResult); ?>
                        <?php $logDetails = $log->fetchDetails($searchResult); ?>
                        <table class="logSearchTable">
                            <tr style="background-color:<?php echo $bgColor; ?>">
                                <td><strong>Log Action</strong></td>
                                <td><strong>Date</strong></td>
                                <td><strong>Action Performed By</strong></td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr style="background-color:<?php echo $bgColor; ?>">
                                <td><span class="<?php echo $log->getRowStyle($log->getAction()); ?>"><?php echo $log->getAction(); ?></span></td>
                                <td><?php echo $cdcMastery->outputDateTime($log->getTimestamp(),$_SESSION['timeZone']); ?></td>
                                <?php if(!in_array($log->getUserUUID(),$cdcMastery->getStaticUserArray())): ?>
                                    <td><a href="/admin/users/<?php echo $log->getUserUUID(); ?>" title="Manage User"><?php echo $user->getUserNameByUUID($log->getUserUUID()); ?></a></td>
                                <?php else: ?>
                                    <td><?php echo $user->getUserNameByUUID($log->getUserUUID()); ?></td>
                                <?php endif; ?>
                                <td><a href="/admin/log-detail/<?php echo $log->getUUID(); ?>">View &raquo;</a></td>
                            </tr>
                        </table>
                        <?php if($logDetails): ?>
                            <?php $testManager = new testManager($db,$log,$afsc); ?>
                            <table class="logSearchTable" style="width:auto;">
                                <tr style="background-color:<?php echo $bgColor; ?>">
                                    <td style="min-width: 8em;"><strong>Key</strong></td>
                                    <td><strong>Data</strong></td>
                                </tr>
                                <?php foreach($logDetails as $detailKey => $detailData):
                                    $dataTypeSearch = strtolower($detailData['dataType']);

                                    if(	strpos($dataTypeSearch,"user") !== false ||
                                        strpos($dataTypeSearch,"supervisor") !== false ||
                                        strpos($dataTypeSearch,"training manager") !== false) {
                                        if (strpos($dataTypeSearch, "uuid") !== false) {
                                            $userName = $user->getUserNameByUUID($detailData['data']);
                                        }
                                    }
                                    elseif($dataTypeSearch == "afsc array"){
                                        $afscArray = unserialize($detailData['data']);

                                        foreach($afscArray as $dataAFSCUUID){
                                            $afscList[] = '<a href="/admin/cdc-data/'.$dataAFSCUUID.'">'.$afsc->getAFSCName($dataAFSCUUID).'</a>';
                                        }

                                        if(count($afscList) > 0){
                                            $afscList = implode(",",$afscList);
                                        }
                                    }
                                    elseif($dataTypeSearch == "afsc uuid") {
                                        $afscUUID = true;
                                    }
                                    elseif($dataTypeSearch == "test uuid") {
                                        if ($testManager->loadTest($detailData['data'])) {
                                            $testUUID = true;
                                        }
                                    }
                                    ?>
                                    <tr style="background-color:<?php echo $bgColor; ?>">
                                        <td><?php echo $detailData['dataType']; ?></td>
                                        <?php if(isset($userName) && !empty($userName)): ?>
                                            <td>
                                                <a href="/admin/users/<?php echo $detailData['data']; ?>"><?php echo $userName; ?></a>
                                            </td>
                                        <?php elseif(isset($afscUUID) && $afscUUID == true): ?>
                                            <td>
                                                <?php echo $afsc->getAFSCName($detailData['data']); ?>
                                            </td>
                                        <?php elseif(isset($afscList) && !empty($afscList)): ?>
                                            <td>
                                                <?php echo $afscList; ?>
                                            </td>
                                        <?php elseif(isset($testUUID) && $testUUID == true): ?>
                                            <td>
                                                <a href="/test/view/<?php echo $detailData['data']; ?>"><?php echo $detailData['data']; ?></a>
                                            </td>
                                        <?php else: ?>
                                            <td><?php echo $detailData['data']; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php
                                    $afscUUID = false;
                                    $afscList = false;
                                    $testUUID = false;
                                    $userName = false;
                                    $dataTypeSearch = "";
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
                            $userObj = new user($db,$log,$emailQueue);
                            $userObj->loadUser($result);
                            ?>
                            <tr>
                                <td><?php echo $userObj->getUserLastName(); ?></td>
                                <td><?php echo $userObj->getUserFirstName(); ?></td>
                                <td><?php echo $userObj->getUserRank(); ?></td>
                                <td><?php echo $bases->getBaseName($userObj->getUserBase()); ?></td>
                                <td><?php echo $userObj->getUserHandle(); ?></td>
                                <td><?php echo $roles->getRoleName($userObj->getUserRole()); ?></td>
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
                            $testManager = new testManager($db,$log,$afsc);
                            $testManager->loadTest($result);
                            ?>
                            <tr>
                                <td><?php echo $cdcMastery->formatDateTime($testManager->getTestTimeCompleted(),"j M Y H:i"); ?></td>
                                <td><a href="/admin/profile/<?php echo $testManager->getUserUUID(); ?>"><?php echo $user->getUserNameByUUID($testManager->getUserUUID()); ?></a></td>
                                <td><?php echo $testManager->getTestScore(); ?></td>
                                <td><?php echo $testManager->getTotalQuestions(); ?></td>
                                <td>
                                    <?php
                                    if(count($testManager->getAFSCList()) > 1){
                                        echo "Multiple";
                                    }
                                    else{
                                        $testManagerAFSCList = $testManager->getAFSCList();
                                        echo $afsc->getAFSCName($testManagerAFSCList[0]);
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
