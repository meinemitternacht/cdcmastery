<?php
$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);
$testList = $testManager->listUserTests($userUUID,false,true);

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
            <div class="3u">
                <section>
                    <header>
                        <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                    </header>
                    <div class="sub-menu">
                        <div class="menu-heading">
                            Test History
                        </div>
                        <ul>
                            <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                        </ul>
                    </div>
                    <div class="clearfix">&nbsp;</div>
                    <a href="/admin/users/<?php echo $userUUID; ?>/tests" title="View Normal History">View Normal History</a>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>Test History</h2>
                    </header>
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

                            if(strlen($testAFSCList) > 24){
                                $testAFSCList = substr($testAFSCList,0,25) . "...";
                            }

                            $chartData[$i]['afscList'] = $testAFSCList;
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
                    $chartData = array_reverse($chartData);
                    $chartOutputData = Array();
                    $firstRow = true;
                    $afscNameStore = "";
                    $i=0;
                    $j=0;
                    foreach($chartData as $key => $testArray) {
                        if($afscNameStore != $testArray['afscList']){
                            $afscNameStore = $testArray['afscList'];
                            $j++;
                        }

                        if(!isset($chartOutputData[$j])){
                            $chartOutputData[$j] = Array('afsc' => '','data' => '');
                        }
                        else {
                            if ($firstRow == false) {
                                $chartOutputData[$j]['data'] = $chartOutputData[$j]['data'] . ",";
                            }
                        }

                        $chartOutputData[$j]['afsc'] = $testArray['afscList'];
                        $chartOutputData[$j]['data'] = $chartOutputData[$j]['data'] . "{ x: " . $i . ", toolTipContent: \"<strong>" . $testArray['afscList'] . "</strong><br>" . $testArray['timeCompleted'] . "<br>Score: <strong>{y}</strong>\", y: " . $testArray['testScore'] . " }";

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
                                    <?php $firstRow = true; ?>
                                    <?php $i = 0; ?>
                                    <?php foreach($chartOutputData as $numKey => $outputData): ?>
                                    <?php if($firstRow == false): ?>
                                    ,{
                                    <?php else: ?>
                                    {
                                        <?php $firstRow = false; ?>
                                        <?php endif; ?>
                                        type: "line",showInLegend: true,name: "series<?php echo $i; ?>",legendText: "<?php echo $outputData['afsc']; ?>",dataPoints: [<?php echo $outputData['data']; ?>] <?php $i++; ?>}
                                    <?php endforeach; ?>
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
    $systemMessages->addMessage("This user has not completed any tests.", "info");
    $cdcMastery->redirect("/admin/users/".$userUUID);
endif;