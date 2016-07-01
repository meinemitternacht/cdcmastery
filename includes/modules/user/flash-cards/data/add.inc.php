<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 2:03 PM
 */

if(isset($_POST['confirmFlashCardAdd'])){
    $cardFrontText = isset($_POST['cardFrontText']) ? $_POST['cardFrontText'] : false;
    $cardBackText = isset($_POST['cardBackText']) ? $_POST['cardBackText'] : false;

    $addError = false;

    if(!$cardFrontText){
        $systemMessages->addMessage("The front text of the card cannot be empty.", "warning");
        $addError = true;
    }

    if(!$cardBackText){
        $systemMessages->addMessage("The back text of the card cannot be empty.", "warning");
        $addError = true;
    }

    if(!$addError) {
        $flashCardManager->newFlashCard();
        $flashCardManager->setFrontText(nl2br($cardFrontText));
        $flashCardManager->setBackText(nl2br($cardBackText));
        $flashCardManager->setCardCategory($flashCardManager->getCategoryUUID());

        if($flashCardManager->saveFlashCardData()){
            $systemMessages->addMessage("Flash card added successfully.", "success");

            $systemLog->setAction("FLASH_CARD_ADD");
            $systemLog->setDetail("Card UUID", $flashCardManager->getCardUUID());
            $systemLog->setDetail("Card Category", $flashCardManager->getCategoryUUID());
            $systemLog->saveEntry();

            $_SESSION['previousFlashCardFront'] = $cardFrontText;
            $_SESSION['previousFlashCardBack'] = $cardBackText;

            unset($cardFrontText);
            unset($cardBackText);
        }
        else{
            $systemMessages->addMessage($flashCardManager->error, "danger");
            $systemMessages->addMessage("The flash card could not be added.  Contact the support help desk for assistance.", "danger");
        }
    }
}
?>
<section>
    <header>
        <h2>Add Flash Card to <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <form action="/cards/data/<?php echo $workingChild; ?>/add" method="POST">
        <input type="hidden" name="confirmFlashCardAdd" value="1">
        <p>
            Enter the details of the flash card below.
        </p>
            <?php if(isset($_SESSION['previousFlashCardFront']) && isset($_SESSION['previousFlashCardBack'])): ?>
            <blockquote>
                Last flash card entered for your session:<br>
                <br>
                <strong>Front:</strong> <?php echo $_SESSION['previousFlashCardFront']; ?><br>
                <strong>Back:</strong> <?php echo $_SESSION['previousFlashCardBack']; ?>
            </blockquote>
            <?php endif; ?>
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
        <input type="submit" value="Add Flash Card">
    </form>
</section>