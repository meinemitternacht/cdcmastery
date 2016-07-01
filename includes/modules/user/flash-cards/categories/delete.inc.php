<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */
if(isset($_POST['confirmCategoryDelete'])){
    if($flashCardManager->deleteFlashCardCategory($workingChild)){
        $systemMessages->addMessage("Category deleted successfully.", "success");
        $cdcMastery->redirect("/cards/categories");
    }
    else{
        $systemMessages->addMessage("Category could not be deleted. Contact the support help desk for assistance.", "danger");
    }
}
?>
<section>
    <header>
        <h2>Delete flash card category <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <form action="/cards/categories/delete/<?php echo $workingChild; ?>" method="POST">
        <input type="hidden" name="confirmCategoryDelete" value="1">
        <p>
            Are you sure you wish to delete this category?
        </p>
        <input type="submit" value="Delete Category">
    </form>
</section>
