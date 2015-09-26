<?php
$testManager = new testManager($db, $log, $afsc);
$testList = $testManager->listUserIncompleteTests($userUUID);

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
            table#history-table-1 td:nth-of-type(1):before { content: "Started"; }
            table#history-table-1 td:nth-of-type(2):before { content: "AFSC"; }
            table#history-table-1 td:nth-of-type(3):before { content: "Questions"; }
            table#history-table-1 td:nth-of-type(4):before { content: "Progress"; }
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
                            Incomplete Test History
                        </div>
                        <ul>
                            <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>Incomplete Test History</h2>
                    </header>
                    <table id="history-table-1">
                        <tr>
                            <th>Started</th>
                            <th>AFSC</th>
                            <th>Questions</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach($testList as $testUUID => $testDetails):
                            if(is_array($testDetails['afscList'])){
                                $rawAFSCList = $testDetails['afscList'];

                                foreach($rawAFSCList as $key => $val){
                                    $rawAFSCList[$key] = $afsc->getAFSCName($val);
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

                            $testProgress = intval(($testDetails['questionsAnswered']/$testDetails['totalQuestions'])*100);
                            ?>
                            <tr>
                                <td><?php echo $cdcMastery->outputDateTime($testDetails['timeStarted'], $_SESSION['timeZone']); ?></td>
                                <td><?php echo $testAFSCList; ?></td>
                                <td><?php echo $testDetails['totalQuestions']; ?></td>
                                <td><strong><?php echo $testProgress; ?>%</strong></td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </section>
            </div>
        </div>
    </div>
<?php
else:
    $sysMsg->addMessage("This user has no incomplete tests.");
    $cdcMastery->redirect("/admin/users/" . $userUUID);
endif;