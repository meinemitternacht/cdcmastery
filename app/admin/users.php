<?php
if(isset($_SESSION['vars'][0])){
	$userUUID = $_SESSION['vars'][0];
	$objUser = new user($db, $log);
	
	if(!$objUser->loadUser($userUUID)){
		$_SESSION['error'][] = "That user does not exist.";
		$cdcMastery->redirect("/errors/404");
	}
	
	$action = isset($_SESSION['vars'][1]) ? strtolower($_SESSION['vars'][1]) : false;
	$subAction = isset($_SESSION['vars'][2]) ? strtolower($_SESSION['vars'][2]) : false;
	$finalAction = isset($_SESSION['vars'][3]) ? strtolower($_SESSION['vars'][3]) : false;
	
	if($action){		
		switch($action){
			/*
			 * Basic Functions
			 */
			case "archive":
				require_once BASE_PATH . "/includes/modules/admin/user/archive.inc.php";
			break;
			case "ban":
				require_once BASE_PATH . "/includes/modules/admin/user/ban.inc.php";
			break;
			case "consolidate":
				require_once BASE_PATH . "/includes/modules/admin/user/consolidate.inc.php";
			break;
			case "delete":
				require_once BASE_PATH . "/includes/modules/admin/user/delete.inc.php";
			break;
			case "edit":
				require_once BASE_PATH . "/includes/modules/admin/user/edit.inc.php";
			break;
			case "resend-activation":
				require_once BASE_PATH . "/includes/modules/admin/user/resend-activation.inc.php";
			break;
			case "reset-password":
				require_once BASE_PATH . "/includes/modules/admin/user/reset-password.inc.php";
			break;
			/*
			 * Test Functions
			 */
			case "tests":
				if($subAction){
					if($subAction == "archive"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/archive.inc.php";
					}
					elseif($subAction == "delete"){
						require_once BASE_PATH . "/includes/modules/admin/user/tests/delete.inc.php";
					}
					elseif($subAction == "incomplete"){
						if($finalAction){
							if($finalAction == "delete"){
								require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete/delete.inc.php";
							}
						}
						else{
							require_once BASE_PATH . "/includes/modules/admin/user/tests/incomplete.inc.php";
						}
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
					elseif($subAction == "trainingmanager"){
						require_once BASE_PATH . "/includes/modules/admin/user/associations/trainingManager.inc.php";
					}
				}
			break;
			/*
			 * Messaging Functions
			 */
			case "message":
				require_once BASE_PATH . "/includes/modules/admin/user/messaging/send.inc.php";
			break;
			/*
			 * Support Ticket Functions
			 */
			case "tickets":
				if($subAction){
					if($subAction == "delete"){
						require_once BASE_PATH . "/includes/modules/admin/user/tickets/delete.inc.php";
					}
					elseif($subAction == "new"){
						require_once BASE_PATH . "/includes/modules/admin/user/tickets/new.inc.php";
					}
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/tickets/tickets.inc.php";
				}
			break;
			/*
			 * Log Functions
			 */
			case "log":
				if($subAction){
					if($subAction == "clear"){
						require_once BASE_PATH . "/includes/modules/admin/user/log/log.inc.php";
					}
				}
				else{
					require_once BASE_PATH . "/includes/modules/admin/user/log/clear.inc.php";
				}
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
		<section>
			<header>
				<h2><?php echo $objUser->getFullName(); ?></h2>
			</header>
		</section>
		<div class="container">
			<div class="row">
				<div class="4u">
					<section>
						<div class="sub-menu">
							<div class="menu-heading">
								Basic
							</div>
							<ul>
								<li><a href="/admin/profile/<?php echo $userUUID; ?>"><i class="fa fa-user fa-fw"></i>View Profile</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/edit"><i class="fa fa-pencil fa-fw"></i>Edit User</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/delete"><i class="fa fa-trash fa-fw"></i>Delete User</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/archive"><i class="fa fa-folder fa-fw"></i>Archive User</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/ban"><i class="fa fa-ban fa-fw"></i>Ban User</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/resend-activation"><i class="fa fa-retweet fa-fw"></i>Resend Activation Code</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/reset-password"><i class="fa fa-refresh fa-fw"></i>Reset Password</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/consolidate"><i class="fa fa-magic fa-fw"></i>Consolidate User</a></li>
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
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests"><i class="fa fa-archive fa-fw"></i>View Tests</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/incomplete"><i class="fa fa-tasks fa-fw"></i>View Incomplete Tests</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/tests/delete"><i class="fa fa-trash fa-fw"></i>Delete All Tests</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/incomplete/delete"><i class="fa fa-trash fa-fw"></i>Delete All Incomplete Tests</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tests/archive"><i class="fa fa-folder fa-fw"></i>Archive Tests</a></li>
							</ul>
						</div>
						<div class="sub-menu">
							<div class="menu-heading">
								Messaging
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/message"><i class="fa fa-envelope fa-fw"></i>Send Message</a></li>
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
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/afsc"><i class="fa fa-puzzle-piece fa-fw"></i>AFSC Associations</a></li>
								<?php if($roles->verifyUserRole($userUUID) == "trainingManager"): ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/subordinate"><i class="fa fa-sitemap fa-fw"></i>Subordinate Associations</a></li>
								<?php elseif($roles->verifyUserRole($userUUID) == "supervisor"): ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/subordinate"><i class="fa fa-sitemap fa-fw"></i>Subordinate Associations</a></li>
								<?php else: ?>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/trainingManager"><i class="fa fa-sitemap fa-fw"></i>Training Manager Associations</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/associations/supervisor"><i class="fa fa-sitemap fa-fw"></i>Supervisor Associations</a></li>
								<?php endif; ?>
							</ul>
						</div>
						<div class="sub-menu">
							<div class="menu-heading">
								Support Tickets
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tickets"><i class="fa fa-life-saver fa-fw"></i>View Tickets</a></li>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/tickets/new"><i class="fa fa-paper-plane fa-fw"></i>Open Ticket for User</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/tickets/delete"><i class="fa fa-trash fa-fw"></i>Delete All Tickets</a></li>
							</ul>
						</div>
						<div class="sub-menu">
							<div class="menu-heading">
								Log Entries
							</div>
							<ul>
								<li><a href="/admin/users/<?php echo $userUUID; ?>/log"><i class="fa fa-bars fa-fw"></i>View Log Entries</a></li>
								<li class="menu-item-warning"><a href="/admin/users/<?php echo $userUUID; ?>/log/clear"><i class="fa fa-trash fa-fw"></i>Clear Log Entries</a></li>
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