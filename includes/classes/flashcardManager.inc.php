<?php

/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/30/2015
 * Time: 9:31 AM
 */
class flashCardManager extends CDCMastery
{
    protected $db;
    protected $log;

    public $error;

    public $categoryUUID;
    public $categoryName;
    public $categoryEncrypted;
    public $categoryType;
    public $categoryBinding;
    public $categoryPrivate;
    public $categoryCreatedBy;
    public $categoryComments;

    public $categoryList;

    public $cardUUID;
    public $frontText;
    public $backText;
    public $cardCategory;

    public $flashCardArray;
    public $currentCard;
    public $totalCards;
    public $cardCurrentState;

    public function __construct(mysqli $db, log $log){
        $this->db = $db;
        $this->log = $log;
    }

    public function newFlashCardCategory(){
        $this->categoryUUID = $this->genUUID();
        $this->categoryName = NULL;
        $this->categoryEncrypted = NULL;
        $this->categoryType = NULL;
        $this->categoryBinding = NULL;
        $this->categoryPrivate = NULL;
        $this->categoryCreatedBy = NULL;

        return true;
    }

    public function listCardCategories($globalOnly=false){
        if($globalOnly) {
            $stmt = $this->db->prepare("SELECT  uuid,
                                            categoryName,
                                            categoryEncrypted,
                                            categoryType,
                                            categoryBinding,
                                            categoryPrivate,
                                            categoryCreatedBy,
                                            categoryComments
                                        FROM flashCardCategories
                                        WHERE categoryType != ?
                                        ORDER BY categoryType, categoryName ASC");
            $paramValue = "private";
            $stmt->bind_param("s",$paramValue);
        }
        else{
            $stmt = $this->db->prepare("SELECT  uuid,
                                            categoryName,
                                            categoryEncrypted,
                                            categoryType,
                                            categoryBinding,
                                            categoryPrivate,
                                            categoryCreatedBy,
                                            categoryComments
                                        FROM flashCardCategories
                                        ORDER BY categoryName ASC");
        }

        if($stmt->execute()){
            $stmt->bind_result($uuid,$categoryName,$categoryEncrypted,$categoryType,$categoryBinding,$categoryPrivate,$categoryCreatedBy,$categoryComments);
            while($stmt->fetch()){
                $this->categoryList[$uuid]['categoryName'] = $categoryName;
                $this->categoryList[$uuid]['categoryEncrypted'] = $categoryEncrypted;
                $this->categoryList[$uuid]['categoryType'] = $categoryType;
                $this->categoryList[$uuid]['categoryBinding'] = $categoryBinding;
                $this->categoryList[$uuid]['categoryPrivate'] = $categoryPrivate;
                $this->categoryList[$uuid]['categoryCreatedBy'] = $categoryCreatedBy;
                $this->categoryList[$uuid]['categoryComments'] = $categoryComments;
            }

            $stmt->close();

            if(is_array($this->categoryList) && !empty($this->categoryList)){
                return $this->categoryList;
            }
            else{
                $this->error = "There are no categories.";
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_LIST");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Calling Function","flashCardManager->listCardCategories()");
            $this->log->saveEntry();

            $stmt->close();

            return false;
        }
    }

    public function loadCardCategory($categoryUUID){
        $stmt = $this->db->prepare("SELECT  uuid,
                                            categoryName,
                                            categoryEncrypted,
                                            categoryType,
                                            categoryBinding,
                                            categoryPrivate,
                                            categoryCreatedBy,
                                            categoryComments
                                        FROM flashCardCategories
                                        WHERE uuid = ?");
        $stmt->bind_param("s",$categoryUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid,$categoryName,$categoryEncrypted,$categoryType,$categoryBinding,$categoryPrivate,$categoryCreatedBy,$categoryComments);
            $stmt->fetch();
            $stmt->close();

            if(!empty($uuid)){
                $this->categoryUUID = $uuid;
                $this->categoryName = $categoryName;
                $this->categoryEncrypted = $categoryEncrypted;
                $this->categoryType = $categoryType;
                $this->categoryBinding = $categoryBinding;
                $this->categoryPrivate = $categoryPrivate;
                $this->categoryCreatedBy = $categoryCreatedBy;
                $this->categoryComments = $categoryComments;

                return true;
            }
            else{
                return false;
            }
        }
        else{
            $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_LOAD");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Calling Function","flashCardManager->loadCardCategory()");
            $this->log->setDetail("Category UUID",$categoryUUID);
            $this->log->saveEntry();

            $stmt->close();

            return false;
        }
    }

    public function saveFlashCardCategory(){
        if(!empty($this->categoryUUID)){
            $stmt = $this->db->prepare("INSERT INTO flashCardCategories
                                                    (uuid,
                                                    categoryName,
                                                    categoryEncrypted,
                                                    categoryType,
                                                    categoryBinding,
                                                    categoryPrivate,
                                                    categoryCreatedBy,
                                                    categoryComments)
                                                VALUES
                                                    (?,?,?,?,?,?,?,?)
                                                ON DUPLICATE KEY UPDATE
                                                    uuid=VALUES(uuid),
                                                    categoryName=VALUES(categoryName),
                                                    categoryEncrypted=VALUES(categoryEncrypted),
                                                    categoryType=VALUES(categoryType),
                                                    categoryBinding=VALUES(categoryBinding),
                                                    categoryPrivate=VALUES(categoryPrivate),
                                                    categoryCreatedBy=VALUES(categoryCreatedBy),
                                                    categoryComments=VALUES(categoryComments)");

            $stmt->bind_param("ssississ",   $this->categoryUUID,
                                            $this->categoryName,
                                            $this->categoryEncrypted,
                                            $this->categoryType,
                                            $this->categoryBinding,
                                            $this->categoryPrivate,
                                            $this->categoryCreatedBy,
                                            $this->categoryComments);

            if($stmt->execute()){
                $stmt->close();
                return true;
            }
            else{
                $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_SAVE");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->setDetail("Calling Function","flashCardManager->saveCardCategory()");
                $this->log->setDetail("Category UUID",$this->categoryUUID);
                $this->log->setDetail("Category Name",$this->categoryName);
                $this->log->setDetail("Category Encrypted",$this->categoryEncrypted);
                $this->log->setDetail("Category Type",$this->categoryType);
                $this->log->setDetail("Category Binding",$this->categoryBinding);
                $this->log->setDetail("Category Private",$this->categoryPrivate);
                $this->log->setDetail("Category Created By",$this->categoryCreatedBy);
                $this->log->setDetail("Category Comments",$this->categoryComments);
                $this->log->saveEntry();
                $stmt->close();
                return false;
            }
        }
        else{
            $this->error = "Category UUID is empty.";
            return false;
        }
    }

    public function deleteFlashCardCategory($categoryUUID){
        if($this->loadCardCategory($categoryUUID)) {
            $stmt = $this->db->prepare("DELETE FROM flashCardCategories WHERE uuid = ?");
            $stmt->bind_param("s", $categoryUUID);

            if ($stmt->execute()) {
                $this->log->setAction("FLASH_CARD_CATEGORY_DELETE");
                $this->log->setDetail("Category UUID", $this->categoryUUID);
                $this->log->setDetail("Category Name", $this->categoryName);
                $this->log->setDetail("Category Type", $this->categoryType);
                $this->log->setDetail("Category Binding", $this->categoryBinding);
                $this->log->saveEntry();
                $stmt->close();

                $stmt = $this->db->prepare("DELETE FROM flashCardData WHERE cardCategory = ?");
                $stmt->bind_param("s",$categoryUUID);

                if($stmt->execute()){
                    $this->log->setAction("FLASH_CARD_DATA_DELETE");
                    $this->log->setDetail("Category UUID", $this->categoryUUID);
                    $this->log->saveEntry();
                    $stmt->close();

                    return true;
                }
                else{
                    $this->log->setAction("ERROR_FLASH_CARD_DATA_DELETE");
                    $this->log->setDetail("MySQL Error", $stmt->error);
                    $this->log->setDetail("Calling Function", "flashCardManager->deleteFlashCardCategory()");
                    $this->log->setDetail("Category UUID", $categoryUUID);
                    $this->log->saveEntry();
                    $stmt->close();

                    return false;
                }
            } else {
                $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_DELETE");
                $this->log->setDetail("MySQL Error", $stmt->error);
                $this->log->setDetail("Calling Function", "flashCardManager->deleteFlashCardCategory()");
                $this->log->setDetail("Category UUID", $categoryUUID);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }
        else{
            $this->error = "That category does not exist.";
            $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_DELETE");
            $this->log->setDetail("Error", $this->error);
            $this->log->setDetail("Calling Function", "flashCardManager->deleteFlashCardCategory()");
            $this->log->setDetail("Category UUID", $categoryUUID);
            $this->log->saveEntry();

            return false;
        }
    }

    public function createCategoryFromAFSC($afscUUID,afsc $afsc,$categoryCreatedBy){
        $this->setCategoryCreatedBy($categoryCreatedBy);

        if($afsc->loadAFSC($afscUUID)){
            $this->setCategoryEncrypted($afsc->getAFSCFOUO());
            $this->setCategoryName($afsc->getAFSCName());

            $this->setCategoryPrivate(false);
            $this->setCategoryBinding($afscUUID);
            $this->setCategoryType("afsc");

            $this->setCategoryComments($afsc->getAFSCVersion());

            if($this->saveFlashCardCategory()){
                return true;
            }
            else{
                return false;
            }
        }
    }

    public function checkCategoryBinding($categoryBinding){
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM flashCardCategories WHERE categoryBinding = ?");
        $stmt->bind_param("s",$categoryBinding);

        if($stmt->execute()){
            $stmt->bind_result($count);
            $stmt->fetch();

            if($count > 0){
                return false;
            }
            else{
                return true;
            }
        }
        else{
            $this->error = "Could not check category binding.";
            $this->log->setAction("ERROR_FLASH_CARD_CHECK_CATEGORY_BINDING");
            $this->log->setDetail("Error", $this->error);
            $this->log->setDetail("MySQL Error", $stmt->error);
            $this->log->setDetail("Calling Function", "flashCardManager->checkCategoryBinding()");
            $this->log->setDetail("Category Binding", $categoryBinding);
            $this->log->saveEntry();

            return true;
        }
    }

    public function getCardCount($categoryUUID){
        if(!$this->loadCardCategory($categoryUUID)){
            $this->error = "That card category does not exist.";
            return false;
        }
        else{
            if($this->categoryType == "afsc"){
                $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM questionData WHERE afscUUID = ?");
                $stmt->bind_param("s",$this->categoryBinding);
            }
            else{
                $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM flashCardData WHERE cardCategory = ?");
                $stmt->bind_param("s",$categoryUUID);
            }

            if($stmt->execute()){
                $stmt->bind_result($count);
                $stmt->fetch();

                return $count;
            }
            else{
                $this->log->setAction("ERROR_FLASH_CARD_COUNT_CARDS");
                $this->log->setDetail("MySQL Error", $stmt->error);
                $this->log->setDetail("Calling Function", "flashCardManager->getCardCount()");
                $this->log->setDetail("Category UUID", $categoryUUID);
                $this->log->saveEntry();

                return false;
            }
        }
    }

    public function newFlashCard(){
        $this->cardUUID = $this->genUUID();
        $this->frontText = NULL;
        $this->backText = NULL;
        $this->cardCategory = NULL;

        return true;
    }

    public function loadAFSCFlashCardData($cardUUID){
        $afsc = new afsc($this->db,$this->log);
        $answerManager = new answerManager($this->db,$this->log);
        $questionManager = new questionManager($this->db,$this->log,$afsc,$answerManager);

        if($this->categoryEncrypted){
            $answerManager->setFOUO(true);
            $questionManager->setFOUO(true);
        }

        if($questionManager->loadQuestion($cardUUID)){
            $this->setFrontText($questionManager->getQuestionText());
            $correctAnswerUUID = $answerManager->getCorrectAnswer($cardUUID);

            if($correctAnswerUUID){
                if($answerManager->loadAnswer($correctAnswerUUID)){
                    $this->setBackText($answerManager->getAnswerText());
                    return true;
                }
                else{
                    $this->error = "Could not load the answer.";
                    return false;
                }
            }
            else{
                $this->error = "Answer UUID was not defined.";
                return false;
            }
        }
        else{
            $this->error = "Could not load the question.";
            return false;
        }
    }

    public function loadFlashCardData($cardUUID){
        $stmt = $this->db->prepare("SELECT  uuid,
                                            frontText,
                                            backText,
                                            cardCategory
                                        FROM flashCardData
                                        WHERE uuid = ?");

        $stmt->bind_param("s",$cardUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid,$frontText,$backText,$cardCategory);
            $stmt->fetch();
            $stmt->close();
            if(!empty($uuid)){
                $this->cardUUID = $uuid;
                $this->frontText = $frontText;
                $this->backText = $backText;
                $this->cardCategory = $cardCategory;

                return true;
            }
            else{
                $this->error = "No data returned.";
                return false;
            }
        }
        else{
            $this->log->setAction("ERROR_FLASH_CARD_LOAD");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Calling Function","flashCardManager->loadFlashCardData()");
            $this->log->setDetail("Card UUID",$this->cardUUID);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    public function saveFlashCardData(){
        if(!empty($this->cardUUID)){
            $stmt = $this->db->prepare("INSERT INTO flashCardData
                                                    (uuid,
                                                    frontText,
                                                    backText,
                                                    cardCategory)
                                                VALUES
                                                    (?,?,?,?)
                                                ON DUPLICATE KEY UPDATE
                                                  uuid=VALUES(uuid),
                                                  frontText=VALUES(frontText),
                                                  backText=VALUES(backText),
                                                  cardCategory=VALUES(cardCategory)");

            $stmt->bind_param("ssss",$this->cardUUID,$this->frontText,$this->backText,$this->cardCategory);

            if($stmt->execute()){
                $stmt->close();
                return true;
            }
            else{
                $this->log->setAction("ERROR_FLASH_CARD_SAVE");
                $this->log->setDetail("MySQL Error",$stmt->error);
                $this->log->setDetail("Calling Function","flashCardManager->saveFlashCardData()");
                $this->log->setDetail("Card UUID",$this->cardUUID);
                $this->log->setDetail("Front Text",$this->frontText);
                $this->log->setDetail("Back Text",$this->backText);
                $this->log->setDetail("Card Category",$this->cardCategory);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }
        else{
            $this->error = "Card UUID is empty.";
            return false;
        }
    }

    public function deleteFlashCardData($cardUUID){
        if($this->loadFlashCardData($cardUUID)) {
            $stmt = $this->db->prepare("DELETE FROM flashCardData WHERE uuid = ?");
            $stmt->bind_param("s", $cardUUID);

            if ($stmt->execute()) {
                return true;
            } else {
                $this->log->setAction("ERROR_FLASH_CARD_DELETE");
                $this->log->setDetail("MySQL Error", $stmt->error);
                $this->log->setDetail("Calling Function", "flashCardManager->deleteFlashCardData()");
                $this->log->setDetail("Card UUID", $cardUUID);
                $this->log->saveEntry();
                $stmt->close();

                return false;
            }
        }
        else{
            $this->error = "That flash card does not exist.";
            $this->log->setAction("ERROR_FLASH_CARD_DELETE");
            $this->log->setDetail("Error", $this->error);
            $this->log->setDetail("Calling Function", "flashCardManager->deleteFlashCardData()");
            $this->log->setDetail("Card UUID", $cardUUID);
            $this->log->saveEntry();

            return false;
        }
    }

    public function listFlashCards($categoryUUID=false,$uuidOnly=false){
        if(!$categoryUUID && empty($this->categoryUUID)){
            $this->error = "No category loaded.";
            return false;
        }
        elseif($categoryUUID && !$this->loadCardCategory($categoryUUID)){
            $this->error = "That card category could not be loaded.";
            return false;
        }
        else{
            if($uuidOnly) {
                if($this->categoryType == "afsc"){
                    $afsc = new afsc($this->db,$this->log);
                    $answerManager = new answerManager($this->db,$this->log);
                    $questionManager = new questionManager($this->db,$this->log,$afsc,$answerManager);
                    $questionManager->setAFSCUUID($this->categoryBinding);
                    $questionManager->setFOUO($this->categoryEncrypted);

                    $questionList = $questionManager->listQuestionsForAFSC();
                    $i=1;
                    foreach($questionList as $questionUUID){
                        $this->flashCardArray[$i] = $questionUUID;
                        $i++;
                    }

                    if(is_array($this->flashCardArray) && !empty($this->flashCardArray)){
                        return true;
                    }
                    else{
                        $this->error = "No flash cards for that AFSC found.";
                        return false;
                    }
                }
                else {
                    $stmt = $this->db->prepare("SELECT uuid FROM flashCardData WHERE cardCategory = ?");
                    $stmt->bind_param("s", $this->categoryUUID);

                    if ($stmt->execute()) {
                        $stmt->bind_result($uuid);
                        while ($stmt->fetch()) {
                            $this->flashCardArray[] = $uuid;
                        }

                        $stmt->close();

                        if (is_array($this->flashCardArray) && count($this->flashCardArray) > 0) {
                            return true;
                        } else {
                            $this->error = "No flash cards found.";
                            return false;
                        }
                    } else {
                        $this->error = "Could not list flash cards.";
                        $this->log->setAction("ERROR_FLASH_CARD_LIST");
                        $this->log->setDetail("MySQL Error", $stmt->error);
                        $this->log->setDetail("Calling Function", "flashCardManager->listFlashCards()");
                        $this->log->setDetail("Category UUID", $categoryUUID);
                        $this->log->setDetail("UUID Only", $uuidOnly);
                        $this->log->saveEntry();
                        $stmt->close();

                        return false;
                    }
                }
            }
            else{
                $stmt = $this->db->prepare("SELECT  uuid,
                                                    frontText,
                                                    backText,
                                                    cardCategory
                                                FROM flashCardData
                                                WHERE cardCategory = ?");
                $stmt->bind_param("s",$this->categoryUUID);

                if($stmt->execute()){
                    $stmt->bind_result($uuid,$frontText,$backText,$cardCategory);
                    $i=1;
                    while($stmt->fetch()){
                        $this->flashCardArray[$i]['uuid'] = $uuid;
                        $this->flashCardArray[$i]['frontText'] = $frontText;
                        $this->flashCardArray[$i]['backText'] = $backText;
                        $this->flashCardArray[$i]['cardCategory'] = $cardCategory;
                        $i++;
                    }

                    $stmt->close();

                    if(is_array($this->flashCardArray) && count($this->flashCardArray) > 0){
                        return true;
                    }
                    else{
                        $this->error = "No flash cards found.";
                        return false;
                    }
                }
                else{
                    $this->error = "Could not list flash cards.";
                    $this->log->setAction("ERROR_FLASH_CARD_LIST");
                    $this->log->setDetail("MySQL Error", $stmt->error);
                    $this->log->setDetail("Calling Function", "flashCardManager->listFlashCards()");
                    $this->log->setDetail("Category UUID", $categoryUUID);
                    $this->log->setDetail("UUID Only", $uuidOnly);
                    $this->log->saveEntry();
                    $stmt->close();

                    return false;
                }
            }
        }
    }

    public function shuffleFlashCards(){
        if(is_array($this->flashCardArray) && count($this->flashCardArray) > 0){
            return shuffle($this->flashCardArray);
        }
        else{
            $this->error = "No flash cards loaded.";
            return false;
        }
    }

    public function countFlashCards(){
        if(is_array($this->flashCardArray) && count($this->flashCardArray) > 0){
            return count($this->flashCardArray);
        }
        else{
            $this->error = "No flash cards loaded.";
            return false;
        }
    }

    public function saveSession(){
        if(is_array($this->flashCardArray) && count($this->flashCardArray) > 0){
            $_SESSION['flashCardStorage']['cardArray'] = serialize($this->flashCardArray);
            $_SESSION['flashCardStorage']['currentCard'] = $this->currentCard;
            $_SESSION['flashCardStorage']['totalCards'] = $this->totalCards;
            $_SESSION['flashCardStorage']['cardCurrentState'] = $this->cardCurrentState;
        }

        return true;
    }

    public function loadSession(){
        if(isset($_SESSION['flashCardStorage']) && !empty($_SESSION['flashCardStorage'])){
            $this->flashCardArray = unserialize($_SESSION['flashCardStorage']['cardArray']);
            $this->currentCard = $_SESSION['flashCardStorage']['currentCard'];
            $this->totalCards = count($this->flashCardArray);
            $this->cardCurrentState = $_SESSION['flashCardStorage']['cardCurrentState'];
            return true;
        }
        else{
            return false;
        }
    }

    public function clearSession(){
        $this->flashCardArray = Array();

        if(isset($_SESSION['flashCardStorage']))
            unset($_SESSION['flashCardStorage']);

        $this->currentCard = 1;

        if($this->cardCurrentState == "back"){
            $this->flipCard();
        }

        return true;
    }

    public function navigateFirstCard(){
        $this->currentCard = 1;

        if($this->cardCurrentState == "back"){
            $this->flipCard();
        }

        return true;
    }

    public function navigatePreviousCard(){
        if($this->currentCard > 1){
            $this->currentCard--;
        }
        else{
            $this->currentCard = 1;
        }

        if($this->cardCurrentState == "back"){
            $this->flipCard();
        }

        return true;
    }

    public function navigateNextCard(){
        if($this->currentCard < $this->totalCards){
            $this->currentCard++;
        }
        else{
            $this->currentCard = $this->totalCards;
        }

        if($this->cardCurrentState == "back"){
            $this->flipCard();
        }

        return true;
    }

    public function navigateLastCard(){
        $this->currentCard = $this->totalCards;
        $this->cardCurrentState = "front";
        return true;
    }

    public function setCurrentCard($currentCard){
        if(is_int($currentCard)){
            if($currentCard > $this->totalCards){
                $this->currentCard = $this->totalCards;
            }
            elseif($currentCard < 1){
                $this->currentCard = 1;
            }
            else{
                $this->currentCard = $currentCard;
            }

            return true;
        }
        else{
            return false;
        }
    }

    public function flipCard(){
        if($this->cardCurrentState == "front"){
            $this->cardCurrentState = "back";
            $_SESSION['flashCardStorage']['cardCurrentState'] = "back";
        }
        else{
            $this->cardCurrentState = "front";
            $_SESSION['flashCardStorage']['cardCurrentState'] = "front";
        }

        return true;
    }

    public function renderFlashCard(){
        if(!isset($this->flashCardArray) || !is_array($this->flashCardArray)){
            $output = "No flash card data is available.";
        }
        elseif(!$this->currentCard){
            $output = "Cannot determine current card.";
        }
        elseif(!$this->totalCards){
            $output = "Cannot determine total number of cards.";
        }
        elseif(!isset($this->flashCardArray[$this->currentCard])){
            $output = "Current card does not exist in the flash card array.";
        }
        else{
            if($this->categoryType == "afsc") {
                if (!$this->loadAFSCFlashCardData($this->flashCardArray[$this->currentCard])) {
                    $output = "We could not load that flash card.";
                } else {
                    if ($this->cardCurrentState == "front") {
                        $output = '<div id="cardFront" class="cardData">';
                        $output .= '<span class="text-success">card front</span><br>';
                        $output .= $this->getFrontText();
                        $output .= '</div>';
                    } else {
                        $output = '<div id="cardBack" class="cardData">';
                        $output .= '<span class="text-caution">card back</span><br>';
                        $output .= $this->getBackText();
                        $output .= '</div>';
                    }
                }
            }
            else{
                if (!$this->loadFlashCardData($this->flashCardArray[$this->currentCard]['uuid'])) {
                    $output = "We could not load that flash card.";
                } else {
                    if ($this->cardCurrentState == "front") {
                        $output = '<div id="cardFront" class="cardData">';
                        $output .= '<span class="text-success">card front</span><br>';
                        $output .= $this->getFrontText();
                        $output .= '</div>';
                    } else {
                        $output = '<div id="cardBack" class="cardData">';
                        $output .= '<span class="text-caution">card back</span><br>';
                        $output .= $this->getBackText();
                        $output .= '</div>';
                    }
                }
            }
        }

        /*
         * Debugging
         */
        /*
        $output .= "Current Card: ".$this->currentCard;
        $output .= "<br>";
        $output .= "Total Cards: ".$this->totalCards;
        $output .= "<br>";
        $output .= "Current Card State: ".$this->cardCurrentState;
        $output .= "<br>";
        */

        return $output;
    }

    public function getProgress(){
        if(($this->currentCard > 0) && ($this->totalCards > 0)) {
            /*
             * Progress Bar
             */
            $output = '<script>
                    $(function() {
                        $( "#progressbar" ).progressbar({
                          value: ' . round(($this->currentCard/$this->totalCards)*100) . ', max: 100
                        });
                    });
                    </script>
                    <div class="clearfix">&nbsp;</div>
                    <div id="progressbar" style="height: 0.5em; margin-bottom: 1em;"></div>';

            return $output;
        }
        else{
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getCategoryUUID()
    {
        return $this->categoryUUID;
    }

    /**
     * @param mixed $categoryUUID
     */
    public function setCategoryUUID($categoryUUID)
    {
        $this->categoryUUID = $categoryUUID;
    }

    /**
     * @return mixed
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @param mixed $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return mixed
     */
    public function getCategoryEncrypted()
    {
        return $this->categoryEncrypted;
    }

    /**
     * @param mixed $categoryEncrypted
     */
    public function setCategoryEncrypted($categoryEncrypted)
    {
        $this->categoryEncrypted = $categoryEncrypted;
    }

    /**
     * @return mixed
     */
    public function getCategoryType()
    {
        return $this->categoryType;
    }

    /**
     * @param mixed $categoryType
     */
    public function setCategoryType($categoryType)
    {
        $this->categoryType = $categoryType;
    }

    /**
     * @return mixed
     */
    public function getCategoryBinding()
    {
        return $this->categoryBinding;
    }

    /**
     * @param mixed $categoryBinding
     */
    public function setCategoryBinding($categoryBinding)
    {
        $this->categoryBinding = $categoryBinding;
    }

    /**
     * @return mixed
     */
    public function getCategoryPrivate()
    {
        return $this->categoryPrivate;
    }

    /**
     * @param mixed $categoryPrivate
     */
    public function setCategoryPrivate($categoryPrivate)
    {
        $this->categoryPrivate = $categoryPrivate;
    }

    /**
     * @return mixed
     */
    public function getCategoryCreatedBy()
    {
        return $this->categoryCreatedBy;
    }

    /**
     * @param mixed $categoryCreatedBy
     */
    public function setCategoryCreatedBy($categoryCreatedBy)
    {
        $this->categoryCreatedBy = $categoryCreatedBy;
    }

    /**
     * @return mixed
     */
    public function getCategoryComments()
    {
        return $this->categoryComments;
    }

    /**
     * @param mixed $categoryComments
     */
    public function setCategoryComments($categoryComments)
    {
        $this->categoryComments = $categoryComments;
    }

    /**
     * @return mixed
     */
    public function getCardUUID()
    {
        return $this->cardUUID;
    }

    /**
     * @param mixed $cardUUID
     */
    public function setCardUUID($cardUUID)
    {
        $this->cardUUID = $cardUUID;
    }

    /**
     * @return mixed
     */
    public function getFrontText()
    {
        return $this->frontText;
    }

    /**
     * @param mixed $frontText
     */
    public function setFrontText($frontText)
    {
        $this->frontText = $frontText;
    }

    /**
     * @return mixed
     */
    public function getBackText()
    {
        return $this->backText;
    }

    /**
     * @param mixed $backText
     */
    public function setBackText($backText)
    {
        $this->backText = $backText;
    }

    /**
     * @return mixed
     */
    public function getCardCategory()
    {
        return $this->cardCategory;
    }

    /**
     * @param mixed $cardCategory
     */
    public function setCardCategory($cardCategory)
    {
        $this->cardCategory = $cardCategory;
    }



    public function __destruct(){
        parent::__destruct();
    }
}