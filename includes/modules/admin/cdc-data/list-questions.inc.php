<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/23/15
 * Time: 2:29 AM
 */

$statistics = new statistics($db,$log,$emailQueue);
$answerManager = new answerManager($db, $log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);
$questionManager->setAFSCUUID($workingAFSC);

$questionList = $questionManager->listQuestionsForAFSC();

$childSetList = $questionManager->listChildSets($workingAFSC);

if($childSetList){
    if(is_array($childSetList)){
        $selectHTML = "";
        foreach($childSetList as $childSet){
            $childVolumeList = $questionManager->listChildVolumes($childSet);

            if(is_array($childVolumeList)) {
                $setName = $questionManager->getSetName($childSet);
                $selectHTML .= '<optgroup label="' . $setName . '">';
                foreach($childVolumeList as $childVolume){
                    $selectHTML .= '<option value="' . $childVolume . '">' . $questionManager->getVolumeName($childVolume) . '</option>';
                }
                $selectHTML .= '</optgroup>';
            }
        }
    }
}

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
    /*
    Max width before this PARTICULAR table gets nasty
    This query will take effect for any screen smaller than 760px
    and also iPads specifically.
    */
    @media
    only screen and (max-width: 760px),
    (min-device-width: 768px) and (max-device-width: 1024px)  {

        /* Force table to not be like tables anymore */
        table, thead, tbody, th, td, tr {
            display: block;
        }

        tr { border: 1px solid #ccc; }

        td {
            /* Behave  like a "row" */
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 3%;
        }

        td:before {
            /* Now like a table header */
            position: absolute;
            /* Top/left values mimic padding */
            top: 6px;
            left: 6px;
            width: 20%;
            padding-right: 10px;
            white-space: nowrap;
        }
    }

    /* Smartphones (portrait and landscape) ----------- */
    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body {
            padding: 0;
            margin: 0;
            width: 320px; }
    }

    /* iPads (portrait and landscape) ----------- */
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
                    <td><a href="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $uuid; ?>/view"><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(),80);  ?></a></td>
                    <td><?php echo $questionOccurrences; ?></td>
                    <?php if($percentCorrect >= 80): ?>
                        <td class="text-success"><?php echo $percentCorrect; ?>%</td>
                    <?php elseif(($percentCorrect >= 60) && ($percentCorrect < 80)): ?>
                        <td class="text-caution"><?php echo $percentCorrect; ?>%</td>
                    <?php else: ?>
                        <td class="text-warning"><?php echo $percentCorrect; ?>%</td>
                    <?php endif; ?>
                    <?php if($showForm): ?>
                    <td>
                        <select name="selectVolume[<?php echo $uuid; ?>]" size="1" disabled="disabled">
                            <?php echo $selectHTML; ?>
                        </select>
                    </td>
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