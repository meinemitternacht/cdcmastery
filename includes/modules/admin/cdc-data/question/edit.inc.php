<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 9/25/2015
 * Time: 02:35 PM
 */

$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

if(!$questionManager->loadQuestion($workingChild)){
    $sysMsg->addMessage($questionManager->error,"warning");
    $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/list-questions");
}

if($questionManager->queryQuestionFOUO($workingChild)){
    $answerManager->setFOUO(true);
    $questionManager->setFOUO(true);
}

if(!empty($_POST) && $_POST['confirmQuestionEdit'] == true){
    $questionText = !empty($_POST['questionText']) ? $_POST['questionText'] : false;

    if(!$questionText){
        $sysMsg->addMessage("You must provide question and answer data.","warning");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/add-questions");
    }

    /*
    function replaceLastDot(&$answerItem,$key){
        $answerItem = preg_replace("/\.$/","",$answerItem);
    }

    array_walk($answerData,"replaceLastDot");*/

    $questionText = preg_replace("/\r\n/"," ",$questionText);
    $questionText = preg_replace("/^[0-9]\. \([0-9]+\)/","",$questionText);

    /*
     * Edit question data in database
     */
    $questionManager->setQuestionText($questionText);
    $logQuestionText = $questionText;

    if(!$questionManager->saveQuestion()){
        $sysMsg->addMessage($questionManager->error,"danger");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/question/".$workingChild."/edit");
    }
    else{
        /*
         * Edit answer data in database
         */

        foreach($_POST['answerData'] as $answerUUID => $answerText){
            $answerManager->loadAnswer($answerUUID);
            $answerManager->setAnswerText($answerText);
            $providedCorrectAnswer = $_POST['correctAnswer'];

            if($providedCorrectAnswer == $answerUUID){
                $answerManager->setAnswerCorrect(true);
            }
            else{
                $answerManager->setAnswerCorrect(false);
            }

            if(!$answerManager->saveAnswer()){
                $sysMsg->addMessage($answerManager->error,"danger");
                $sysMsg->addMessage("There may be incomplete data for this question in the database.  Either delete the question or contact the helpdesk.","danger");
                $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/question/".$workingChild."/edit");
            }
        }

        $log->setAction("QUESTION_EDIT");
        $log->setDetail("Question UUID",$questionManager->getUUID());
        $log->setDetail("Question Text",$questionText);

        foreach($_POST['answerData'] as $answerUUID => $answerText) {
            $providedCorrectAnswer = $_POST['correctAnswer'];

            if ($providedCorrectAnswer == $answerUUID) {
                $log->setDetail("Answer Text",$answerText . " [" . $answerUUID . "] (correct)");
            } else {
                $log->setDetail("Answer Text",$answerText . " [" . $answerUUID . "]");
            }
        }

        $log->saveEntry();

        $sysMsg->addMessage("Question edited successfully.","success");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/list-questions");
    }
}
?>
<div class="6u">
    <section>
        <header>
            <h2>Add Question</h2>
        </header>
        <form action="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $workingChild; ?>/edit" method="POST">
            <input type="hidden" name="confirmQuestionEdit" value="1">
            <label for="questionData">Question</label>
            <textarea type="text" class="input_full" name="questionText" id="questionText" style="width: 100%;height: 8em;"><?php echo $questionManager->getQuestionText(); ?></textarea>
            <div class="clearfix">&nbsp;</div>
            <?php $answerManager->setQuestionUUID($workingChild); ?>
            <label>Answers</label>
            <div class="clearfix">&nbsp;</div>
            <?php foreach($answerManager->listAnswersByQuestion() as $answerUUID => $answerData): ?>
                <textarea type="text" style="width:75%;" name="answerData[<?php echo $answerUUID; ?>]" id="answerData[<?php echo $answerUUID; ?>]" style="width: 100%;height: 4em;"><?php echo $answerData['answerText']; ?></textarea>
                <?php if($answerData['answerCorrect']): ?>
                    <input type="radio" name="correctAnswer" value="<?php echo $answerUUID; ?>" CHECKED>
                <?php else: ?>
                    <input type="radio" name="correctAnswer" value="<?php echo $answerUUID; ?>">
                <?php endif; ?>
                <div class="clearfix">&nbsp;</div>
            <?php endforeach; ?>
            <input type="submit" value="Edit Question">
        </form>
    </section>
</div>
