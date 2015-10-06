<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmCategoryAdd'])){
    $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : false;
    $categoryComments = isset($_POST['categoryComments']) ? $_POST['categoryComments'] : false;

    $addError = false;

    if(!$categoryName){
        $sysMsg->addMessage("Flash card category name cannot be empty.");
        $addError = true;
    }

    if(!$addError) {
        $flashCardManager->newFlashCardCategory();
        $flashCardManager->setCategoryName($categoryName);
        $flashCardManager->setCategoryEncrypted(true);
        $flashCardManager->setCategoryType("private");
        $flashCardManager->setCategoryPrivate(true);
        $flashCardManager->setCategoryBinding($_SESSION['userUUID']);
        $flashCardManager->setCategoryComments($categoryComments);
        $flashCardManager->setCategoryCreatedBy($_SESSION['userUUID']);

        if($flashCardManager->saveFlashCardCategory()){
            $log->setAction("FLASH_CARD_CATEGORY_ADD");
            $log->setDetail("Category UUID",$flashCardManager->getCategoryUUID());
            $log->setDetail("Category Name",$flashCardManager->getCategoryName());
            $log->setDetail("Category Type",$flashCardManager->getCategoryType());
            $log->setDetail("Category Encrypted",$flashCardManager->getCategoryEncrypted());
            $log->setDetail("Category Private",$flashCardManager->getCategoryPrivate());
            $log->setDetail("Category Binding",$flashCardManager->getCategoryBinding());
            $log->saveEntry();

            $sysMsg->addMessage("Flash card category added successfully. Start creating cards below..");

            unset($categoryName);
            unset($categoryComments);

            $cdcMastery->redirect("/cards/data/".$flashCardManager->getCategoryUUID());
        }
        else{
            $sysMsg->addMessage("The flash card category could not be added.  Contact the support help desk for assistance.");
        }
    }
}

?>
<section>
    <header>
        <h2>Add Flash Card Category</h2>
    </header>
    <form action="/cards/categories/add" method="POST">
        <input type="hidden" name="confirmCategoryAdd" value="1">
        <p>
            Enter the details of the flash card category below. This category (and all of the cards under it) will only be visible to you.  If you want to study URE questions/answers,
            there <em>should</em> already be a category containing data from the testing database.
        </p>
        <ul class="form-field-list">
            <li>
                <label for="categoryName">Name</label>
                <p>
                    Enter a name for the Flash Card Category.  This should be a unique identifier that clearly conveys the content within the category.
                    <em>e.g., My 2W151 Flashcards</em>
                </p>
                <input type="text" class="input_full" name="categoryName" id="categoryName" maxlength="255"<?php if(isset($categoryName)): echo ' value="'.$categoryName.'"'; endif; ?>>
            </li>
            <li>
                <label for="categoryComments">Comments</label>
                <p>
                    Optional comments for this category.  You may want to include the CDC version here as a reference.
                </p>
                <textarea class="input_full" name="categoryComments" id="categoryComments" style="height:8em;"><?php if(isset($categoryComments)): echo $categoryComments; endif; ?></textarea>
            </li>
        </ul>
        <input type="submit" value="Add Category">
    </form>
</section>