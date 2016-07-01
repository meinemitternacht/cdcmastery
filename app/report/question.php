<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/31/2016
 * Time: 7:28 PM
 */

$questionUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$questionUUID){
    $systemMessages->addMessage("You must select a question to report.", "warning");
    $cdcMastery->redirect("/");
}
else{
    $answerManager = new AnswerManager($db, $systemLog);
    $questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

    if(!$questionManager->loadQuestion($questionUUID)){
        $systemMessages->addMessage("That question does not exist.", "warning");
        $cdcMastery->redirect("/");
    }
    else {
        if(isset($_POST['userReportComments']) && !empty($_POST['userReportComments'])){
            if($userManager->reportQuestion($userManager->getUserEmail(), $questionManager->getAFSCUUID(), $questionUUID, $questionManager->getQuestionText(), $_POST['userReportComments'])){
                $systemLog->setAction("REPORT_QUESTION");
                $systemLog->setDetail("Question UUID", $questionUUID);
                $systemLog->setDetail("AFSC UUID", $questionManager->getAFSCUUID());
                $systemLog->setDetail("Report Reason", $_POST['userReportComments']);
                $systemLog->saveEntry();

                $systemMessages->addMessage("Your report has been sent to the CDCMastery help desk successfully.  If we need further information, we will contact you within the next few days.", "success");
                $cdcMastery->redirect("/");
            }
            else{
                $systemLog->setAction("ERROR_REPORT_QUESTION");
                $systemLog->setDetail("Question UUID", $questionUUID);
                $systemLog->setDetail("AFSC UUID", $questionManager->getAFSCUUID());
                $systemLog->setDetail("Report Reason", $_POST['userReportComments']);
                $systemLog->saveEntry();

                $systemMessages->addMessage("There was an issue sending this report to the CDCMastery help desk.  If this issue persists, go to http://helpdesk.cdcmastery.com and submit a ticket instead.", "danger");
                $cdcMastery->redirect("/report/question/" . $questionUUID);
            }
        }
        elseif(isset($_POST['userReportComments']) && empty($_POST['userReportComments'])){
            $systemMessages->addMessage("You must provide details on why this question has an error.", "warning");
            $cdcMastery->redirect("/report/question/" . $questionUUID);
        }

        if($questionManager->getFOUO()){
            $answerManager->setFOUO(true);
        }
        else{
            $answerManager->setFOUO(false);
        }
        $answerManager->setQuestionUUID($questionUUID);
        $answerList = $answerManager->listAnswersByQuestion();
        ?>
        <div class="container">
            <div class="row">
                <div class="8u">
                    <section>
                        <header>
                            <h2>Report Question</h2>
                        </header>
                        <form action="/report/question/<?php echo $questionUUID; ?>" method="POST">
                            Using the form below, please give us more information about why this question is incorrect,
                            along with information on how to correct it.
                            <br>
                            <br>
                            <strong>Question</strong>
                            <p>
                                <?php echo $questionManager->getQuestionText(); ?>
                            </p>
                            <?php if($answerList): ?>
                                <strong>Answers</strong>
                                    <ul>
                                    <?php foreach($answerList as $answerUUID => $answerData): ?>
                                        <?php if($answerData['answerCorrect']): ?>
                                            <li><?php echo $answerData['answerText']; ?></li>
                                        <?php else: ?>
                                            <li><?php echo $answerData['answerText']; ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </ul>
                            <?php endif; ?>
                            <label for="userReportComments">Describe the issue with the question in the box below:</label>
                            <textarea id="userReportComments" name="userReportComments" style="height:6em;"></textarea>
                            <br>
                            <br>
                            <input type="submit" value="Submit Report">
                        </form>
                    </section>
                </div>
            </div>
        </div>
        <?php
    }
}