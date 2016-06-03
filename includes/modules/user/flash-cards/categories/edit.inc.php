<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmCategoryEdit'])){
    $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : false;
    $categoryComments = isset($_POST['categoryComments']) ? $_POST['categoryComments'] : false;

    $editError = false;

    if(!$categoryName){
        $sysMsg->addMessage("Category name cannot be empty.","warning");
        $editError = true;
    }

    if(!$editError) {
        $flashCardManager->setCategoryName($categoryName);
        $flashCardManager->setCategoryComments($categoryComments);

        if($flashCardManager->saveFlashCardCategory()){
            $log->setAction("FLASH_CARD_CATEGORY_EDIT");
            $log->setDetail("Category UUID",$flashCardManager->getCategoryUUID());
            $log->saveEntry();

            $sysMsg->addMessage("Flash card category edited successfully.","success");

            unset($categoryName);
            unset($categoryComments);
        }
        else{
            $sysMsg->addMessage("The flash card category could not be edited.  Contact the support help desk for assistance.","danger");
        }
    }
}

$categoryName = $flashCardManager->getCategoryName();
$categoryComments = $flashCardManager->getCategoryComments();
?>
<section>
    <header>
        <h2>Edit Flash Card Category <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <form action="/cards/categories/edit/<?php echo $workingChild; ?>" method="POST">
        <input type="hidden" name="confirmCategoryEdit" value="1">
        <p>
            Change the details of the flash card category below.
        </p>
        <ul class="form-field-list">
            <li>
                <label for="categoryName">Name</label>
                <p>
                    Enter a name for the Flash Card Category.  This should be a unique identifier that clearly conveys the content within the category.
                    <em>e.g. My 2W151 Flashcards</em>
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
        <input type="submit" value="Edit Category">
    </form>
</section>
