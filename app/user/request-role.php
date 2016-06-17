<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/16/16
 * Time: 1:04 AM
 */
$roleAuth = new userAuthorizationQueue($db,$log,$emailQueue);

if($roleAuth->checkUserRoleAuthorization($user->getUUID(),$roles->getRoleUUIDByName("Supervisors")) || $roleAuth->checkUserRoleAuthorization($user->getUUID(),$roles->getRoleUUIDByName("TrainingManagers"))){
    $sysMsg->addMessage("You are already awaiting approval for a role request.  Please wait until that has been approved or disapproved before requesting a new role.","caution");
    $cdcMastery->redirect("/");
}

$requestedRole = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($requestedRole){
    switch($requestedRole){
        case "supervisor":
            if($user->getUserRole() == $roles->getRoleUUIDByName("Users")){
                if($roleAuth->queueRoleAuthorization($user->getUUID(),$roles->getRoleUUIDByName("Supervisors"))){
                    $sysMsg->addMessage("Your request has been received and queued.  When your request is approved, you should receive a confirmation e-mail.","success");
                    $cdcMastery->redirect("/");
                }
                else{
                    $sysMsg->addMessage("Sorry, your request could not be processed.  Please contact the help desk.","danger");
                }
            }
            else{
                $sysMsg->addMessage("Sorry, you cannot request the supervisor role.  Contact the help desk if you have any questions.","danger");
            }
            break;
        case "training-manager":
            if($user->getUserRole() == $roles->getRoleUUIDByName("Users") || $user->getUserRole() == $roles->getRoleUUIDByName("Supervisors")){
                $roleAuth = new userAuthorizationQueue($db,$log,$emailQueue);

                if($roleAuth->queueRoleAuthorization($user->getUUID(),$roles->getRoleUUIDByName("Training Managers"))){
                    $sysMsg->addMessage("Your request has been received and queued.  When your request is approved, you should receive a confirmation e-mail.","success");
                    $cdcMastery->redirect("/");
                }
                else{
                    $sysMsg->addMessage("Sorry, your request could not be processed.  Please contact the help desk.","danger");
                }
            }
            else{
                $sysMsg->addMessage("Sorry, you cannot request the supervisor role.  Contact the help desk if you have any questions.","danger");
            }
            break;
    }
}
?>
<div class="container">
    <div class="row">
        <div class="8u">
            <section>
                <header>
                    <h2>Request Role</h2>
                </header>
                <p>
                    Select a role below to request that it be added to your account.  <strong>Note:</strong> after requesting
                    a role below, your request will be placed into an authorization queue for approval.  This may take up to
                    24 hours, and your account will retain its current permissions until approval occurs.
                </p>
                <br>
                <div class="container">
                    <div class="row">
                        <?php if(!$cdcMastery->verifyAdmin() && !$cdcMastery->verifyTrainingManager() && !$cdcMastery->verifySupervisor()): ?>
                        <div class="4u">
                            <section style="border-bottom: 6px solid #369;">
                                <header>
                                    <h2>Supervisor Role</h2>
                                </header>
                                <p>Select this role if you wish to take tests and require an overview of your subordinates.</p>
                                <br>
                                <div class="sub-menu">
                                    <ul>
                                        <li><a href="/user/request-role/supervisor">Request Supervisor Role &raquo;</a></li>
                                    </ul>
                                </div>
                                <div class="clearfix">&nbsp;</div>
                            </section>
                        </div>
                        <?php endif; ?>
                        <?php if(!$cdcMastery->verifyAdmin() && !$cdcMastery->verifyTrainingManager()): ?>
                        <div class="4u">
                            <section style="border-bottom: 6px solid #933;">
                                <header>
                                    <h2>Training Manager</h2>
                                </header>
                                <p>Choose this role to take tests, manage questions and answers, as well as view subordinate progress.</p>
                                <br>
                                <div class="sub-menu">
                                    <ul>
                                        <li><a href="/user/request-role/training-manager">Request Training Manager Role &raquo;</a></li>
                                    </ul>
                                </div>
                                <div class="clearfix">&nbsp;</div>
                            </section>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
