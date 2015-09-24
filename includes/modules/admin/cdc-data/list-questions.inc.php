<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/23/15
 * Time: 2:29 AM
 */

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

        /* Hide table headers (but not display: none;, for accessibility) */
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
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

        /*
        Label the data
        */
        <?php if($showForm): ?>
        table#questionListTable td:nth-of-type(2):before { content: "Volume"; }
        <?php endif; ?>
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
        <table id="questionListTable">
            <tr>
                <th>Question Text (Truncated)</th>
                <?php if($showForm): ?>
                <th>Volume</th>
                <?php endif; ?>
            </tr>
            <?php foreach($questionList as $uuid): ?>
                <?php $questionManager->loadQuestion($uuid); ?>
                <tr>
                    <td><a href="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $uuid; ?>/view"><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(),100);  ?></a></td>
                    <?php if($showForm): ?>
                    <td>
                        <select name="selectVolume[<?php echo $uuid; ?>]" size="1" disabled="disabled">
                            <?php echo $selectHTML; ?>
                        </select>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        There are no questions for this AFSC.
        <?php endif; ?>
    </section>
</div>