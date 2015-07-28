<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/20/15
 * Time: 9:01 PM
 */

if(isset($_SESSION['vars'][0])){
    $trainingManagerUUID = $_SESSION['vars'][0];
}
else{
    $trainingManagerUUID = $_SESSION['userUUID'];
}

if(!$cdcMastery->verifyTrainingManager() && !$cdcMastery->verifyAdmin()){
    $sysMsg->addMessage("You are not authorized to view the Training Manager Overview.");
    $cdcMastery->redirect("/errors/403");
}

if($roles->getRoleType($user->getUserRoleByUUID($trainingManagerUUID)) != "trainingManager"){
    $sysMsg->addMessage("That user is not a Training Manager.");
    $cdcMastery->redirect("/errors/500");
}

$tmUser = new user($db,$log,$emailQueue);
$tmOverview = new trainingManagerOverview($db,$log,$userStatistics,$tmUser,$roles);

$tmOverview->loadTrainingManager($trainingManagerUUID);

$subordinateUsers = $user->sortUserUUIDList($tmOverview->getSubordinateUserList(),"userLastName");
$subordinateSupervisors = $user->sortUserUUIDList($tmOverview->getSubordinateSupervisorList(),"userLastName");

if(empty($subordinateSupervisors) && empty($subordinateUsers)):
    $sysMsg->addMessage("You do not have any subordinate users.");
    $cdcMastery->redirect("/admin/users");
endif;
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Training Manager Overview</h2>
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
                        $tmUser->loadUser($subordinateUser);
                        $userStatistics->setUserUUID($subordinateUser);
                        $userAverage = round($userStatistics->getAverageScore(),2);
                        $userLatestScore = $userStatistics->getLatestTestScore();
                        ?>
                        <tr>
                            <td><a href="/admin/profile/<?php echo $tmUser->getUUID(); ?>"><?php echo $tmUser->getFullName(); ?></a></td>
                            <td><?php echo $userStatistics->getTotalTests(); ?> <span class="text-float-right"><a href="/admin/users/<?php echo $tmUser->getUUID(); ?>/tests">[view]</a></span></td>
                            <td<?php if($cdcMastery->scoreColor($userAverage)){ echo " class=\"".$cdcMastery->scoreColor($userAverage)."\""; }?>><?php echo $userAverage; ?></td>
                            <td<?php if($cdcMastery->scoreColor($userLatestScore)){ echo " class=\"".$cdcMastery->scoreColor($userLatestScore)."\""; }?>><?php echo $userLatestScore; ?></td>
                            <td><?php echo $tmUser->getUserLastLogin(); ?></td>
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
                        <td><?php echo count($tmOverview->getSubordinateUserList()); ?></td>
                    </tr>
                    <tr>
                        <td>Subordinate Supervisors</td>
                        <td><?php echo count($tmOverview->getSubordinateSupervisorList()); ?></td>
                    </tr>
                    <tr>
                        <td>Total User Tests</td>
                        <td><?php echo $tmOverview->getTotalUserTests(); ?></td>
                    </tr>
                    <tr>
                        <td>Total Supervisor Tests</td>
                        <td><?php echo $tmOverview->getTotalSupervisorTests(); ?></td>
                    </tr>
                    <tr>
                        <td>Average User Test Score</td>
                        <td><?php echo $tmOverview->getAverageUserTestScore(); ?></td>
                    </tr>
                    <tr>
                        <td>Average Supervisor Test Score</td>
                        <td><?php echo $tmOverview->getAverageSupervisorTestScore(); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
    <?php if(!empty($subordinateSupervisors)): ?>
    <div class="row">
        <div class="9u">
            <section>
                <h2>Supervisor Data</h2>
                <table>
                    <tr>
                        <th>User Name</th>
                        <th>Total Tests</th>
                        <th>Average Score</th>
                        <th>Latest Score</th>
                        <th>Last Login</th>
                    </tr>
                    <?php
                    foreach($subordinateSupervisors as $subordinateSupervisor):
                        $tmUser->loadUser($subordinateSupervisor);
                        $userStatistics->setUserUUID($subordinateSupervisor);
                        $supervisorAverage = round($userStatistics->getAverageScore(),2);
                        $supervisorLatestTestScore = $userStatistics->getLatestTestScore();
                        ?>
                        <tr>
                            <td><a href="/admin/profile/<?php echo $tmUser->getUUID(); ?>"><?php echo $tmUser->getFullName(); ?></a></td>
                            <td><?php echo $userStatistics->getTotalTests(); ?> <span class="text-float-right"><a href="/admin/users/<?php echo $tmUser->getUUID(); ?>/tests">[view]</a></span></td>
                            <td<?php if($cdcMastery->scoreColor($supervisorAverage)){ echo " class=\"".$cdcMastery->scoreColor($supervisorAverage)."\""; }?>><?php echo $supervisorAverage; ?></td>
                            <td<?php if($cdcMastery->scoreColor($supervisorLatestTestScore)){ echo " class=\"".$cdcMastery->scoreColor($supervisorLatestTestScore)."\""; }?>><?php echo $supervisorLatestTestScore; ?></td>
                            <td><?php echo $tmUser->getUserLastLogin(); ?></td>
                        </tr>
                    <?php endforeach;?>
                </table>
            </section>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="clearfix"><br></div>