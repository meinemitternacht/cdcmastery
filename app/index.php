<?php if(!$cdcMastery->loggedIn()): ?>
<div class="container">
    <div id="mobile-login">
        <div class="row">
            <div class="12u">
                <section>
                    <header>
                        <h3>Log In</h3>
                    </header>
                    <form id="loginForm" action="/auth/login" method="POST">
                        <label for="username">Username or E-mail</label>
                        <br>
                        <input type="text" id="username" name="username" class="input_full">
                        <br>
                        <label for="password">Password</label>
                        <br>
                        <input type="password" id="password" name="password" class="input_full">
                        <div class="clearfix">&nbsp;</div>
                        <input type="submit" value="Log in">
                        <br>
                        <br>
                        <a href="/auth/reset">Forgot Password</a>
                    </form>
                </section>
            </div>
        </div>
    </div>
	<div class="row">
		<!-- Content -->
		<div id="content" class="8u skel-cell-important">
			<section>
				<header>
					<h3>Welcome to CDCMastery!</h3>
					<span class="byline">Helping you get on the right track for your career.</span>
				</header>
				<a href="#" class="image full"><img src="images/tracks.png" alt="Get on the right track for your career!" /></a>
				<p>CDCMastery is a study tool developed to help you succeed on assessments of your career knowledge. It's free to use, and we're always here when you need us. <a href="/about">Click here</a> to see if your AFSC is in our database. If it's not, contact us about adding your Career Development Course information.</p>
			</section>
		</div>
		<div id="sidebar" class="4u">
            <div id="desktop-login">
                <section>
                    <header>
                        <h3>Log In</h3>
                    </header>
                    <form id="loginForm" action="/auth/login" method="POST">
                        <label for="username">Username or E-mail</label>
                        <br>
                        <input type="text" id="username" name="username" class="input_full">
                        <br>
                        <label for="password">Password</label>
                        <br>
                        <input type="password" id="password" name="password" class="input_full">
                        <div class="clearfix">&nbsp;</div>
                        <input type="submit" value="Log in">
                        <br>
                        <br>
                        <a href="/auth/reset">Forgot Password</a>
                    </form>
                </section>
            </div>
            <div class="clearfix">&nbsp;</div>
			<section>
				<header>
					<h2>Recent Updates</h2>
				</header>
				<ul class="style">
					<li>
						<p class="posted">September 27, 2015</p>
						<p class="text">Site completely redesigned.  Please report any issues that you may come across to <a href="mailto:support@cdcmastery.com">support@cdcmastery.com</a>.</p>
					</li>
					<li>
						<p class="posted">September 24, 2015</p>
						<p class="text">Add ability for administrators to manually authorize roles, approve FOUO AFSC associations, and activate users.</p>
					</li>
					<li>
						<p class="posted">September 22, 2015</p>
						<p class="text">Fix click issue for users with iOS.</p>
					</li>
				</ul>
			</section>
		</div>
	</div>
</div>
<?php elseif($cdcMastery->loggedIn()): ?>
<!--[if !IE]><!-->
<style type="text/css">
@media
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {
	table, thead, tbody, th, td, tr {
		display: block;
	}

	thead tr {
		position: absolute;
		top: -9999px;
		left: -9999px;
	}

	tr { border: 1px solid #ccc; }

	td {
		border: none;
		border-bottom: 1px solid #eee;
		position: relative;
		padding-left: 30%;
	}

	td:before {
		position: absolute;
		top: 6px;
		left: 6px;
		width: 25%;
		padding-right: 10px;
		white-space: nowrap;
	}

	table#index-table-1 td:nth-of-type(1):before { content: "Completed"; }
	table#index-table-1 td:nth-of-type(2):before { content: "AFSC"; }
	table#index-table-1 td:nth-of-type(3):before { content: "Score"; }
	table#index-table-1 td:nth-of-type(4):before { content: "Actions"; }
	
	table#index-table-2 td:nth-of-type(1):before { content: "Started"; }
	table#index-table-2 td:nth-of-type(2):before { content: "Progress"; }
	table#index-table-2 td:nth-of-type(3):before { content: "AFSC"; }
	table#index-table-2 td:nth-of-type(4):before { content: "Actions"; }
}

@media only screen
and (min-device-width : 320px)
and (max-device-width : 480px) {
	body {
		padding: 0;
		margin: 0;
		width: 320px; }
	}

@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
	body {
		width: 495px;
	}
}

</style>
<!--<![endif]-->
<?php
if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()) {
	$pendingAssociations = $assoc->listPendingAFSCAssociations();
	if (is_array($pendingAssociations)) {
		$pendingAssociationsCount = count($pendingAssociations);
	} else {
		$pendingAssociationsCount = 0;
	}

	$userActivation = new userActivation($db, $log, $emailQueue);
	$unactivatedUsers = $userActivation->listUnactivatedUsers();
	if (is_array($unactivatedUsers)) {
		$unactivatedUsersCount = count($unactivatedUsers);
	} else {
		$unactivatedUsersCount = 0;
	}

	$userAuthorization = new userAuthorizationQueue($db, $log, $emailQueue);
	$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();
	if (is_array($authorizationQueue)) {
		$authorizationQueueCount = count($authorizationQueue);
	} else {
		$authorizationQueueCount = 0;
	}
}
?>
<div class="container">
	<div class="row">
        <div class="4u">
			<?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
				<?php if(!empty($pendingAssociationsCount) || !empty($unactivatedUsersCount) || !empty($authorizationQueueCount)): ?>
				<section>
					<header>
						<h2>Administrative Tasks</h2>
					</header>
					<div class="informationMessages">
						<ul>
						<?php if(!empty($pendingAssociationsCount)): ?>
							<li><a href="/admin/afsc-pending">There <?php echo ($pendingAssociationsCount > 1) ? "are" : "is"; ?> <?php echo $pendingAssociationsCount; ?> FOUO AFSC association<?php echo ($pendingAssociationsCount > 1) ? "s" : ""; ?> pending.</a></li>
						<?php endif; ?>
						<?php if(!empty($unactivatedUsersCount)): ?>
							<li><a href="/admin/activate-users">There <?php echo ($unactivatedUsersCount > 1) ? "are" : "is"; ?> <?php echo $unactivatedUsersCount; ?> user activation<?php echo ($unactivatedUsersCount > 1) ? "s" : ""; ?> pending.</a></li>
						<?php endif; ?>
						<?php if(!empty($authorizationQueueCount)): ?>
							<li><a href="/admin/authorize-users">There <?php echo ($authorizationQueueCount > 1) ? "are" : "is"; ?> <?php echo $authorizationQueueCount; ?> user role authorization<?php echo ($authorizationQueueCount > 1) ? "s" : ""; ?> pending.</a></li>
						<?php endif; ?>
						</ul>
					</div>
				</section>
				<div class="clearfix">&nbsp;</div>
				<?php endif; ?>
			<?php endif; ?>
            <section>
                <header>
                    <h2>Tasks</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/test/take" title="Start Test"><i class="icon-inline icon-20 ic-arrow-right"></i>Start Test</a></li>
                        <li><a href="/user/history" title="My History"><i class="icon-inline icon-20 ic-book"></i>My History</a></li>
                        <li><a href="/user/profile" title="My Profile"><i class="icon-inline icon-20 ic-user-single"></i>My Profile</a></li>
						<li><a href="/about/statistics" title="View Site Statistics"><i class="icon-inline icon-20 ic-clipboard"></i>View Site Statistics</a></li>
                    </ul>
                </div>
            </section>
            <div class="clearfix">&nbsp;</div>
			<section>
				<header>
                    <h2>Statistics</h2>
                </header>
                <table>
                    <tr>
                        <td style="width: 50%"><strong>Completed Tests</strong></td>
                        <td><?php echo !empty($userStatistics->getCompletedTests()) ? number_format($userStatistics->getCompletedTests()) : "None"; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Incomplete Tests</strong></td>
                        <td><?php echo !empty($userStatistics->getIncompleteTests()) ? number_format($userStatistics->getIncompleteTests()) : "None"; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Questions Answered</strong></td>
                        <td><?php echo !empty($userStatistics->getQuestionsAnswered()) ? number_format($userStatistics->getQuestionsAnswered()) : "None"; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Log Entries</strong></td>
                        <td><?php echo !empty($userStatistics->getLogEntries()) ? number_format($userStatistics->getLogEntries()) : "None"; ?></td>
                    </tr>
                </table>
			</section>
        </div>
		<div class="8u">
            <section>
                <header>
                    <h2>Welcome, <?php echo $user->getFullName(); ?></h2>
                </header>
                <p>
                    We want your feedback!  If you have suggestions on how we can improve this service, or if you want to tell us your success story, drop us a line
                    by clicking "Support" at the top and creating a ticket under the category "Feedback".<br>
                    <br>
                    Also, there are a few features we are actively
                    working on:
                </p>
                <blockquote>
                    <ul style="list-style: circle">
                        <li>Flash Cards</li>
                        <li>Custom Tests (where you create the questions you want to see)</li>
                        <li>Printed tests</li>
                    </ul>
                </blockquote>
            </section>
            <div class="clearfix">&nbsp;</div>
			<section>
				<header>
					<h2>Overview</h2>
				</header>
				<?php
				$testManager = new testManager($db, $log, $afsc);
				$userTestArray = $testManager->listUserTests($_SESSION['userUUID'],5);
				
				if($userTestArray): ?>
					<h4>Last Five Tests</h4>
					<table id="index-table-1">
						<tr>
							<th>Time Completed</th>
							<th>AFSC</th>
							<th>Score</th>
							<th>Actions</th>
						</tr>
						<?php foreach($userTestArray as $testUUID => $testData): ?>
							<?php $tooltipList = $testData['afscList']; ?>
						<tr>
							<td><?php echo $cdcMastery->outputDateTime($testData['testTimeCompleted'],$_SESSION['timeZone']); ?></td>
							<td title="<?php array_walk_recursive($testData['afscList'],array($afsc,'getAFSCNameCallback')); echo implode(", ",$testData['afscList']); ?>"><?php if(count($testData['afscList']) > 1){ echo "Multiple (hover to view)"; }else{ echo $testData['afscList'][0]; } ?></td>
							<td><?php echo $testData['testScore']; ?>%</td>
							<td>
								<a href="/test/view/<?php echo $testUUID; ?>">View</a>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
				<?php else: ?>
					<p>You have not completed any tests.  Why not <a href="/test/take">take a test</a>?</p>
				<?php endif; 
				$userIncompleteTestArray = $testManager->listUserIncompleteTests($_SESSION['userUUID']);
				
				if($userIncompleteTestArray): ?>
					<h4>Incomplete Tests</h4>
					<table id="index-table-2">
						<tr>
							<th>Time Started</th>
							<th>Progress</th>
							<th>AFSC</th>
							<th>Actions</th>
						</tr>
						<?php foreach($userIncompleteTestArray as $testUUID => $testData): ?>
						<tr>
							<td><?php echo $cdcMastery->outputDateTime($testData['timeStarted'],$_SESSION['timeZone']); ?></td>
							<td><?php echo round((($testData['questionsAnswered']/$testData['totalQuestions']) * 100),2); ?> %</td>
							<td title="<?php array_walk_recursive($testData['afscList'],array($afsc,'getAFSCNameCallback')); echo implode(", ",$testData['afscList']); ?>"><?php if(count($testData['afscList']) > 1){ echo "Multiple (hover to view)"; }else{ echo $testData['afscList'][0]; } ?></td>
							<td>
								<a href="/test/delete/incomplete/<?php echo $testUUID; ?>" title="Delete Test"><i class="icon-inline icon-20 ic-delete"></i></a>
								<a href="/test/resume/<?php echo $testUUID; ?>" title="Resume Test"><i class="icon-inline icon-20 ic-arrow-right"></i></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
					<div class="text-right text-warning"><a href="/test/delete/incomplete/all">Delete Incomplete Tests</a></div>
				<?php else: ?>
					<p>You do not have any incomplete tests.</p>
				<?php endif; ?>
			</section>
		</div>
	</div>
</div>
<?php else: ?>
Something is really wrong here...
<?php endif; ?>