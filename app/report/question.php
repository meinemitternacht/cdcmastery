<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/31/2016
 * Time: 7:28 PM
 */

$questionUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$questionUUID){
    $sysMsg->addMessage("You must select a question to report.");
    $cdcMastery->redirect("/");
}
else{
    $answerManager = new answerManager($db,$log);
    $questionManager = new questionManager($db,$log,$afsc,$answerManager);

    if(!$questionManager->loadQuestion($questionUUID)){
        $sysMsg->addMessage("That question does not exist.");
        $cdcMastery->redirect("/");
    }
    else {
        if(isset($_POST['userReportComments']) && !empty($_POST['userReportComments'])){
            if($user->reportQuestion($questionUUID,$questionManager->getQuestionText(),$_POST['userReportComments'])){
                $log->setAction("REPORT_QUESTION");
                $log->setDetail("Question UUID",$questionUUID);
                $log->setDetail("Report Reason",$_POST['userReportComments']);
                $log->saveEntry();

                $sysMsg->addMessage("Your report has been sent to the CDCMastery Helpdesk successfully.  If we need further information, we will contact you within the next few days.");
                $cdcMastery->redirect("/");
            }
            else{
                $log->setAction("ERROR_REPORT_QUESTION");
                $log->setDetail("Question UUID",$questionUUID);
                $log->setDetail("Report Reason",$_POST['userReportComments']);
                $log->saveEntry();

                $sysMsg->addMessage("There was an issue sending this report to the CDCMastery Helpdesk.  If this issue persists, go to http://helpdesk.cdcmastery.com and submit a ticket.");
                $cdcMastery->redirect("/report/question/" . $questionUUID);
            }
        }
        elseif(isset($_POST['userReportComments']) && empty($_POST['userReportComments'])){
            $sysMsg->addMessage("You must provide details on why this question has an error.");
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
                                            <li><span class="text-success-bold"><?php echo $answerData['answerText']; ?></span></li>
                                        <?php else: ?>
                                            <li><span class="text-warning"><?php echo $answerData['answerText']; ?></span></li>
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