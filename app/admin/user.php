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
	
	if(isset($action)){		
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
			<a href="/admin/profile/<?php echo $userUUID; ?>" class="button">View User Profile</a>
		</section>
		<div class="container">
			<div class="row">
				<div class="4u">
					<section>
						<header>
							<h2>Basic</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/edit">Edit User</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/delete">Delete User</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/archive">Archive User</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/ban">Ban User</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/resend-activation">Resend Activation Code</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/reset-password">Reset Password</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/consolidate">Consolidate User</a></li>
						</ul>
					</section>
				</div>
				<div class="4u">
					<section>
						<header>
							<h2>Testing</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tests">View Tests</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tests/incomplete">View Incomplete Tests</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/tests/delete">Delete All Tests</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tests/incomplete/delete">Delete All Incomplete Tests</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tests/archive">Archive Tests</a></li>
						</ul>
					</section>
				</div>
				<div class="4u">
					<section>
						<header>
							<h2>Associations</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/associations/afsc">AFSC Associations</a></li>
							<?php if($roles->verifyUserRole($userUUID) == "trainingManager"): ?>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/associations/subordinate">Subordinate Associations</a></li>
							<?php elseif($roles->verifyUserRole($userUUID) == "supervisor"): ?>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/associations/subordinate">Subordinate Associations</a></li>
							<?php else: ?>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/associations/trainingManager">Training Manager Associations</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/associations/supervisor">Supervisor Associations</a></li>
							<?php endif; ?>
						</ul>
					</section>
				</div>
			</div>
			<div class="row">
				<div class="4u">
					<section>
						<header>
							<h2>Messaging</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/message">Send Message</a></li>
						</ul>
					</section>
				</div>
				<div class="4u">
					<section>
						<header>
							<h2>Support Tickets</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tickets">View Tickets</a></li>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/tickets/new">Open Ticket for User</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/tickets/delete">Delete All Tickets</a></li>
						</ul>
					</section>
				</div>
				<div class="4u">
					<section>
						<header>
							<h2>Log Entries</h2>
						</header>
						<ul>
							<li><a href="/admin/user/<?php echo $userUUID; ?>/log">View Log Entries</a></li>
							<li class="text-warning"><a href="/admin/user/<?php echo $userUUID; ?>/log/clear">Clear Log Entries</a></li>
						</ul>
					</section>
				</div>
			</div>
		</div>
		<?php
	}
}
else{
	require BASE_PATH . "/includes/modules/admin/user/userAlphaList.inc.php";
}