<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/22/2015
 * Time: 10:05 PM
 */

$subPage = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$searchType = isset($_POST['searchType']) ? $_POST['searchType'] : false;
$searchParameterJoinMethod = isset($_POST['searchParameterJoinMethod']) ? $_POST['searchParameterJoinMethod'] : false;

if(isset($_POST['doSearch']) && $_POST['doSearch'] == true) {
    if(!$searchType){
        $sysMsg->addMessage("Search type not specified.  Contact the helpdesk for assistance.");
        $cdcMastery->redirect("/admin/search");
    }

    if(!$searchParameterJoinMethod){
        $sysMsg->addMessage("Search criteria join method not specified. Please select 'Match All' or 'Match Any' Criteria.");
        $cdcMastery->redirect("/admin/search");
    }

    switch($searchType){
        case "user":
            $searchParameterList['userFirstName'] = isset($_POST['userFirstName']) ? $_POST['userFirstName'] : false;
            $searchParameterList['userLastName'] = isset($_POST['userLastName']) ? $_POST['userLastName'] : false;
            $searchParameterList['userHandle'] = isset($_POST['userHandle']) ? $_POST['userHandle'] : false;
            $searchParameterList['userEmail'] = isset($_POST['userEmail']) ? $_POST['userEmail'] : false;
            $searchParameterList['userRank'] = isset($_POST['userRank']) ? $_POST['userRank'] : false;
            $searchParameterList['userRole'] = isset($_POST['userRole']) ? $_POST['userRole'] : false;
            $searchParameterList['userBase'] = isset($_POST['userBase']) ? $_POST['userBase'] : false;
            break;
        case "AFSCassociations":
            $searchParameterList['afscUUID'] = isset($_POST['afscUUID']) ? $_POST['afscUUID'] : false;
            break;
        case "testHistory":
            $searchParameterList['afscList'] = isset($_POST['afscList']) ? $_POST['afscList'] : false;
            $searchParameterList['userUUID'] = isset($_POST['userUUID']) ? $_POST['userUUID'] : false;
            break;
    }

    $searchObj = new search($db, $log);
    $searchObj->setSearchType($_POST['searchType']);
    $searchObj->setSearchParameterJoinMethod($_POST['searchParameterJoinMethod']);

    foreach($searchParameterList as $searchParameterKey => $searchParameter){
        if(!empty($searchParameter)){
            if(is_array($searchParameter)){
                $searchObj->addSearchParameterMultipleValues($searchParameterKey,$searchParameter);
            }
            else{
                $searchObj->addSearchParameterSingleValue(Array($searchParameterKey,$searchParameter));
            }
        }
    }

    $searchResults = $searchObj->executeSearch();

    if(!$searchResults){
        $sysMsg->addMessage("There were no results for that search query.");
        $sysMsg->addMessage($searchObj->error);

        $cdcMastery->redirect("/admin/search");
    }
    else{
        $numSearchResults = count($searchResults);
    }
}
?>
<div class="container">
<?php if($subPage == "results"): ?>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><?php echo $numSearchResults; ?> Search Result<?php echo ($numSearchResults > 1) ? "s" : ""; ?> Found</h2>
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
        <div class="12u">
            <section>
                <a href="/admin/search">&laquo; New Search</a>
                <?php if($searchType == "user"): ?>
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
                        <?php foreach($searchResults as $result): ?>
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
                        <?php $sortedSearchResults = $user->sortUserUUIDList($searchResults,"userLastName"); ?>
                        <?php foreach($sortedSearchResults as $result): ?>
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
                        <?php foreach($searchResults as $result): ?>
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
<?php else: ?>
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>Search</h2>
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
        <div class="4u">
            <section>
                <header>
                    <h3>Users</h3>
                </header>
                <form action="/admin/search/results" method="POST">
                    <input type="hidden" name="doSearch" value="1">
                    <input type="hidden" name="searchType" value="user">
                    <ul>
                        <li>
                            <input type="radio" name="searchParameterJoinMethod" value="AND" CHECKED> Match All Criteria<br>
                            <input type="radio" name="searchParameterJoinMethod" value="OR"> Match Any Criteria
                        </li>
                        <li>
                            <label for="userFirstName">First Name</label>
                            <input type="text" name="userFirstName" id="userFirstName" maxlength="255" class="input_full">
                        </li>
                        <li>
                            <label for="userLastName">Last Name</label>
                            <input type="text" name="userLastName" id="userLastName" maxlength="255" class="input_full">
                        </li>
                        <li>
                            <label for="userHandle">Username</label>
                            <input type="text" name="userHandle" id="userHandle" maxlength="255" class="input_full">
                        </li>
                        <li>
                            <label for="userEmail">E-mail</label>
                            <input type="text" name="userEmail" id="userEmail" maxlength="255" class="input_full">
                        </li>
                        <li>
                            <label for="userRank">Rank</label>
                            <select id="userRank"
                                    name="userRank"
                                    class="input_full"
                                    MULTIPLE>
                                <?php
                                $rankList = $cdcMastery->listRanks();
                                foreach($rankList as $rankGroupLabel => $rankGroup){
                                    echo '<optgroup label="'.$rankGroupLabel.'">';
                                    foreach($rankGroup as $rankOrder){
                                        foreach($rankOrder as $rankKey => $rankVal): ?>
                                            <option value="<?php echo $rankKey; ?>"><?php echo $rankVal; ?></option>
                                            <?php
                                        endforeach;
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="userRole">Permission Group</label>
                            <select id="userRole"
                                    name="userRole"
                                    class="input_full"
                                    MULTIPLE>
                                <?php
                                $roleList = $roles->listRoles();
                                foreach($roleList as $roleUUID => $roleDetails): ?>
                                    <option value="<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="userBase">Base</label>
                            <select id="userBase"
                                    name="userBase"
                                    class="input_full"
                                    MULTIPLE>
                                <?php
                                $baseList = $bases->listBases();
                                foreach($baseList as $baseUUID => $baseName): ?>
                                    <option value="<?php echo $baseUUID; ?>"><?php echo $baseName; ?></option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Find Users">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h3>AFSC Associations</h3>
                </header>
                <form action="/admin/search/results" method="POST">
                    <input type="hidden" name="doSearch" value="1">
                    <input type="hidden" name="searchType" value="AFSCassociations">
                    <input type="hidden" name="searchParameterJoinMethod" value="AND">
                    <ul>
                        <li>
                            <label for="afscUUID">AFSC</label>
                            <select id="afscUUID"
                                    name="afscUUID"
                                    class="input_full"
                                    style="height:10em;"
                                    MULTIPLE>
                                <?php
                                $afscList = $afsc->listAFSC();
                                foreach($afscList as $afscUUID => $afscDetails): ?>
                                    <option value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Search Associations">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h3>Completed Tests</h3>
                </header>
                <form action="/admin/search/results" method="POST">
                    <input type="hidden" name="doSearch" value="1">
                    <input type="hidden" name="searchType" value="testHistory">
                    <ul>
                        <li>
                            <input type="radio" name="searchParameterJoinMethod" value="AND" CHECKED> Match All Criteria<br>
                            <input type="radio" name="searchParameterJoinMethod" value="OR"> Match Any Criteria
                        </li>
                        <li>
                            <label for="afscList">AFSC</label>
                            <select id="afscList"
                                    name="afscList"
                                    class="input_full"
                                    style="height:10em;"
                                    MULTIPLE>
                                <?php
                                $afscList = $afsc->listAFSC();
                                foreach($afscList as $afscUUID => $afscDetails): ?>
                                    <option value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="userUUID">User</label>
                            <select id="userUUID"
                                    name="userUUID"
                                    class="input_full"
                                    style="height:10em;"
                                    MULTIPLE>
                                <?php
                                $userList = $user->listUsers();
                                foreach($userList as $userUUID => $userDetails): ?>
                                    <option value="<?php echo $userUUID; ?>">
                                        <?php echo $userDetails['userLastName'] . ", " . $userDetails['userFirstName'] . " " . $userDetails['userRank']; ?>
                                    </option>
                                    <?php
                                endforeach;
                                ?>
                            </select>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Find Tests">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
    </div>
<?php endif; ?>
</div>
