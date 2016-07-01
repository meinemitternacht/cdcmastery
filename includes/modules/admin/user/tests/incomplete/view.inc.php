<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/11/2015
 * Time: 3:46 AM
 */

$testManager = new TestManager($db, $systemLog, $afscManager);

if(!$testManager->loadIncompleteTest($finalChild)){
    $systemMessages->addMessage("That test does not exist.", "warning");
    $cdcMastery->redirect("/admin/users/".$userUUID."/tests/incomplete");
}

$questionList = $testManager->getIncompleteQuestionList();
if($testManager->loadTestData($finalChild)){
    $testData = $testManager->getTestData();
}

if(!empty($questionList) && is_array($questionList)): ?>
    <div class="container">
        <div class="row">
            <div class="4u">
                <section>
                    <header>
                        <h2>View Incomplete Test</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin/users/<?php echo $userUUID; ?>/tests/incomplete"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to test list</a></li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="12u">
                <script type="text/javascript">
                    (function($)
                    {
                        $(document).ready(function()
                        {
                            $.ajaxSetup(
                                {
                                    cache: false,
                                    beforeSend: function() {
                                    },
                                    complete: function() {
                                    },
                                    success: function() {
                                    }
                                });
                            var $container = $("#viewIncompleteTest");
                            $container.load("/ajax/admin/viewIncompleteTest/<?php echo $finalChild; ?>");
                            var refreshId = setInterval(function()
                            {
                                $container.load("/ajax/admin/viewIncompleteTest/<?php echo $finalChild; ?>");
                            }, 5000);
                        });
                    })(jQuery);

                </script>
                <div id="viewIncompleteTest"></div>
            </div>
        </div>
    </div>
<?php endif; ?>
