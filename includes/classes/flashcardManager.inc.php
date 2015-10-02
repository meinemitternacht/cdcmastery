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

    public $cardUUID;
    public $frontText;
    public $backText;
    public $cardCategory;

    public function __construct(mysqli $db, log $log){
        $this->db = $db;
        $this->log = $log;
    }

    public function loadCardCategory($categoryUUID){
        $stmt = $this->db->prepare("SELECT  uuid,
                                            categoryName,
                                            categoryEncrypted,
                                            categoryType,
                                            categoryBinding
                                        FROM flashCardCategories
                                        WHERE uuid = ?");
        $stmt->bind_param("s",$categoryUUID);

        if($stmt->execute()){
            $stmt->bind_result($uuid,$categoryName,$categoryEncrypted,$categoryType,$categoryBinding);
            $stmt->fetch();
            $stmt->close();

            if(!empty($uuid)){
                $this->categoryUUID = $uuid;
                $this->categoryName = $categoryName;
                $this->categoryEncrypted = $categoryEncrypted;
                $this->categoryType = $categoryType;
                $this->categoryBinding = $categoryBinding;

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
                                                    categoryBinding)
                                                VALUES
                                                    (?,?,?,?,?)
                                                ON DUPLICATE KEY UPDATE
                                                    uuid=VALUES(uuid),
                                                    categoryName=VALUES(categoryName),
                                                    categoryEncrypted=VALUES(categoryEncrypted),
                                                    categoryType=VALUES(categoryType),
                                                    categoryBinding=VALUES(categoryBinding)");

            $stmt->bind_param("ssiss",$this->categoryUUID,$this->categoryName,$this->categoryEncrypted,$this->categoryType,$this->categoryBinding);

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
        $stmt = $this->db->prepare("DELETE FROM flashCardCategories WHERE uuid = ?");
        $stmt->bind_param("s",$categoryUUID);

        if($stmt->execute()){
            $this->log->setAction("FLASH_CARD_CATEGORY_DELETE");
            $this->log->setDetail("Category UUID",$this->categoryUUID);
            $this->log->setDetail("Category Name",$this->categoryName);
            $this->log->setDetail("Category Type",$this->categoryType);
            $this->log->setDetail("Category Binding",$this->categoryBinding);
            $this->log->saveEntry();
            $stmt->close();

            return true;
        }
        else{
            $this->log->setAction("ERROR_FLASH_CARD_CATEGORY_DELETE");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Calling Function","flashCardManager->deleteFlashCardCategory()");
            $this->log->setDetail("Category UUID",$categoryUUID);
            $this->log->saveEntry();
            $stmt->close();

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
        $stmt = $this->db->prepare("DELETE FROM flashCardData WHERE uuid = ?");
        $stmt->bind_param("s",$cardUUID);

        if($stmt->execute()){
            return true;
        }
        else{
            $this->log->setAction("ERROR_FLASH_CARD_DELETE");
            $this->log->setDetail("MySQL Error",$stmt->error);
            $this->log->setDetail("Calling Function","flashCardManager->deleteFlashCardData()");
            $this->log->setDetail("Card UUID",$cardUUID);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
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