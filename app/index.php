<?php if(!$cdcMastery->loggedIn()): ?>
<!-- Content -->
<div id="content" class="8u skel-cell-important">
	<section>
		<header>
			<h3>Welcome to CDCMastery!</h3>
			<span class="byline">Helping you get on the right track for your career.</span>
		</header>
		<a href="#" class="image full"><img src="images/tracks.png" alt="Get on the right track for your career!" /></a>
		<p>CDCMastery is a study tool developed to help you succeed on assessments of your career knowledge. It's free to use, and we're always here when you need us. <a href="/about/afsc">Click here</a> to see if your AFSC is in our database. If it's not, contact us about adding your Career Development Course information.</p>
	</section>
</div>
<div id="sidebar" class="4u">
	<section>
		<header>
			<h2>Recent Updates</h2>
		</header>
		<ul class="style">
			<li>
				<p class="posted">October 1, 2014</p>
				<p class="text">Site completely redesigned.  Please report any issues that you may come across to <a href="mailto:support@cdcmastery.com">support@cdcmastery.com</a>.</p>
			</li>
			<li>
				<p class="posted">April 21, 2014</p>
				<p class="text">Fixed various bugs and permission issues.</p>
			</li>
			<li>
				<p class="posted">April 20, 2014</p>
				<p class="text">Updated search page to include the ability to search AFSC associations.</p>
			</li>
		</ul>
	</section>
</div>
<?php elseif($cdcMastery->loggedIn()): ?>
<div class="container">
	<div class="row">
		<div class="8u">
			<section>
				<header>
					<h2>Overview</h2>
				</header>
				<?php
				$testManager = new testManager($db, $log, $afsc);
				$userTestArray = $testManager->listUserTests($_SESSION['userUUID'],5);
				
				if($userTestArray): ?>
					<h4>Last Five Tests</h4>
					<div class="tablecloth">
						<table cellspacing="0" cellpadding="0">
							<tr>
								<th>Time Completed</th>
								<th>AFSC</th>
								<th>Score</th>
								<th>Actions</th>
							</tr>
							<?php foreach($userTestArray as $testUUID => $testData): ?>
							<tr>
								<td><?php echo $cdcMastery->outputDateTime($testData['testTimeCompleted'],$_SESSION['timeZone']); ?></td>
								<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
								<td><?php echo $testData['testScore']; ?></td>
								<td>
									<a href="/test/view/<?php echo $testUUID; ?>">View</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>
				<?php else: ?>
					<p>You have not completed any tests.  Why not <a href="/test/take">take a test</a>?</p>
				<?php endif; 
				$userIncompleteTestArray = $testManager->listUserIncompleteTests($_SESSION['userUUID']);
				
				if($userIncompleteTestArray): ?>
					<h4>Incomplete Tests</h4>
					<div class="tablecloth">
						<table cellspacing="0" cellpadding="0">
							<tr>
								<th>Time Started</th>
								<th>Progress</th>
								<th>AFSC</th>
								<th>Combined Test</th>
								<th>Actions</th>
							</tr>
							<?php foreach($userIncompleteTestArray as $testUUID => $testData): ?>
							<tr>
								<td><?php echo $cdcMastery->outputDateTime($testData['timeStarted'],$_SESSION['timeZone']); ?></td>
								<td><?php echo round((($testData['questionsAnswered']/$testData['totalQuestions']) * 100),2); ?> %</td>
								<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
								<td><?php if($testData['combinedTest']){ echo "Yes"; } else { echo "No"; } ?></td>
								<td>
									<a href="/test/delete/<?php echo $testUUID; ?>" title="Delete Test"><i class="fa fa-trash fa-fw"></i></a>
									<a href="/test/resume/<?php echo $testUUID; ?>" title="Resume Test"><i class="fa fa-external-link-square fa-fw"></i></a>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>
					<div class="text-right text-warning"><a href="/test/delete/incomplete/all"><i class="fa fa-trash fa-fw"></i>Delete Incomplete Tests</a></div>
				<?php else: ?>
					<p>You do not have any incomplete tests.</p>
				<?php endif; ?>
			</section>
		</div>
		<div class="4u">
			<section>
				<a href="/test/take" class="button" title="Start Test"><i class="fa fa-caret-square-o-right fa-fw"></i>Start Test</a><br>
				<a href="/user/history" class="button" title="My History"><i class="fa fa-archive fa-fw"></i>My History</a><br>
				<a href="/user/profile" class="button" title="My Profile"><i class="fa fa-user fa-fw"></i>My Profile</a>
			</section>
		</div>
	</div>
</div>
<?php else: ?>
Something is really wrong here...
<?php endif; ?>