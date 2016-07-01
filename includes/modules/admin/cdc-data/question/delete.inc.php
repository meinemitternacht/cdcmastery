<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/22/2015
 * Time: 4:39 AM
 */

$answerManager = new AnswerManager($db, $systemLog);
$questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

if(!$questionManager->loadQuestion($workingChild)){
    $systemMessages->addMessage($questionManager->error, "warning");
    $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID());
}

if(isset($_POST['confirmQuestionDelete']) && $_POST['confirmQuestionDelete'] == true){
    if(!$questionManager->archiveQuestion($questionManager->getUUID())){
        $systemMessages->addMessage("There was a problem archiving the question.  Please contact the helpdesk for further assistance.", "danger");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/list-questions");
    }
    else{
        $systemMessages->addMessage("Question archived successfully.", "success");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/list-questions");
    }
}

$questionFOUO = $questionManager->getFOUO();
$answerManager->setFOUO($questionFOUO);
$answerManager->setQuestionUUID($workingChild);
$answerArray = $answerManager->listAnswersByQuestion();
?>
<div class="6u">
    <section>
        <header>
            <h2>Delete Question</h2>
        </header>
        <p>
            <h3>Upon confirmation, this question will be archived for database integrity, but it will not appear in any future tests.</h3>
            <strong>Question</strong>
            <br>
            <?php echo $questionManager->getQuestionText(); ?>
            <br>
            <br>
            <strong>Answers</strong>
            <br>
            <?php
            if($answerArray) {
                foreach ($answerArray as $answerUUID => $answerData) {
                    if ($answerData['answerCorrect'] == true) {
                        echo "<strong><span style=\"color:green;\">" . $answerData['answerText'] . "</span></strong>";
                    } else {
                        echo $answerData['answerText'];
                    }

                    echo "<br>";
                }
            }
            else{
                echo "There are no answers for this question in the database.";
            }
            ?>
        </p>
        <br>
        <form action="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/question/<?php echo $questionManager->getUUID(); ?>/delete" method="POST">
            <input type="hidden" name="confirmQuestionDelete" value="1">
            <input type="submit" value="Confirm Delete">
            <br>
            <br>
            <a href="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/list-questions">Cancel</a>
        </form>
    </section>
</div>
