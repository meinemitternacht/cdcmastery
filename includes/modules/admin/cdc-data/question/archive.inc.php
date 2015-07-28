<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/22/15
 * Time: 10:01 PM
 */

if(!$qManager->verifyQuestion($workingChild)){
    $sysMsg->addMessage("That question does not exist.");
    $cdcMastery->redirect("/admin/cdc-data/" . $workingAFSC);
}
else{
    $qManager->loadQuestion($workingChild);
    if($qManager->queryQuestionFOUO($workingChild)){
        $aManager->setFOUO(true);
    }
}

if(!empty($_POST)){
    if(isset($_POST['confirmQuestionArchive'])){
        if($qManager->archiveQuestion($workingChild)){
            $sysMsg->addMessage("Question archived.");
            $cdcMastery->redirect("/admin/cdc-data/". $workingAFSC);
        }
    }
}
?>
<div class="9u">
    <section>
        <header>
            <h2>Archive Question</h2>
        </header>
        <p>
            <div class="systemMessages">
                <strong>Question</strong>
                <br>
                <?php echo $qManager->getQuestionText(); ?>
                <br>
                <strong>Answer</strong>
                <br>
                <?php
                $answerArray = $qManager->loadAssociatedAnswers(); ?>
                <?php foreach($answerArray as $answerUUID => $answerData): ?>
                    <?php if($answerData['answerCorrect']): ?>
                        <?php echo $answerData['answerText']; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="clearfix"><br></div>
            </div>
            <br>
            If you wish to archive this question, please press confirm. Otherwise, return to the <a href="/admin/cdc-data/<?php echo $workingAFSC; ?>"><?php echo $afsc->getAFSCName($workingAFSC); ?> overview</a>.
            <br>
            <br>
            <form action="/admin/cdc-data/<?php echo $workingAFSC; ?>/<?php echo $subsection; ?>/<?php echo $workingChild; ?>/<?php echo $action; ?>" method="POST">
                <input type="hidden" name="confirmQuestionArchive" value="1">
                <input type="submit" value="Confirm">
            </form>
        </p>
    </section>
</div>