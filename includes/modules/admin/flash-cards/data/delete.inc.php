<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmFlashCardDelete'])){
    if($flashCardManager->deleteFlashCardData($actionChild)){
        $sysMsg->addMessage("Flash card deleted successfully.","success");
        $cdcMastery->redirect("/admin/card-data/".$workingChild);
    }
    else{
        $sysMsg->addMessage("That flash card could not be deleted. Contact the support help desk for assistance.","danger");
    }
}

if(isset($_POST['confirmDeleteMultipleFlashCards'])){
    if(!isset($_POST['flashCardUUIDList']) || !is_array($_POST['flashCardUUIDList']) || empty($_POST['flashCardUUIDList'])){
        $sysMsg->addMessage("You must specify flash cards to delete.","warning");
        $cdcMastery->redirect("/admin/card-data/".$workingChild);
    }
    else{
        $error = false;
        foreach($_POST['flashCardUUIDList'] as $flashCardUUID){
            if(!$flashCardManager->deleteFlashCardData($flashCardUUID)){
                $error = true;
            }
        }

        if(!$error){
            $sysMsg->addMessage("Flash cards deleted successfully.","success");
            $cdcMastery->redirect("/admin/card-data/".$workingChild);
        }
        else{
            $sysMsg->addMessage("Some flash cards could not be deleted. Contact the support help desk for assistance.","danger");
        }
    }
}

if(!empty($actionChild)):
    if(!$flashCardManager->loadFlashCardData($actionChild)){
        $sysMsg->addMessage("That flash card does not exist.","warning");
        $cdcMastery->redirect("/admin/card-data/".$workingChild);
    }
    ?>
    <section>
        <header>
            <h2>Delete flash card</h2>
        </header>
        <form action="/admin/card-data/<?php echo $workingChild; ?>/delete/<?php echo $actionChild; ?>" method="POST">
            <input type="hidden" name="confirmFlashCardDelete" value="1">
            <p>
                Are you sure you wish to delete this flash card?
            </p>
            <ul>
                <li><strong>Front:</strong> <?php echo $flashCardManager->getFrontText(); ?></li>
                <li><strong>Back:</strong> <?php echo $flashCardManager->getBackText(); ?></li>
            </ul>
            <input type="submit" value="Delete Flash Card">
        </form>
    </section>
<?php
else:
    if($flashCardManager->listFlashCards()){
        $flashCardList = $flashCardManager->flashCardArray;
    }
    ?>
    <section>
        <header>
            <h2>Delete Flash Card Data for <?php echo $flashCardManager->getCategoryName(); ?></h2>
        </header>
        <?php if($flashCardManager->getCategoryType() == "afsc"): ?>
            <strong>This flash card category is URE Bound, meaning it pulls data directly from CDC data.  Either edit the
                CDC information directly or delete the entire category if it is no longer required.</strong>
        <?php else: ?>
            <?php if(isset($flashCardList) && is_array($flashCardList) && !empty($flashCardList)): ?>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $('#selectAll').click(function(event) {  //on click
                            if(this.checked) { // check select status
                                $('.deleteFlashCardCheckbox').each(function() { //loop through each checkbox
                                    this.checked = true;  //select all checkboxes with class "checkbox1"
                                });
                            }else{
                                $('.deleteFlashCardCheckbox').each(function() { //loop through each checkbox
                                    this.checked = false; //deselect all checkboxes with class "checkbox1"
                                });
                            }
                        });

                    });
                </script>
                <form action="/admin/card-data/<?php echo $workingChild; ?>/delete" method="POST">
                    <input type="hidden" name="confirmDeleteMultipleFlashCards" value="1">
                    <p>
                        Note: This data is truncated to fit in the table.  Select the flash cards you would like to delete and click "Delete Selected".
                    </p>
                    <input type="submit" value="Delete Selected">
                    <table>
                        <thead>
                        <tr>
                            <th><input type="checkbox" name="selectAll" id="selectAll"></th>
                            <th>Card Front</th>
                            <th>Card Back</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($flashCardList as $flashCardRow): ?>
                            <?php if($flashCardManager->loadFlashCardData($flashCardRow['uuid'])): ?>
                                <tr>
                                    <td><input type="checkbox" class="deleteFlashCardCheckbox" name="flashCardUUIDList[]" value="<?php echo $flashCardRow['uuid']; ?>"></td>
                                    <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getFrontText(),100); ?></td>
                                    <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getBackText(),100); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">Data could not be retrieved.</td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="submit" value="Delete Selected">
                </form>
            <?php else: ?>
                <div class="clearfix">&nbsp;</div>
                There are no flash cards for this category.
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>
