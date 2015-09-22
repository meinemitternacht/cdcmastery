<?php
$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

if(!$questionManager->loadQuestion($workingChild)){
    $sysMsg->addMessage($questionManager->error);
    $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/list-questions");
}

$questionFOUO = $questionManager->getFOUO();

$answerManager->setFOUO($questionFOUO);
$answerManager->setQuestionUUID($workingChild);
$answerArray = $answerManager->listAnswersByQuestion();
?>
<div class="6u">
    <section>
        <header>
            <h2>View Question</h2>
        </header>
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
    </section>
</div>
<div class="3u">
    <section>
        <header>
            <h2>Actions</h2>
        </header>
        <ul>
            <li><a href="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $questionManager->getUUID(); ?>/delete">Delete Question</a></li>
            <li><a href="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $questionManager->getUUID(); ?>/edit">Edit Question</a></li>
        </ul>
    </section>
</div>
