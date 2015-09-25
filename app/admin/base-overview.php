<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 9/25/15
 * Time: 1:54 AM
 */
$baseUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$baseUUID){
    $baseUUID = $user->getUserBase();
    if(empty($baseUUID)){
        $sysMsg->addMessage("Your account settings do not specify a base.");
        $cdcMastery->redirect("/errors/500");
    }
}

if(isset($_POST['baseUUID']) && !empty($_POST['baseUUID'])){
    if($bases->loadBase($_POST['baseUUID'])){
        $baseUUID = $_POST['baseUUID'];
    }
    else{
        $sysMsg->addMessage("Invalid base specified.");
    }
}

$statistics = new statistics($db,$log,$emailQueue);
$baseUserObj = new user($db,$log,$emailQueue);
$baseUsersUUIDList = $user->listUserUUIDByBase($baseUUID);
$baseUsers = $user->sortUserUUIDList($baseUsersUUIDList,"userLastName");
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Base Overview for <?php echo $bases->getBaseName($baseUUID); ?></h2>
                </header>
            </section>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php if(!empty($baseUsers)): ?>
        <div class="9u">
            <section>
                <h2>Testing Data</h2>
                <table>
                    <tr>
                        <th>User Name</th>
                        <th>Total Tests</th>
                        <th>Average Score</th>
                        <th>Latest Score</th>
                        <th>Last Login</th>
                    </tr>
                    <?php
                    foreach($baseUsers as $baseUser):
                        $baseUserObj->loadUser($baseUser);
                        $userStatistics->setUserUUID($baseUser);
                        $userAverage = round($userStatistics->getAverageScore(),2);
                        $userLatestScore = $userStatistics->getLatestTestScore();
                        ?>
                        <tr>
                            <td><a href="/admin/profile/<?php echo $baseUserObj->getUUID(); ?>"><?php echo $baseUserObj->getFullName(); ?></a></td>
                            <td><?php echo $userStatistics->getTotalTests(); ?> <span class="text-float-right"><a href="/admin/users/<?php echo $baseUserObj->getUUID(); ?>/tests">[view]</a></span></td>
                            <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                            <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                            <td><?php echo $baseUserObj->getUserLastLogin(); ?></td>
                        </tr>
                    <?php endforeach;?>
                </table>
            </section>
        </div>
        <?php else: ?>
        <div class="9u">
            <section>
                <p>There are no users with test data at this base.</p>
            </section>
        </div>
        <?php endif; ?>
        <div class="3u">
            <section>
                <h2>Actions</h2>
                <ul>
                    <li>
                        <form action="/admin/base-overview" method="POST">
                            <label for="baseUUID">Base</label>
                            <select id="baseUUID"
                                    name="baseUUID"
                                    class="input_full"
                                    size="1">
                                <?php
                                $baseList = $bases->listBases();
                                foreach($baseList as $baseListUUID => $baseName): ?>
                                    <?php if($baseUUID == $baseListUUID): ?>
                                    <option value="<?php echo $baseListUUID; ?>" SELECTED><?php echo $baseName; ?></option>
                                    <?php else: ?>
                                    <option value="<?php echo $baseListUUID; ?>"><?php echo $baseName; ?></option>
                                    <?php endif;
                                endforeach;
                                ?>
                            </select>
                            <div class="clearfix">&nbsp;</div>
                            <input type="submit" value="Change Base">
                        </form>
                    </li>
                </ul>
            </section>
            <div class="clearfix">&nbsp;</div>
            <section>
                <h2>Statistics</h2>
                <table>
                    <tr>
                        <th>Statistic</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Base Users</td>
                        <td><?php echo count($baseUsers); ?></td>
                    </tr>
                    <tr>
                        <td>Total Tests</td>
                        <td><?php echo $statistics->getTotalTestsByBase($baseUUID); ?></td>
                    </tr>
                    <tr>
                        <td>Average Test Score</td>
                        <td><?php echo $statistics->getAverageScoreByBase($baseUUID); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>
<div class="clearfix"><br></div>