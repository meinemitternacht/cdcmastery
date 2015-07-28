<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/21/15
 * Time: 1:21 AM
 */

if(isset($_SESSION['vars'][0])){
    $supervisorUUID = $_SESSION['vars'][0];
}
else{
    $supervisorUUID = $_SESSION['userUUID'];
}

if(!$cdcMastery->verifySupervisor() && !$cdcMastery->verifyAdmin()){
    $sysMsg->addMessage("You are not authorized to view the Supervisor Overview.");
    $cdcMastery->redirect("/errors/403");
}

if($roles->getRoleType($user->getUserRoleByUUID($supervisorUUID)) != "supervisor"){
    $sysMsg->addMessage("That user is not a Supervisor.");
    $cdcMastery->redirect("/errors/500");
}

$supUser = new user($db,$log,$emailQueue);
$supOverview = new supervisorOverview($db,$log,$userStatistics,$supUser,$roles);

$supOverview->loadSupervisor($supervisorUUID);

$subordinateUsers = $user->sortUserUUIDList($supOverview->getSubordinateUserList(),"userLastName");

if(empty($subordinateUsers)):
    $sysMsg->addMessage("You do not have any subordinate users.");
    $cdcMastery->redirect("/admin/users");
endif;
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Supervisor Overview</h2>
                </header>
            </section
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php if(!empty($subordinateUsers)): ?>
            <div class="9u">
                <section>
                    <h2>User Data</h2>
                    <table>
                        <tr>
                            <th>User Name</th>
                            <th>Total Tests</th>
                            <th>Average Score</th>
                            <th>Latest Score</th>
                            <th>Last Login</th>
                        </tr>
                        <?php
                        foreach($subordinateUsers as $subordinateUser):
                            $supUser->loadUser($subordinateUser);
                            $userStatistics->setUserUUID($subordinateUser);
                            $userAverage = round($userStatistics->getAverageScore(),2);
                            $userLatestScore = $userStatistics->getLatestTestScore();
                            ?>
                            <tr>
                                <td><a href="/admin/profile/<?php echo $supUser->getUUID(); ?>"><?php echo $supUser->getFullName(); ?></a></td>
                                <td><?php echo $userStatistics->getTotalTests(); ?> <span class="text-float-right"><a href="/admin/users/<?php echo $supUser->getUUID(); ?>/tests">view</a></span></td>
                                <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                                <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                                <td><?php echo $supUser->getUserLastLogin(); ?></td>
                            </tr>
                        <?php endforeach;?>
                    </table>
                </section>
            </div>
        <?php endif; ?>
        <div class="3u">
            <section>
                <h2>Statistics</h2>
                <table>
                    <tr>
                        <th>Statistic</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Subordinate Users</td>
                        <td><?php echo count($supOverview->getSubordinateUserList()); ?></td>
                    </tr>
                    <tr>
                        <td>Total User Tests</td>
                        <td><?php echo $supOverview->getTotalUserTests(); ?></td>
                    </tr>
                    <tr>
                        <td>Average User Test Score</td>
                        <td><?php echo $supOverview->getAverageUserTestScore(); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>
<div class="clearfix"><br></div>