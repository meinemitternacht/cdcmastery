<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 3/14/2016
 * Time: 7:07 PM
 */

$pageSection = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$genTestUUID = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

$genTestManager = new testGenerator($db,$log,$afsc);

if(!empty($_POST)){
    $afscUUID = isset($_POST['afscUUID']) ? $_POST['afscUUID'] : false;
    $numQuestions = isset($_POST['numQuestions']) ? $_POST['numQuestions'] : false;

    if(!$afscUUID){
        $sysMsg->addMessage("You must choose an AFSC to generate a test for.");
    }

    if(!$numQuestions){
        $sysMsg->addMessage("You must specify the number of questions you desire");
    }

    $genTestManager->setAfscUUID($afscUUID);
    if($genTestManager->generateTest($numQuestions)){
        $sysMsg->addMessage("Test generated successfully.");
    }
    else{
        $sysMsg->addMessage("Sorry, there was a problem generating that test.  Please contact the helpdesk for assistance.");
    }
}

if($genTestUUID){
    if(!$genTestManager->loadGeneratedTest($genTestUUID)){
        $sysMsg->addMessage("Sorry, we could not load that test from the database.  Please contact the helpdesk for assistance.");
        $cdcMastery->redirect("/training/generate-test");
    }
}

if($pageSection == "print"){
    $afsc->loadAFSC($genTestManager->getAfscUUID());
    ?>
    <h1 style="font-size: 1.8em;"><?php echo $afsc->getAFSCName($genTestManager->getAfscUUID()); ?> Practice Test</h1>
    <?php if(!empty($afsc->getAFSCVersion())): ?>
        <em>Version: <?php echo $afsc->getAFSCVersion(); ?></em><br>
    <?php endif; ?>
    <em>Test ID: <?php echo $genTestUUID; ?> created on <?php echo $cdcMastery->formatDateTime($genTestManager->getDateCreated()); ?></em>
    <br>
    <br>
    <?php
    $i = 1;
    $k = 1;
    $ansLetterArray = Array(    1 => 'a',
                                2 => 'b',
                                3 => 'c',
                                4 => 'd');

    $questionList = $genTestManager->getQuestionList();

    $answerManager = new answerManager($db,$log);
    $question = new questionManager($db,$log,$afsc,$answerManager);

    foreach($questionList as $questionUUID):
        $j = 1;

        $answerManager->setFOUO($question->queryQuestionFOUO($questionUUID));

        if($question->loadQuestion($questionUUID)):
            $answerList = $question->loadAssociatedAnswers($questionUUID,true);
            ?>
            <div>
                <strong><?php echo $i; ?>. <?php echo $question->getQuestionText(); ?></strong>
                <br>
                <?php foreach($answerList as $answerUUID => $gibberish): ?>
                    <?php $answerManager->loadAnswer($answerUUID); ?>
                    <?php echo $ansLetterArray[$j]; ?>. <?php echo $answerManager->getAnswerText(); ?><br>
                    <?php
                    if($answerManager->getAnswerCorrect()){
                        $answerKey[$i] = $j;
                    }
                    ?>
                    <?php $j++; ?>
                <?php endforeach; ?>
            </div>
            <br>
            <?php
            if($k == 6){
                echo '<div style="page-break-after:always;"></div>';
                echo '<em>Test '.$genTestUUID.' created on '.$cdcMastery->formatDateTime($genTestManager->getDateCreated()).'</em><br><br>';
                $k=1;
            }
            else{
                $k++;
            }
        endif;
        $i++;
    endforeach; ?>
    <div style="page-break-after:always;"></div>
    <a name="answer-key"></a>
    <h2><?php echo $afsc->getAFSCName($genTestManager->getAfscUUID()); ?> Practice Test Answer Key</h2>
    <?php if(!empty($afsc->getAFSCVersion())): ?>
        <em>Version: <?php echo $afsc->getAFSCVersion(); ?></em><br>
    <?php endif; ?>
    <em>Test ID: <?php echo $genTestUUID; ?> created on <?php echo $cdcMastery->formatDateTime($genTestManager->getDateCreated()); ?></em>
    <div class="clearfix">&nbsp;</div>
    <div style="float:left;width:13%;">
        <?php
        $m=0;

        $total = count($answerKey);
        $split = intval($total/6);

        foreach($answerKey as $key => $val){
            echo "<strong>".$key."</strong>: ".strtoupper($ansLetterArray[$val])."<br /><br />";
            if($m == $split){
                echo "</div><div style=\"float:left;width:13%;\">\r\n";
                $m=0;
            }
            else{
                $m++;
            }
        }
        ?>
    </div>
<?php
}
else {
    ?>
    <div class="container">
        <?php if($pageSection == "view"): ?>
        <div class="row">
            <div class="12u">
                <section>
                    <header>
                        <h2>View Generated Test</h2>
                    </header>
                    <a href="/training/generate-test"
                       class="button">&laquo; Back</a>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="12u">
                <section>
                    <a href="/training/generate-test/print/<?php echo $genTestUUID; ?>">Print this test</a> | <a href="#answer-key">View Answer Key</a>
                    <br>
                    <br>
                    <?php $afsc->loadAFSC($genTestManager->getAfscUUID()); ?>
                    <h1 style="font-size: 1.8em;"><?php echo $afsc->getAFSCName($genTestManager->getAfscUUID()); ?> Practice Test</h1>
                    <?php if(!empty($afsc->getAFSCVersion())): ?>
                        <em>Version: <?php echo $afsc->getAFSCVersion(); ?></em><br>
                    <?php endif; ?>
                    <em>Test ID: <?php echo $genTestUUID; ?> created on <?php echo $cdcMastery->formatDateTime($genTestManager->getDateCreated()); ?></em>
                    <br>
                    <br>
                    <?php
                    $i = 1;
                    $ansLetterArray = Array(    1 => 'a',
                                                2 => 'b',
                                                3 => 'c',
                                                4 => 'd');

                    $questionList = $genTestManager->getQuestionList();

                    $answerManager = new answerManager($db,$log);
                    $question = new questionManager($db,$log,$afsc,$answerManager);

                    foreach($questionList as $questionUUID):
                        $j = 1;

                        $answerManager->setFOUO($question->queryQuestionFOUO($questionUUID));

                        if($question->loadQuestion($questionUUID)):
                            $answerList = $question->loadAssociatedAnswers($questionUUID,true);
                            ?>
                            <div>
                                <strong><?php echo $i; ?>. <?php echo $question->getQuestionText(); ?></strong>
                                <br>
                                <?php foreach($answerList as $answerUUID => $gibberish): ?>
                                    <?php $answerManager->loadAnswer($answerUUID); ?>
                                    <?php echo $ansLetterArray[$j]; ?>. <?php echo $answerManager->getAnswerText(); ?><br>
                                    <?php
                                    if($answerManager->getAnswerCorrect()){
                                        $answerKey[$i] = $j;
                                    }
                                    ?>
                                    <?php $j++; ?>
                                <?php endforeach; ?>
                            </div>
                            <br>
                        <?php
                        endif;
                        $i++;
                    endforeach; ?>
                    <a name="answer-key"></a>
                    <h2><?php echo $afsc->getAFSCName($genTestManager->getAfscUUID()); ?> Practice Test Answer Key</h2>
                    <?php if(!empty($afsc->getAFSCVersion())): ?>
                        <em>Version: <?php echo $afsc->getAFSCVersion(); ?></em><br>
                    <?php endif; ?>
                    <em>Test ID: <?php echo $genTestUUID; ?> created on <?php echo $cdcMastery->formatDateTime($genTestManager->getDateCreated()); ?></em>
                    <div class="clearfix">&nbsp;</div>
                    <div style="float:left;width:13%;">
                        <?php
                        $m=0;

                        $total = count($answerKey);
                        $split = intval($total/6);

                        foreach($answerKey as $key => $val){
                            echo "<strong>".$key."</strong>: ".strtoupper($ansLetterArray[$val])."<br /><br />";
                            if($m == $split){
                                echo "</div><div style=\"float:left;width:13%;\">\r\n";
                                $m=0;
                            }
                            else{
                                $m++;
                            }
                        }
                        ?>
                    </div>
                </section>
            </div>
        </div>
        <?php else: ?>
        <?php $userGenTestList = $genTestManager->listGeneratedTests($_SESSION['userUUID']); ?>
        <div class="row">
            <div class="12u">
                <section>
                    <header>
                        <h2>Generate Offline Tests</h2>
                    </header>
                    <a href="/training/overview" class="button">&laquo; Back</a>
                    <br>
                    <br>
                    <p>
                        After you choose an AFSC to generate a test for, enter the desired number of questions and click "Generate Test".  If the AFSC you chose does not have
                        enough questions, it will output all questions for that AFSC.  Each test is randomly generated and will contain an answer key on the last page when printed.
                    </p>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="4u">
                <section>
                    <header>
                        <h2>Generate Test</h2>
                    </header>
                    <form action="/training/generate-test" method="POST">
                        <label for="afscUUID">AFSC</label>
                        <select id="afscUUID"
                                name="afscUUID"
                                class="input_full">
                            <?php
                            $afscList = $afsc->listAFSC(false);
                            foreach ($afscList as $afscUUID => $afscDetails): ?>
                                <option
                                    value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                <?php
                            endforeach;
                            ?>
                        </select>
                        <br>
                        <br>
                        <label for="numQuestions">Desired Questions</label>
                        <br>
                        <em>(Default is 100 questions)</em>
                        <input type="text" name="numQuestions" id="numQuestions" class="input_full" maxlength="4"
                               value="100">
                        <br>
                        <br>
                        <input type="submit" value="Generate Test">
                    </form>
                </section>
            </div>
            <div class="8u">
                <section>
                    <header>
                        <h2>Your Generated Tests</h2>
                    </header>
                    <?php if (!empty($userGenTestList)): ?>
                        <table>
                            <tr>
                                <th>AFSC</th>
                                <th>Total Questions</th>
                                <th>Date Created</th>
                                <th>Link</th>
                            </tr>
                            <?php foreach ($userGenTestList as $genTestUUID => $genTestDetails): ?>
                                <tr>
                                    <td><?php echo $afsc->getAFSCName($genTestDetails['afscUUID']); ?></td>
                                    <td><?php echo number_format($genTestDetails['totalQuestions']); ?></td>
                                    <td>
                                        <?php
                                        echo $cdcMastery->formatDateTime($genTestDetails['dateCreated']);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="/training/generate-test/view/<?php echo $genTestUUID; ?>">View &raquo;</a>
                                        <a href="/training/generate-test/print/<?php echo $genTestUUID; ?>">Print &raquo;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>You have not generated any tests.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}