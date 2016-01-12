<?php
$logFilter = new logFilter($db, $user);

$pageNumber = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : 0;
$pageRows = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : 15;
$sortBy = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : "timestamp";
$sortDirection = isset($_SESSION['vars'][3]) ? $_SESSION['vars'][3] : "DESC";

/*
 * Starting Row
 */
if($pageNumber <= 0){
	$rowOffset = 0;
}
else{
	$rowOffset = $pageNumber * $pageRows;
}

/*
 * Get some stats and set some variables!
 */

$logFilter->setFilterUserUUID($_SESSION['userUUID']);

$logFilter->setRowOffset($rowOffset);
$logFilter->setPageRows($pageRows);
$logFilter->setSortBy($sortBy);
$logFilter->setSortDirection($sortDirection);

$totalLogEntries = $logFilter->countEntries();
$logEntries = $logFilter->listEntries();

/*
 * Total Pages
 */
$totalPages = ceil($totalLogEntries / $pageRows) - 1;

if($logEntries): ?>
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
	table#log-table-1 td:nth-of-type(1):before { content: "Timestamp"; }
	table#log-table-1 td:nth-of-type(2):before { content: "Action"; }
	table#log-table-1 td:nth-of-type(3):before { content: "User"; }
	table#log-table-1 td:nth-of-type(4):before { content: "Details"; }
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
		<div class="3u">
			<?php if($totalPages): ?>
			<br>
			<section>
				<header>
					<h2>Navigation</h2>
				</header>
				<div class="sub-menu">
					<ul>
						<?php if($pageNumber > 0): ?>
						<li><a href="/user/log/0/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; ?>" title="First"><i class="icon-inline icon-20 ic-arrow-left-silver"></i>First</a></li>
						<?php endif; ?>
						<?php if($pageNumber > 0): ?>
						<li><a href="/user/log/<?php echo ($pageNumber - 1); ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; ?>" title="Previous"><i class="icon-inline icon-20 ic-arrow-left"></i>Previous</a></li>
						<?php endif; ?>
						<?php if($pageNumber < $totalPages): ?>
						<li><a href="/user/log/<?php echo ($pageNumber + 1); ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; ?>" title="Next"><i class="icon-inline icon-20 ic-arrow-right"></i>Next</a></li>
						<?php endif; ?>
						<?php if($pageNumber < $totalPages): ?>
						<li><a href="/user/log/<?php echo $totalPages; ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; ?>" title="Last"><i class="icon-inline icon-20 ic-arrow-right-silver"></i>Last</a></li>
						<?php endif; ?>
					</ul>
				</div>
			</section>
			<div class="clearfix">&nbsp;</div>
			<?php endif; ?>
			<br>
			<section>
				<header>
					<h2>Sort By</h2>
				</header>
				<ul>
					<li>
						<span class="tableLinksDashed">
							<a <?php if($sortBy == "timestamp") echo "class=\"active\""?> href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/desc">Timestamp</a>:
						</span>
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/asc"><i class="icon-inline icon-20 ic-arrow-up"></i></a>&nbsp;&nbsp;
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/desc"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
					</li>
					<li>
						<span class="tableLinksDashed">
							<a <?php if($sortBy == "action") echo "class=\"active\""?> href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/asc">Action</a>:
						</span>
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/asc"><i class="icon-inline icon-20 ic-arrow-up"></i></a>&nbsp;&nbsp;
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/desc"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
					</li>
					<li>		
						<span class="tableLinksDashed">
							<a <?php if($sortBy == "useruuid") echo "class=\"active\""?> href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/asc">User</a>:
						</span>
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/asc"><i class="icon-inline icon-20 ic-arrow-up"></i></a>&nbsp;&nbsp;
						<a href="/user/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/desc"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
					</li>
				</ul>
			</section>
		</div>
		<div class="9u">
			<section>
				<header>
					<h2>Log Data</h2>
				</header>
				<div class="tableSmallText">
					<table id="log-table-1">
						<tr>
							<th>Timestamp</th>
							<th>Action</th>
							<th>User</th>
							<th>Details</th>
						</tr>
						<?php foreach($logEntries as $logUUID => $logData): ?>
						<tr>
							<td><?php echo $cdcMastery->outputDateTime($logData['timestamp'], $_SESSION['timeZone']); ?></td>
							<td><?php echo $logData['action']; ?></td>
							<?php if(!in_array($logData['userUUID'],$cdcMastery->getStaticUserArray())): ?>
								<td><a href="/user/profile"><?php echo $user->getUserNameByUUID($logData['userUUID']); ?></a></td>
							<?php else: ?>
								<td><?php echo $user->getUserNameByUUID($logData['userUUID']); ?></td>
							<?php endif; ?>
							<td><a href="/user/log-detail/<?php echo $logUUID; ?>"><i class="icon-inline icon-20 ic-clipboard"></i>details</a></td>
						</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</section>
		</div>
	</div>
</div>
<?php else: ?>
You have no log entries in the database.
<?php endif; ?>