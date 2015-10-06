<?php
/*
 * AJAX entry point for the testing platform.
 * URL Structure:  /ajax/testPlatform/<test_uuid>/
 * Data Structure: /ajax/testPlatform/$_SESSION['vars'][0]
 * 
 * Data is requested and sent through POST method.
 */

$flashCardManager = new flashCardManager($db, $log);

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
                    $flashCardManager->setCurrentCard($actionData);
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
                    $log->setAction("AJAX_ACTION_ERROR");
                    $log->setDetail("CALLING SCRIPT","/ajax/flashCardPlatform");
                    $log->setDetail("Category UUID",$categoryUUID);
                    $log->setDetail("User Action",$userAction);

                    if(isset($actionData))
                        $log->setDetail("Action Data",$actionData);

                    $log->saveEntry();
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
    $log->setAction("AJAX_DIRECT_ACCESS");
    $log->setDetail("CALLING SCRIPT","/ajax/flashCardPlatform");
    $log->saveEntry();

    $sysMsg->addMessage("Direct access to that script is not authorized.");
    $cdcMastery->redirect("/errors/403");
}