<?php
$statistics = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);
$answerManager = new CDCMastery\AnswerManager($db, $systemLog);
$questionManager = new CDCMastery\QuestionManager($db, $systemLog, $afscManager, $answerManager);

if(!$questionManager->loadQuestion($workingChild)){
    $systemMessages->addMessage($questionManager->error, "warning");
    $cdcMastery->redirect("/admin/cdc-data/".$afscManager->getUUID()."/list-questions");
}

$questionFOUO = $afscManager->getAFSCFOUO();

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
        <ul style="list-style:square;">
        <?php
        if($answerArray) {
            $questionOccurrences = $statistics->getTotalQuestionOccurrences($workingChild);
            foreach ($answerArray as $answerUUID => $answerData) {
                $answerOccurrences = $statistics->getTotalAnswerOccurrences($answerUUID);
                if(($questionOccurrences > 0) && ($answerOccurrences > 0)){
                    $pickPercent = (($answerOccurrences)/($questionOccurrences) * 100);
                    $pickPercentString = "Users picked this answer " . round($pickPercent,2) . "% of the time. The answer was picked " . $answerOccurrences . " " . (($answerOccurrences == 1) ? "time" : "times") . " and the question has been seen " . $questionOccurrences . " " . (($questionOccurrences == 1) ? "time." : "times.");
                }
                else{
                    $pickPercentString = "Users have never picked this answer.";
                }

                if ($answerData['answerCorrect'] == true) {
                    echo "<li style=\"border-bottom: 1px solid #555555; padding-bottom: 0.2em; \" title=\"".$pickPercentString."\"><strong><span style=\"color:green;\">" . $answerData['answerText'] . "</span></strong></li>";
                } else {
                    echo "<li style=\"border-bottom: 1px solid #555555; padding-bottom: 0.2em; \" title=\"".$pickPercentString."\">".$answerData['answerText']."</li>";
                }
            }
        }
        else{
            echo "There are no answers for this question in the database.";
        }
        ?>
        </ul>
        <div class="clearfix">&nbsp;</div>
        <p>
            <strong>Note:</strong> Hover over the answer to see statistics on which one users picked during tests.
        </p>
    </section>
</div>
<div class="3u">
    <section>
        <header>
            <h2>Actions</h2>
        </header>
        <ul>
            <li><a href="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/question/<?php echo $questionManager->getUUID(); ?>/delete">Delete Question</a></li>
            <li><a href="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/question/<?php echo $questionManager->getUUID(); ?>/edit">Edit Question</a></li>
        </ul>
    </section>
</div>
