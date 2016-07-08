<?php
$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
$testList = $testManager->listUserTests($_SESSION['userUUID']);

if(!empty($testList)): ?>
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
		padding-left: 25%;
	}

	td:before {
		/* Now like a table header */
		position: absolute;
		/* Top/left values mimic padding */
		top: 6px;
		left: 6px;
		width: 20%;
		padding-right: 10px;
		white-space: nowrap;
	}

	/*
	Label the data
	*/	
	table#history-table-1 td:nth-of-type(1):before { content: "Completed"; }
	table#history-table-1 td:nth-of-type(2):before { content: "AFSC"; }
	table#history-table-1 td:nth-of-type(3):before { content: "Questions"; }
	table#history-table-1 td:nth-of-type(4):before { content: "Score"; }
	table#history-table-1 td:nth-of-type(5):before { content: "Actions"; }
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
		<div class="12u">
			<section>
				<header>
					<h2>Test History For <?php echo $userManager->getUserNameByUUID($_SESSION['userUUID']); ?></h2>
				</header>
				<br>
				<a href="/user/history-split" title="View Split History">View Split History</a>
				<br>
                <div id="chart-container" style="height:400px">
                    &nbsp;
                </div>
				<table id="history-table-1">
					<tr>
						<th>Date Completed</th>
						<th>AFSC</th>
						<th>Questions</th>
						<th>Score</th>
						<th>Actions</th>
					</tr>
					<?php
					$i=0;
					foreach($testList as $testUUID => $testDetails):
						if(is_array($testDetails['afscList'])){
							$rawAFSCList = $testDetails['afscList'];

							foreach($rawAFSCList as $key => $val){
								$rawAFSCList[$key] = $afscManager->getAFSCName($val);
							}

							if(count($rawAFSCList) > 1){
								$testAFSCList = implode(",",$rawAFSCList);
							}
							else{
								$testAFSCList = $rawAFSCList[0];
							}
						}
						else{
							$testAFSCList = $testDetails['afscList'];
						}

						if(strlen($testAFSCList) > 11){
							$testAFSCList = substr($testAFSCList,0,12) . "...";
						}

						$chartData[$i]['timeCompleted'] = $testDetails['testTimeCompleted'];
						$chartData[$i]['testScore'] = $testDetails['testScore'];
						$i++;
						?>
					<tr>
						<td><?php echo $cdcMastery->outputDateTime($testDetails['testTimeCompleted'], $_SESSION['timeZone']); ?></td>
						<td><?php echo $testAFSCList; ?></td>
						<td><?php echo $testDetails['totalQuestions']; ?></td>
						<td><strong><?php echo $testDetails['testScore']; ?>%</strong></td>
						<td><a href="/test/view/<?php echo $testUUID; ?>">View</a></td>
					</tr>
					<?php endforeach; ?>
				</table>
				<?php

				$chartOutputData = "";
				$firstRow = true;
				$i=0;
				$chartData = array_reverse($chartData);
				foreach($chartData as $rowKey => $rowData){
					if ($firstRow == false) {
						$chartOutputData .= ",";
					}

					$chartOutputData .= "{ x: " . $i . ", toolTipContent: \"" . $rowData['timeCompleted'] . "<br>Score: <strong>{y}</strong>\", y: " . $rowData['testScore'] . " }";
					$firstRow = false;
					$i++;
				}
				?>
				<script type="text/javascript">
					window.onload = function () {
						var chart = new CanvasJS.Chart("chart-container", {

							title:{
								text: "Test History"
							},
							axisX:{
								valueFormatString: " ",
								tickLength: 0
							},
							data: [
								{
									/*** Change type "column" to "bar", "area", "line" or "pie"***/
										<?php if($i > 75){ ?>type: "line",<?php } else{ ?>type: "spline",<?php } ?>
									dataPoints: [<?php echo $chartOutputData; ?>]
								}
							]
						});

						chart.render();
					}
				</script>
			</section>
		</div>
	</div>
</div>
<?php
else:
	$systemMessages->addMessage("You have not completed any tests.", "info");
	$cdcMastery->redirect("/");
endif;