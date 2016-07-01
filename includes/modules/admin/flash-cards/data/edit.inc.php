<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 2:03 PM
 */
if(!isset($actionChild)){
    $systemMessages->addMessage("You must specify a flash card to edit.", "warning");
    $cdcMastery->redirect("/admin/card-data/".$workingChild);
}
elseif(!$flashCardManager->loadFlashCardData($actionChild)){
    $systemMessages->addMessage("That flash card does not exist.", "warning");
    $cdcMastery->redirect("/admin/card-data/".$workingChild);
}

if(isset($_POST['confirmFlashCardEdit'])){
    $cardFrontText = isset($_POST['cardFrontText']) ? $_POST['cardFrontText'] : false;
    $cardBackText = isset($_POST['cardBackText']) ? $_POST['cardBackText'] : false;

    $editError = false;

    if(!$cardFrontText){
        $systemMessages->addMessage("The front text of the card cannot be empty.", "warning");
        $editError = true;
    }

    if(!$cardBackText){
        $systemMessages->addMessage("The back text of the card cannot be empty.", "warning");
        $editError = true;
    }

    if(!$editError) {
        if(strpos($cardFrontText,"<br>") !== false){
            $flashCardManager->setFrontText($cardFrontText);
        }
        else{
            $flashCardManager->setFrontText(nl2br($cardFrontText));
        }

        if(strpos($cardBackText,"<br>") !== false){
            $flashCardManager->setBackText($cardBackText);
        }
        else{
            $flashCardManager->setBackText(nl2br($cardBackText));
        }

        if($flashCardManager->saveFlashCardData()){
            $systemMessages->addMessage("Flash card edited successfully.", "success");

            $systemLog->setAction("FLASH_CARD_EDIT");
            $systemLog->setDetail("Card UUID", $flashCardManager->getCardUUID());
            $systemLog->setDetail("Card Category", $flashCardManager->getCategoryUUID());
            $systemLog->saveEntry();

            unset($cardFrontText);
            unset($cardBackText);
        }
        else{
            $systemMessages->addMessage($flashCardManager->error, "danger");
            $systemMessages->addMessage("The flash card could not be edited.  Contact the support help desk for assistance.", "danger");
        }
    }
}

if(!isset($cardFrontText)){
    $cardFrontText = $flashCardManager->getFrontText();
}

if(!isset($cardBackText)){
    $cardBackText = $flashCardManager->getBackText();
}
?>
<section>
    <header>
        <h2>Edit Flash Card</h2>
    </header>
    <form action="/admin/card-data/<?php echo $workingChild; ?>/edit/<?php echo $actionChild; ?>" method="POST">
        <input type="hidden" name="confirmFlashCardEdit" value="1">
        <p>
            Edit the details of the flash card below.
        </p>
        <ul class="form-field-list">
            <li>
                <label for="cardFrontText">Flash Card Front</label>
                <p>
                    Enter text to show on the front of the flash card.  This is usually a question or term.
                </p>
                <textarea class="input_full" name="cardFrontText" id="cardFrontText" style="height:6em;"><?php if(isset($cardFrontText)): echo $cardFrontText; endif; ?></textarea>
            </li>
            <li>
                <label for="cardBackText">Flash Card Back</label>
                <p>
                    Enter text to show on the back of the flash card.  This is usually the answer to the question or definition of a term.
                </p>
                <textarea class="input_full" name="cardBackText" id="cardBackText" style="height:6em;"><?php if(isset($cardBackText)): echo $cardBackText; endif; ?></textarea>
            </li>
        </ul>
        <input type="submit" value="Edit Flash Card">
    </form>
</section>