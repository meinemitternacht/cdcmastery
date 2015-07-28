<?php if(!$cdcMastery->loggedIn()): ?>
<div class="container">
	<div class="row">
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
	</div>
</div>
<?php elseif($cdcMastery->loggedIn()): ?>
<!--[if !IE]><!-->
<style type="text/css">
/*
Max width before this PARTICULAR table gets nasty
This query will take effect for any screen smaller than 760px
and also iPads specifically.
*/
@media
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {

	/* Force table to not be like tables anymore */
	table, thead, tbody, th, td, tr {
		display: block;
	}

	/* Hide table headers (but not display: none;, for accessibility) */
	thead tr {
		position: absolute;
		top: -9999px;
		left: -9999px;
	}

	tr { border: 1px solid #ccc; }

	td {
		/* Behave  like a "row" */
		border: none;
		border-bottom: 1px solid #eee;
		position: relative;
		padding-left: 30%;
	}

	td:before {
		/* Now like a table header */
		position: absolute;
		/* Top/left values mimic padding */
		top: 6px;
		left: 6px;
		width: 25%;
		padding-right: 10px;
		white-space: nowrap;
	}

	/*
	Label the data
	*/
	table#index-table-1 td:nth-of-type(1):before { content: "Completed"; }
	table#index-table-1 td:nth-of-type(2):before { content: "AFSC"; }
	table#index-table-1 td:nth-of-type(3):before { content: "Score"; }
	table#index-table-1 td:nth-of-type(4):before { content: "Actions"; }
	
	table#index-table-2 td:nth-of-type(1):before { content: "Started"; }
	table#index-table-2 td:nth-of-type(2):before { content: "Progress"; }
	table#index-table-2 td:nth-of-type(3):before { content: "AFSC"; }
	table#index-table-2 td:nth-of-type(4):before { content: "Actions"; }
}

/* Smartphones (portrait and landscape) ----------- */
@media only screen
and (min-device-width : 320px)
and (max-device-width : 480px) {
	body {
		padding: 0;
		margin: 0;
		width: 320px; }
	}

/* iPads (portrait and landscape) ----------- */
@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
	body {
		width: 495px;
	}
}

</style>
<!--<![endif]-->
<div class="container">
	<div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Tasks</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/test/take" title="Start Test"><i class="fa fa-caret-square-o-right fa-fw"></i>Start Test</a></li>
                        <li><a href="/user/history" title="My History"><i class="fa fa-archive fa-fw"></i>My History</a></li>
                        <li><a href="/user/profile" title="My Profile"><i class="fa fa-user fa-fw"></i>My Profile</a></li>
                    </ul>
                </div>
            </section>
        </div>
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
					<table id="index-table-1">
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
							<td><?php if(count($testData['afscList']) > 1){ echo "Multiple"; }else{ echo $afsc->getAFSCName($testData['afscList'][0]); } ?></td>
							<td>
								<a href="/test/delete/incomplete/<?php echo $testUUID; ?>" title="Delete Test"><i class="fa fa-trash fa-fw"></i></a>
								<a href="/test/resume/<?php echo $testUUID; ?>" title="Resume Test"><i class="fa fa-external-link-square fa-fw"></i></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>
					<div class="text-right text-warning"><a href="/test/delete/incomplete/all"><i class="fa fa-trash fa-fw"></i>Delete Incomplete Tests</a></div>
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