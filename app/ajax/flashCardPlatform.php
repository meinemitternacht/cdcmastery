<?php
/*
 * AJAX entry point for the testing platform.
 * URL Structure:  /ajax/testPlatform/<test_uuid>/
 * Data Structure: /ajax/testPlatform/$_SESSION['vars'][0]
 * 
 * Data is requested and sent through POST method.
 */

$flashCardManager = new CDCMastery\FlashCardManager($db, $systemLog);

if(isset($_SESSION['vars'][0]))
    $categoryUUID = $_SESSION['vars'][0];

if(isset($_POST['action']))
    $userAction = $_POST['action'];

if(isset($_POST['actionData']))
    $actionData = $_POST['actionData'];

if(isset($categoryUUID)){
    if($flashCardManager->loadCardCategory($categoryUUID)){
        $flashCardManager->loadSession();
        if(isset($userAction)){
            switch($userAction){
                case "firstCard":
                    $flashCardManager->navigateFirstCard();
                    break;
                case "previousCard":
                    $flashCardManager->navigatePreviousCard();
                    break;
                case "nextCard":
                    $flashCardManager->navigateNextCard();
                    break;
                case "lastCard":
                    $flashCardManager->navigateLastCard();
                    break;
                case "loadCard":
                    if(isset($actionData)){
                        $flashCardManager->setCurrentCard($actionData);
                    }
                    break;
                case "flipCard":
                    $flashCardManager->flipCard();
                    break;
                case "shuffleCards":
                    $flashCardManager->shuffleFlashCards();
                    $flashCardManager->setCurrentCard(1);
                    break;
                case "getProgress":
                    echo $flashCardManager->getProgress();
                    $skipData = true;
                    break;
                default:
                    $systemLog->setAction("AJAX_ACTION_ERROR");
                    $systemLog->setDetail("CALLING SCRIPT", "/ajax/flashCardPlatform");
                    $systemLog->setDetail("Category UUID", $categoryUUID);
                    $systemLog->setDetail("User Action", $userAction);

                    if(isset($actionData))
                        $systemLog->setDetail("Action Data", $actionData);

                    $systemLog->saveEntry();
                    break;
            }
        }

        if(!isset($skipData)) {
            echo $flashCardManager->renderFlashCard();
        }
        $flashCardManager->saveSession();
    }
    else{
        echo "That flash card category does not exist.";
    }
}
else{
    $systemLog->setAction("AJAX_DIRECT_ACCESS");
    $systemLog->setDetail("CALLING SCRIPT", "/ajax/flashCardPlatform");
    $systemLog->saveEntry();

    $systemMessages->addMessage("Direct access to this script is not authorized.", "danger");
    $cdcMastery->redirect("/errors/403");
}