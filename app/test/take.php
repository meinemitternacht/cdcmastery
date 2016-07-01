<?php
$testManager = new TestManager($db, $systemLog, $afscManager);

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
            $systemLog->setAction("ERROR_TEST_USER_UUID_NOT_EQUAL");
            $systemLog->setDetail("Incomplete Test UUID", $testUUID);
            $systemLog->setDetail("Test Owner", $testManager->getIncompleteUserUUID());
            $systemLog->saveEntry();
            $systemMessages->addMessage("Sorry, but you are not authorized to take tests as other users!", "danger");
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

                function navigateTest(destination) {
                    loading_show();

                    $.post("/ajax/testPlatform/<?php echo $testUUID; ?>", {
                        action: destination
                    }, function (response) {
                        setTimeout("finishAjax('questionAnswer', '" + escape(response) + "')", 500);
                    });

                    return false;

                }

                $(window).keydown(function(e) {
                    switch (e.keyCode) {
                        case 35:
                            e.preventDefault();
                            navigateTest("lastQuestion");
                            return;
                        case 36:
                            e.preventDefault();
                            navigateTest("firstQuestion");
                            return;
                        case 37:
                            navigateTest("previousQuestion");
                            return;
                        case 38:
                            e.preventDefault();
                            return;
                        case 39:
                            navigateTest("nextQuestion");
                            return;
                        case 40:
                            e.preventDefault();
                            return;
                        case 49:
                            $("#answer1").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer1').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 50:
                            $("#answer2").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer2').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 51:
                            $("#answer3").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer3').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 52:
                            $("#answer4").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer4').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 65:
                            $("#answer1").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer1').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 66:
                            $("#answer2").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer2').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 67:
                            $("#answer3").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer3').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 68:
                            $("#answer4").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer4').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 97:
                            $("#answer1").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer1').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 98:
                            $("#answer2").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer2').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 99:
                            $("#answer3").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer3').attr('p');
                            submitAnswer(ansID);
                            return;
                        case 100:
                            $("#answer4").toggleClass("answer-jquery-hover");
                            var ansID = $('#answer4').attr('p');
                            submitAnswer(ansID);
                            return;
                    }
                });

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
                        <div class="test-nav" style="display: none;">
                            <button class="test-nav-button" id="goFirst" title="First Question">&lt;&lt;</button>
                            <button class="test-nav-button" id="goPrevious" title="Previous Question">&lt;</button>
                            <button class="test-nav-button" id="goNext" title="Next Question">&gt;</button>
                            <button class="test-nav-button" id="goLast" title="Last Question">&gt;&gt;</button>
                        </div>
                        <div class="clearfix">&nbsp;</div>
                        <div class="text-center">
                            <strong>Tip:</strong> You can use the <strong>Left</strong> and <strong>Right</strong> arrow keys to navigate through the test.  <strong>Home</strong> and <strong>End</strong> will take you to the first and last questions.<br>
                            To answer a question, click on the answer or press the numbers <strong>1-4</strong> or the letters <strong>a</strong>, <strong>b</strong>, <strong>c</strong>, or <strong>d</strong>.
                        </div>
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
        $systemMessages->addMessage("Sorry, that test does not exist.", "warning");
        $cdcMastery->redirect("/errors/404");
    }
}
else {
    /*
     * Entry point for starting a new test
     */

    if (isset($_POST['startNewTest']) && $_POST['startNewTest'] == true) {
        if(!isset($_POST['userAFSCList']) || empty($_POST['userAFSCList'])){
            $systemMessages->addMessage("You must select at least one AFSC to test with.", "warning");
            $cdcMastery->redirect("/test/take");
        }

        $testManager->newTest();

        foreach ($_POST['userAFSCList'] as $afscUUID) {
            $testManager->addAFSC($afscUUID);
        }

        if ($testManager->populateQuestions()) {
            if ($testManager->incompleteTotalQuestions > 1) {
                $testManager->saveIncompleteTest();

                $systemLog->setAction("TEST_START");
                $systemLog->setDetail("TEST UUID", $testManager->getIncompleteTestUUID());
                $systemLog->setDetail("AFSC ARRAY", serialize($testManager->getIncompleteAFSCList()));
                $systemLog->saveEntry();

                $cdcMastery->redirect("/test/take/" . $testManager->getIncompleteTestUUID());
            } else {
                $systemMessages->addMessage("That AFSC does not have any questions.  If you would like to add them, please contact the support helpdesk.", "info");
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
                            <?php $afscList = $userStatistics->getAFSCAssociations(); ?>
                            <?php if(!empty($afscList)): ?>
                            <p>Tap or click the AFSC categories you wish to test with.  You may select multiple categories from this list.</p>

                            <form id="afscListForm" action="/test/take" method="POST">
                                <input type="hidden" name="startNewTest" value="1">
                                <div id="afscList">
                                    <?php
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
                            </form>
                            <?php else: ?>
                            <p>You are not associated with any AFSC's.  Click "Manage AFSC Associations" to add and remove them, or view ones that are pending approval.</p>
                            <?php endif; ?>
                            <a href="/user/afsc-associations" title="Manage AFSC Associations">Manage AFSC Associations</a>
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
                                                    echo $afscManager->getAFSCName($testData['afscList'][0]);
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