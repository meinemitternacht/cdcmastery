<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */
if(!$flashCardManager->loadCardCategory($workingChild)){
    $sysMsg->addMessage("That category does not exist.","warning");
    $cdcMastery->redirect("/admin/flash-card-categories");
}

if(isset($_POST['confirmCategoryDelete'])){
    if($flashCardManager->deleteFlashCardCategory($workingChild)){
        $sysMsg->addMessage("Category deleted successfully.","success");
        $cdcMastery->redirect("/admin/flash-card-categories");
    }
    else{
        $sysMsg->addMessage("Category could not be deleted. Contact the support help desk for assistance.","danger");
    }
}
?>
<section>
    <header>
        <h2>Delete flash card category <?php echo $flashCardManager->getcategoryName(); ?></h2>
    </header>
    <form action="/admin/flash-card-categories/delete/<?php echo $workingChild; ?>" method="POST">
        <input type="hidden" name="confirmCategoryDelete" value="1">
        <p>
            Are you sure you wish to delete this category?
        </p>
        <input type="submit" value="Delete Category">
    </form>
</section>
