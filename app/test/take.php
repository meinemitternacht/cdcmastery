<?php
$testManager = new testManager($db, $log, $afsc);

if(isset($_SESSION['vars'][0]) && !empty($_SESSION['vars'][0])) {
    /*
     * Entry point for test in progress, or after resuming a test
     */
    $testUUID = $_SESSION['vars'][0];

    /*
     * Ensure test is valid
     */
    if ($testManager->loadIncompleteTest($testUUID)) {
        if(empty($testManager->getIncompleteUserUUID())){
            $cdcMastery->redirect("/test/take");
        }
        if($_SESSION['userUUID'] != $testManager->getIncompleteUserUUID()) {
            /*
             * Not this user's test!!  Oh boy...
             */
            $log->setAction("ERROR_TEST_USER_UUID_NOT_EQUAL");
            $log->setDetail("Incomplete Test UUID",$testUUID);
            $log->setDetail("Test Owner",$testManager->getIncompleteUserUUID());
            $log->saveEntry();
            $sysMsg->addMessage("Sorry, but you are not authorized to take tests as other users!");
            $cdcMastery->redirect("/errors/403");
        }
        ?>
        <script type="text/javascript">

            $(document).ready(function () {
                function loading_show() {
                    $('#loading').html("<img src='/images/loader.gif'>").fadeIn('fast');
                }

                function loading_hide() {
                    $('#loading').fadeOut('fast');
                }

                function submitAnswer(answer) {
                    loading_show();

                    $.ajax({
                        type: "POST",
                        url: "/ajax/testPlatform/<?php echo $testUUID; ?>",
                        data: {'action': 'answerQuestion', 'actionData': answer},
                        success: function (response) {
                            setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                        }
                    });
                }


                $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                    action: 'specificQuestion',
                    actionData: '1'
                }, function (response) {
                    setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                });

                $(document).on('click', '#answer1', function () {
                    var ansID = $(this).attr('p');
                    submitAnswer(ansID);
                });

                $(document).on('click', '#answer2', function () {
                    var ansID = $(this).attr('p');
                    submitAnswer(ansID);
                });

                $(document).on('click', '#answer3', function () {
                    var ansID = $(this).attr('p');
                    submitAnswer(ansID);
                });

                $(document).on('click', '#answer4', function () {
                    var ansID = $(this).attr('p');
                    submitAnswer(ansID);
                });

                $('#goFirst').click(function () {
                    loading_show();

                    $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                        action: 'firstQuestion'
                    }, function (response) {
                        setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                    });

                    return false;

                });

                $('#goPrevious').click(function () {
                    loading_show();

                    $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                        action: 'previousQuestion'
                    }, function (response) {
                        setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                    });

                    return false;

                });

                $('#goNext').click(function () {
                    loading_show();

                    $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                        action: 'nextQuestion'
                    }, function (response) {
                        setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                    });

                    return false;

                });

                $('#goLast').click(function () {
                    loading_show();

                    $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                        action: 'lastQuestion'
                    }, function (response) {
                        setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                    });

                    return false;

                });

            });

            function finishAjax(id, response) {
                $('#loading').fadeOut('fast');
                $('#' + id).html(unescape(response));
                $('.test-nav').fadeIn('fast');

                var submitTest = document.getElementById("submitTest");
                if (submitTest != null) {
                    $('#storeAnswer').fadeOut();
                }
                else {
                    $('#storeAnswer').show();
                }
            }

        </script>
        <div id="loading"><img src="/images/loader.gif"/></div>
        <div class="container">
            <div class="row">
                <div class="12u">
                    <section>
                        <div id="questionAnswer"></div>
                    </section>
                </div>
            </div>
            <div class="row">
                <div class="12u">
                    <section>
                        <div class="test-nav" style="display: none;">
                            <button class="test-nav-button" id="goFirst">&lt;&lt;</button>
                            <button class="test-nav-button" id="goPrevious">&lt;</button>
                            <button class="test-nav-button" id="goNext">&gt;</button>
                            <button class="test-nav-button" id="goLast">&gt;&gt;</button>
                        </div>
                        <div class="clearfix">&nbsp;</div>
                    </section>
                </div>
            </div>
        </div>
    <?php
    } elseif ($testManager->loadTest($testUUID)) {
        /*
         * Test has already been scored
         */
        $cdcMastery->redirect("/test/view/" . $testUUID);
    } else {
        /*
         * Test does not exist.
         */
        $sysMsg->addMessage("Sorry, that test does not exist.");
        $cdcMastery->redirect("/errors/404");
    }
}
else {
    /*
     * Entry point for a new test
     */

    if (!empty($_POST)) {
        $testManager->newTest();

        foreach ($_POST['userAFSCList'] as $afscUUID) {
            $testManager->addAFSC($afscUUID);
        }

        if ($testManager->populateQuestions()) {
            if ($testManager->incompleteTotalQuestions > 1) {
                $testManager->saveIncompleteTest();

                $log->setAction("TEST_START");
                $log->setDetail("TEST UUID", $testManager->getIncompleteTestUUID());
                $log->setDetail("AFSC ARRAY", serialize($testManager->getIncompleteAFSCList()));
                $log->saveEntry();

                $cdcMastery->redirect("/test/take/" . $testManager->getIncompleteTestUUID());
            } else {
                $sysMsg->addMessage("That AFSC does not have any questions.  If you would like to add them, please contact the support helpdesk.");
                $cdcMastery->redirect("/test/take");
            }
        } else {
            echo $testManager->error;
        }
    } else {
        ?>
        <script>
            $(function () {
                $("#afscList").buttonset();

                $("#startTest").click(function () {
                    $("#afscListForm").submit();
                });
            });
        </script>
        <div class="container">
            <div class="row">
                <?php if ($userStatistics->getIncompleteTests() >= 5): ?>
                    <div class="4u">
                        <section>
                            <header>
                                <h2>Start new test</h2>
                            </header>
                            <div class="systemMessages">
                                Sorry, you can't start a new test until you complete or delete your
                                incomplete tests.
                            </div>
                        </section>
                    </div>
                <?php else: ?>
                    <div class="4u">
                        <section>
                            <header>
                                <h2>Start a new test</h2>
                            </header>
                            <p>Tap or click the AFSC categories you wish to test with.  You may select multiple categories from this list.</p>

                            <form id="afscListForm" action="/test/take" method="POST">
                                <div id="afscList">
                                    <?php
                                    $afscList = $userStatistics->getAFSCAssociations();

                                    $i = 0;
                                    foreach ($afscList as $afscUUID => $afscName): ?>
                                        <input type="checkbox" name="userAFSCList[]" id="checkbox<?php echo $i; ?>"
                                               value="<?php echo $afscUUID; ?>">
                                        <label for="checkbox<?php echo $i; ?>"><?php echo $afscName; ?></label>
                                        <br>
                                        <?php
                                        $i++;
                                    endforeach;
                                    ?>
                                </div>
                                <div class="sub-menu">
                                    <ul>
                                        <li>
                                            <a id="startTest"><i class="icon-inline icon-20 ic-arrow-right"></i>Start Test</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="clearfix">&nbsp;</div>
                                <a href="/user/afsc-associations" title="Manage AFSC Associations">Manage AFSC Associations</a>
                            </form>
                        </section>
                    </div>
                <?php endif;

                if ($userStatistics->getIncompleteTests()):
                    $userIncompleteTests = $testManager->listUserIncompleteTests($_SESSION['userUUID']);
                    ?>
                    <div class="4u">
                        <section>
                            <header>
                                <h2>Resume an Incomplete Test</h2>
                            </header>
                            <p>Click on a test to resume where you left off.</p>

                            <div class="sub-menu">
                                <ul>
                                    <?php foreach ($userIncompleteTests as $testUUID => $testData): ?>
                                        <li>
                                            <a href="/test/resume/<?php echo $testUUID; ?>">
                                                <?php if (count($testData['afscList']) > 1) {
                                                    echo "Multiple AFSC's";
                                                } else {
                                                    echo $afsc->getAFSCName($testData['afscList'][0]);
                                                } ?>
                                                <br>
                                                <?php echo $cdcMastery->outputDateTime($testData['timeStarted'], $_SESSION['timeZone']); ?>
                                                <br>
                                                <?php echo round((($testData['questionsAnswered'] / $testData['totalQuestions']) * 100), 2); ?>
                                                % complete
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="clearfix">&nbsp;</div>
                            <br>

                            <div class="text-right text-warning">
                                <a href="/test/delete/incomplete/all"><i class="icon-inline icon-20 ic-delete"></i>Delete Incomplete Tests</a></div>
                        </section>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }
}