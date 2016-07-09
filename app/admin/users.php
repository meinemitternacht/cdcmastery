<?php
if(isset($_SESSION['vars'][0])){
	$userUUID = $_SESSION['vars'][0];
	$objUser = new CDCMastery\UserManager($db, $systemLog, $emailQueue);
	
	if(!$objUser->loadUser($userUUID)){
		$systemMessages->addMessage("That user does not exist.", "warning");
		$cdcMastery->redirect("/errors/404");
	}
	
	$action = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : false;
	$subAction = isset($_SESSION['vars'][2]) ? strtolower($_SESSION['vars'][2]) : false;
	$finalAction = isset($_SESSION['vars'][3]) ? strtolower($_SESSION['vars'][3]) : false;
    $finalChild = isset($_SESSION['vars'][4]) ? strtolower($_SESSION['vars'][4]) : false;
	
	if($action){
		switch($action){
			/*
			 * Basic Functions
			 */
			case "ban":
				if($objUser->getUUID() == $userManager->getUUID()){
					$systemMessages->addMessage("You cannot ban yourself.", "info");
					$cdcMastery->redirect("/");
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/ban.inc.php";
				}
			break;
            case "unban":
				if($objUser->getUUID() == $userManager->getUUID()){
					$systemMessages->addMessage("You cannot unban yourself.", "info");
					$cdcMastery->redirect("/");
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/unban.inc.php";
				}
            break;
			case "delete":
				if($objUser->getUUID() == $userManager->getUUID()){
					$systemMessages->addMessage("You cannot delete your own account.", "info");
					$cdcMastery->redirect("/");
				}
				else {
					require_once BASE_PATH . "/includes/modules/admin/user/delete.inc.php";
				}
			break;
			case "edit":
				require_once BASE_PATH . "/includes/modules/admin/user/edit.inc.php";
			break;
			case "resend-activation":
				if($objUser->getUUID() == $userManager->getUUID()){
					$systemMessages->addMessage("You cannot resend an activation code to your own account.", "info");
					$cdcMastery->redirect("/");
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/resend-activation.inc.php";
				}
			break;
			case "reset-password":
				require_once BASE_PATH . "/includes/modules/admin/user/reset-password.inc.php";
			break;
			/*
			 * Test Functions
			 */
			case "tests":
				if($subAction){
					if($subAction == "top-missed"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/top-missed.inc.php";
					}
					elseif($subAction == "delete"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/delete.inc.php";
					}
                    elseif($subAction == "delete-all"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/delete-all.inc.php";
					}
					elseif($subAction == "incomplete"){
                        if($finalAction == "delete-all"){
                            require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete/delete-all.inc.php";
                        }
						elseif($finalAction == "delete") {
                            require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete/delete.inc.php";
                        }
                        elseif($finalAction == "view"){
                            require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete/view.inc.php";
                        }
                        else{
							require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete.inc.php";
						}
					}
					elseif($subAction == "split"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/history-split.inc.php";
					}
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/tests/tests.inc.php";
				}
			break;
			/*
			 * Association Functions
			 */
			case "associations":
				if($subAction){
					if($subAction == "afsc"){
						require_once BASE_PATH . "/includes/modules/admin/user/associations/afsc.inc.php";
					}
					elseif($subAction == "subordinate"){
						require_once BASE_PATH . "/includes/modules/admin/user/associations/subordinate.inc.php";
					}
					elseif($subAction == "supervisor"){
						require_once BASE_PATH . "/includes/modules/admin/user/associations/supervisor.inc.php";
					}
					elseif($subAction == "training-manager"){
						require_once BASE_PATH . "/includes/modules/admin/user/associations/training-manager.inc.php";
					}
				}
			break;
			/*
			 * Messaging Functions
			 */
			case "message":
				require_once BASE_PATH . "/includes/modules/admin/user/message/send.inc.php";
			break;
			/*
			 * Log Functions
			 */
			case "log":
				if($subAction && $subAction == "clear"){
					if($objUser->getUUID() == $userManager->getUUID()){
						$systemMessages->addMessage("For security reasons, you cannot clear log entries associated with your own account.", "warning");
						$cdcMastery->redirect("/");
					}
					else {
						require_once BASE_PATH . "/includes/modules/admin/user/log/clear.inc.php";
					}
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/log/log.inc.php";
				}
			break;
			case "log-detail":
				require_once BASE_PATH . "/includes/modules/admin/user/log/log-detail.inc.php";
			break;
			default:
				echo "Sigh.";
			break;
		}
	}
	else{
		/*
		 * Show menu of functions for user editing
		 */
		?>
		<div class="container">
			<div class="row">
				<div class="12u">
					<section>
						<header>
							<h2><?php echo $objUser->getFullName(); ?></h2>
						</header>
					</section>
				</div>
				<div class="4u">
					<section>
						<div class="sub-menu">
							<div class="menu-heading">
								Basic
							</div>
							<ul>
								<li><a href="/admin/profile/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-user-single"></i>View Profile</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/edit"><i class="icon-inline icon-20 ic-pencil"></i>Edit User</a></li>
								<?php if($cdcMastery->verifyAdmin()): ?>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/delete"><i class="icon-inline icon-20 ic-delete"></i>Delete User</a></li>
								<?php endif; ?>
                                <?php if(!$objUser->getUserDisabled()): ?>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/ban"><i class="icon-inline icon-20 ic-hammer"></i>Ban User</a></li>
                                <?php else: ?>
                                <li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/unban"><i class="icon-inline icon-20 ic-hammer"></i>Unban User</a></li>
                                <?php endif; ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/resend-activation"><i class="icon-inline icon-20 ic-resend"></i>Resend Activation Code</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/reset-password"><i class="icon-inline icon-20 ic-safe"></i>Reset Password</a></li>
							</ul>
						</div>
					</section>
				</div>
				<div class="4u">
					<section>
						<div class="sub-menu">
							<div class="menu-heading">
								Testing
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/top-missed"><i class="icon-inline icon-20 ic-clipboard"></i>View Top Missed Questions</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests"><i class="icon-inline icon-20 ic-folder"></i>View Tests</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/incomplete"><i class="icon-inline icon-20 ic-folder"></i>View Incomplete Tests</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/tests/delete"><i class="icon-inline icon-20 ic-delete"></i>Delete Tests</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/tests/delete-all"><i class="icon-inline icon-20 ic-delete"></i>Delete All Tests</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/incomplete/delete-all"><i class="icon-inline icon-20 ic-delete"></i>Delete All Incomplete Tests</a></li>
							</ul>
						</div>
						<div class="sub-menu">
							<div class="menu-heading">
								Messaging
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/message"><i class="icon-inline icon-20 ic-email"></i>Send Message</a></li>
							</ul>
						</div>
					</section>
				</div>
				<div class="4u">
					<section>
						<div class="sub-menu">
							<div class="menu-heading">
								Associations
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/afsc"><i class="icon-inline icon-20 ic-puzzle"></i>AFSCs</a></li>
								<?php if($roleManager->verifyUserRole($userUUID) == "trainingManager"): ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/subordinate"><i class="icon-inline icon-20 ic-relationship"></i>Subordinates</a></li>
                                <li><a href="/training/overview/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-clipboard"></i>Training Manager Overview</a></li>
								<?php elseif($roleManager->verifyUserRole($userUUID) == "supervisor"): ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/subordinate"><i class="icon-inline icon-20 ic-relationship"></i>Subordinates</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/training-manager"><i class="icon-inline icon-20 ic-relationship"></i>Training Managers</a></li>
                                <li><a href="/supervisor/overview/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-clipboard"></i>Supervisor Overview</a></li>
								<?php else: ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/supervisor"><i class="icon-inline icon-20 ic-relationship"></i>Supervisors</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/training-manager"><i class="icon-inline icon-20 ic-relationship"></i>Training Managers</a></li>
								<?php endif; ?>
							</ul>
						</div>
						<div class="sub-menu">
							<div class="menu-heading">
								Log Entries
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/log"><i class="icon-inline icon-20 ic-log"></i>View Log Entries</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/log/clear"><i class="icon-inline icon-20 ic-delete"></i>Clear Log Entries</a></li>
							</ul>
						</div>
					</section>
				</div>
			</div>
		</div>
		<?php
	}
}
else{
	$linkBaseURL = "admin/users";
	require BASE_PATH . "/includes/modules/admin/user/userAlphaList.inc.php";
}