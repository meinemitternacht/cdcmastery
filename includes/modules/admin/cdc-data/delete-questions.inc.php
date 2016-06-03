<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/22/2015
 * Time: 7:48 AM
 */

$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

if(isset($_POST['confirmQuestionsDelete']) && $_POST['confirmQuestionsDelete'] == true){
    if(!isset($_SESSION['deleteUUIDList'])){
        $sysMsg->addMessage("You must select questions to delete.","warning");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/delete-questions");
    }

    $questionUUIDList = &$_SESSION['deleteUUIDList'];
    $questionUUIDCount = count($questionUUIDList);
    $loopError = false;
    for($i=0;$i<$questionUUIDCount;$i++){
        if(!$questionManager->archiveQuestion($questionUUIDList[$i])){
            $errorArray[] = $questionUUIDList[$i];
            $loopError = true;
        }
    }

    if($loopError && !empty($errorArray)){
        $sysMsg->addMessage("There was a problem deleting the following questions: <br><em>" . implode(" , ",$errorArray) . "</em><br>Please contact the helpdesk for assistance.","danger");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/delete-questions");
    }
    else{
        $sysMsg->addMessage("Questions deleted successfully.","success");
        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID());
    }
} elseif(isset($_POST['showDeleteConfirmMessage']) && $_POST['showDeleteConfirmMessage'] == true){
    $_SESSION['deleteUUIDList'] = $_POST['questionUUIDList'];

    foreach($_SESSION['deleteUUIDList'] as $questionUUID){
        if(!$questionManager->verifyQuestion($questionUUID)){
            unset($_SESSION['deleteUUIDList']);
            $sysMsg->addMessage("There was a problem deleting those questions. Contact the helpdesk for assistance.","danger");
            $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID()."/delete-questions");
        }
    }
}

if(isset($_SESSION['deleteUUIDList'])): ?>
    <?php $uuidCount = count($_SESSION['deleteUUIDList']); ?>
    <div class="9u">
        <section>
            <header>
                <h2>Delete Questions</h2>
            </header>
            <p>
                Are you sure you wish to archive the <?php echo $uuidCount; ?> question<?php echo ($uuidCount > 1) ? "s" : ""; ?> you
                selected?  In order to maintain database integrity, questions are archived upon deletion and will not appear in any
                future tests.
            </p>
            <form action="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/delete-questions" method="POST">
                <input type="hidden" name="confirmQuestionsDelete" id="confirmQuestionsDelete" value="1">
                <input type="submit" value="Delete Selected Questions">
            </form>
        </section>
    </div>
<?php else: ?>
<?php $questionList = $questionManager->listQuestionsForAFSC(); ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#selectAll').click(function(event) {  //on click
            if(this.checked) { // check select status
                $('.deleteQuestionCheckbox').each(function() { //loop through each checkbox
                    this.checked = true;  //select all checkboxes with class "checkbox1"
                });
            }else{
                $('.deleteQuestionCheckbox').each(function() { //loop through each checkbox
                    this.checked = false; //deselect all checkboxes with class "checkbox1"
                });
            }
        });

    });
</script>
<div class="9u">
    <section>
        <header>
            <h2>Delete Questions</h2>
        </header>
        <?php if(empty($questionList)): ?>
        <p>There are no questions in the database for this AFSC.</p>
        <?php else: ?>
        <!--[if !IE]><!-->
        <style type="text/css">
            @media
            only screen and (max-width: 760px),
            (min-device-width: 768px) and (max-device-width: 1024px)  {
                table, thead, tbody, th, td, tr {
                    display: block;
                }

                thead tr {
                    position: absolute;
                    top: -9999px;
                    left: -9999px;
                }

                tr { border: 1px solid #ccc; }

                td {
                    border: none;
                    border-bottom: 1px solid #eee;
                    position: relative;
                    padding-left: 25%;
                }

                td:before {
                    position: absolute;
                    top: 6px;
                    left: 6px;
                    width: 20%;
                    padding-right: 10px;
                    white-space: nowrap;
                }

                table#question-list-table-1 td:nth-of-type(1):before { content: "Select"; }
                table#question-list-table-1 td:nth-of-type(2):before { content: "Question"; }
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
        <form action="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/delete-questions" method="POST">
            <input type="hidden" name="showDeleteConfirmMessage" id="showDeleteConfirmMessage" value="1">
            <input type="submit" value="Delete Selected Questions">
            <table id="question-list-table-1">
                <tr>
                    <th><input type="checkbox" name="selectAll" id="selectAll"></th>
                    <th>Question</th>
                </tr>
                <?php foreach($questionList as $questionUUID): ?>
                    <?php
                    if(!$questionManager->loadQuestion($questionUUID)){
                        $sysMsg->addMessage($questionManager->error,"danger");
                        $cdcMastery->redirect("/admin/cdc-data/".$afsc->getUUID());
                    }
                    ?>
                    <tr>
                        <td><input type="checkbox" class="deleteQuestionCheckbox" name="questionUUIDList[]" value="<?php echo $questionUUID; ?>"></td>
                        <td><?php echo $cdcMastery->formatOutputString($questionManager->getQuestionText(),100); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <input type="submit" value="Delete Selected Questions">
        </form>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>