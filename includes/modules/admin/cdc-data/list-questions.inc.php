<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/23/15
 * Time: 2:29 AM
 */

$statistics = new CDCMastery\StatisticsModule($db, $systemLog, $emailQueue, $memcache);
$answerManager = new CDCMastery\AnswerManager($db, $systemLog);
$questionManager = new CDCMastery\QuestionManager($db, $systemLog, $afscManager, $answerManager);
$questionManager->setAFSCUUID($workingAFSC);

$questionList = $questionManager->listQuestionsForAFSC();

if(isset($selectHTML) && !empty($selectHTML)){
    $showForm = true;
}
else{
    $showForm = false;
}
?>
<script>
    $(document).ready(function()
        {
            $("#questionListTable").tablesorter();
        }
    );
</script>
<!--[if !IE]><!-->
<style type="text/css">
    @media only screen and (max-width: 760px),
    (min-device-width: 768px) and (max-device-width: 1024px)  {
        table, thead, tbody, th, td, tr {
            display: block;
        }

        tr { border: 1px solid #ccc; }

        td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 3%;
        }

        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 20%;
            padding-right: 10px;
            white-space: nowrap;
        }
    }

    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body {
            padding: 0;
            margin: 0;
            width: 320px; }
    }
    
    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
        body {
            width: 495px;
        }
    }

</style>
<!--<![endif]-->
<div class="9u">
    <section>
        <?php if(!empty($questionList)): ?>
        <p>
            <strong>Note:</strong> Click on the column headers to sort by that column.
        </p>
        <table id="questionListTable" class="tablesorter">
            <thead>
            <tr>
                <th>Question Text (Truncated)</th>
                <th title="How many times this question has appeared on a test.">Times Shown</th>
                <th title="Percent of the time this question has been answered correctly.">% Correct</th>
                <?php if($showForm): ?>
                <th>Volume</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach($questionList as $uuid): ?>
                <?php
                $questionManager->loadQuestion($uuid);
                $questionAnswerPairOccurrences = $statistics->getTotalQuestionAnswerPairOccurrences($uuid,$answerManager->getCorrectAnswer($uuid));
                $questionOccurrences = $statistics->getTotalQuestionOccurrences($uuid);

                if(($questionOccurrences > 0)){
                    $percentCorrect = round((($questionAnswerPairOccurrences / $questionOccurrences) * 100),2);
                }
                else{
                    $percentCorrect = 0;
                }
                ?>
                <tr>
                    <td><a href="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/question/<?php echo $uuid; ?>/view"><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(), 80);  ?></a></td>
                    <td><?php echo $questionOccurrences; ?></td>
                    <?php if($percentCorrect >= 80): ?>
                        <td class="text-success"><?php echo $percentCorrect; ?>%</td>
                    <?php elseif(($percentCorrect >= 60) && ($percentCorrect < 80)): ?>
                        <td class="text-caution"><?php echo $percentCorrect; ?>%</td>
                    <?php else: ?>
                        <td class="text-warning"><?php echo $percentCorrect; ?>%</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        There are no questions for this AFSC.
        <?php endif; ?>
    </section>
</div>