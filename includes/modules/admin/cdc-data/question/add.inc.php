<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/22/15
 * Time: 10:01 PM
 */

$answerManager = new AnswerManager($db, $systemLog);
$questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

if(!empty($_POST) && $_POST['confirmQuestionAdd'] == true){
    $questionData = !empty($_POST['questionData']) ? $_POST['questionData'] : false;
    $correctAnswer = !empty($_POST['correctAnswer']) ? $_POST['correctAnswer'] : false;

    if(!$questionData){
        $systemMessages->addMessage("You must provide question and answer data.", "warning");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/add-questions");
    }

    $questionDataSplit = preg_split("/\r\n(.)\. /",$questionData);

    if(count($questionDataSplit) == 1){
        $questionData = preg_replace("/( [a]\. )/","\r\na. ",$questionData);
        $questionData = preg_replace("/( [b]\. )/","\r\nb. ",$questionData);
        $questionData = preg_replace("/( [c]\. )/","\r\nc. ",$questionData);
        $questionData = preg_replace("/( [d]\. )/","\r\nd. ",$questionData);
        $questionDataSplit = preg_split("/\r\n(.)\. /",$questionData);
    }

    $answerData[0] = $questionDataSplit[1];
    $answerData[1] = $questionDataSplit[2];
    $answerData[2] = $questionDataSplit[3];
    $answerData[3] = $questionDataSplit[4];

    foreach($answerData as $answerText){
        if(empty($answerText)){
            $emptyAnswer = true;
        }
    }

    if(isset($emptyAnswer)){
        $systemMessages->addMessage("You must provide four answers.  Make sure that each answer has a letter and a period followed by a space at the beginning: e.g., A._ where the underscore is a space.", "warning");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/add-questions");
    }

    function replaceLastDot(&$answerItem,$key){
        $answerItem = preg_replace("/\.$/","",$answerItem);
    }

    array_walk($answerData,"replaceLastDot");

    $questionText = preg_replace("/\r\n/"," ",$questionDataSplit[0]);
    $questionText = preg_replace("/^[0-9]\. \([0-9]+\)/","",$questionText);

    $_SESSION['prevEnteredQuestion']['questionText'] = $questionText;
    $_SESSION['prevEnteredQuestion']['correctAnswer'] = $correctAnswer;
    $_SESSION['prevEnteredQuestion']['answerData'] = $answerData;

    /*
     * Add question to database
     */
    $questionUUID = $cdcMastery->genUUID();
    $questionManager->setUUID($questionUUID);
    $questionManager->setQuestionText($questionText);
    $questionManager->setAFSCUUID($afscManager->getUUID());
    $questionManager->setFOUO($afscManager->getAFSCFOUO());

    if(!$questionManager->saveQuestion()){
        $systemMessages->addMessage("We could not save that question.  Please contact the Help Desk.", "danger");
        $systemMessages->addMessage($questionManager->error, "danger");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/add-questions");
    }
    else{
        /*
         * Add answers to database
         */
        for($i=0;$i<4;$i++){
            $answerManager->newAnswer();
            $answerManager->setFOUO($afscManager->getAFSCFOUO());
            $answerManager->setQuestionUUID($questionUUID);
            $answerManager->setAnswerText($answerData[$i]);

            if($i == $correctAnswer){
                $answerManager->setAnswerCorrect(true);
            }

            if(!$answerManager->saveAnswer()){
                $systemMessages->addMessage($answerManager->error, "danger");
                $systemMessages->addMessage("There may be incomplete data for this question in the database.  Either delete the question or contact the helpdesk.", "danger");
                $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/add-questions");
            }
        }

        $systemLog->setAction("QUESTION_ADD");
        $systemLog->setDetail("Question UUID", $questionUUID);
        $systemLog->setDetail("AFSC UUID", $afscManager->getUUID());
        $systemLog->setDetail("Question Text", $questionText);
        $systemLog->saveEntry();

        $systemMessages->addMessage("Question added successfully.", "success");
        $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/add-questions");
    }
}
?>
<div class="8u">
    <section>
        <header>
            <h2>Add Question</h2>
        </header>
        <form action="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/add-questions" method="POST">
            <input type="hidden" name="confirmQuestionAdd" value="1">
            <div class="informationMessages">
                To add questions, enter the entire question and answer block in the text area below.  You may only add one question at a time.  For any issues, please contact the helpdesk.
                <br>
                <?php if(!isset($_SESSION['prevEnteredQuestion']) || empty($_SESSION['prevEnteredQuestion'])): ?>
<pre>
Which Maintenance Group agency provides technical assistance for Deficiency
Reports to work center supervisors?
a. Plans, Scheduling, and Documentation.
b. Programs and Resources Flight.
c. Maintenance Supply Liaison.
d. Quality Assurance.
</pre>
                <?php else: ?>
                    <strong>Previously Entered Question:</strong><br/>
                    <?php if(isset($_SESSION['prevEnteredQuestion']['questionText'])){ echo $_SESSION['prevEnteredQuestion']['questionText']; } else { echo "NULL"; } ?>
                    <br>
                    <strong>Answers:</strong><br/>
                    1. <?php if(isset($_SESSION['prevEnteredQuestion']['answerData'][0])){ echo $_SESSION['prevEnteredQuestion']['answerData'][0]; } else { echo "NULL"; } ?>
                    <?php if(isset($_SESSION['prevEnteredQuestion']['correctAnswer']) && $_SESSION['prevEnteredQuestion']['correctAnswer'] == 0){ echo "<strong>&lt;&lt;</strong>"; } ?><br>
                    2. <?php if(isset($_SESSION['prevEnteredQuestion']['answerData'][1])){ echo $_SESSION['prevEnteredQuestion']['answerData'][1]; } else { echo "NULL"; } ?>
                    <?php if(isset($_SESSION['prevEnteredQuestion']['correctAnswer']) && $_SESSION['prevEnteredQuestion']['correctAnswer'] == 1){ echo "<strong>&lt;&lt;</strong>"; } ?><br>
                    3. <?php if(isset($_SESSION['prevEnteredQuestion']['answerData'][2])){ echo $_SESSION['prevEnteredQuestion']['answerData'][2]; } else { echo "NULL"; } ?>
                    <?php if(isset($_SESSION['prevEnteredQuestion']['correctAnswer']) && $_SESSION['prevEnteredQuestion']['correctAnswer'] == 2){ echo "<strong>&lt;&lt;</strong>"; } ?><br>
                    4. <?php if(isset($_SESSION['prevEnteredQuestion']['answerData'][3])){ echo $_SESSION['prevEnteredQuestion']['answerData'][3]; } else { echo "NULL"; } ?>
                    <?php if(isset($_SESSION['prevEnteredQuestion']['correctAnswer']) && $_SESSION['prevEnteredQuestion']['correctAnswer'] == 3){ echo "<strong>&lt;&lt;</strong>"; } ?>
                <?php endif; ?>
            </div>
            <label for="questionData">Question and answers:</label>
            <textarea type="text" class="input_full" name="questionData" id="questionData" style="width: 100%;height: 8em;"></textarea>
            <br>
            <br>
            Correct Answer:
            <select name="correctAnswer" id="correctAnswer">
                <option value="0">A</option>
                <option value="1">B</option>
                <option value="2">C</option>
                <option value="3">D</option>
            </select>
            <br>
            <input type="submit" value="Add Question">
        </form>
    </section>
</div>
