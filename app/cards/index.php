<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 3:08 PM
 */
$flashCardManager = new CDCMastery\FlashCardManager($db, $systemLog);
$cardCategoryList = $flashCardManager->listCardCategories(true);
$privateCardCategoryList = $flashCardManager->listPrivateCardCategories($_SESSION['userUUID']);
$userAFSCList = $userStatistics->getAFSCAssociations();

if(!$userAFSCList){
    $systemMessages->addMessage("You are not associated with any AFSC's! Why don't you add some using the page below?", "info");
    $cdcMastery->redirect("/user/afsc-associations");
}

if($cardCategoryList): ?>
    <div class="container">
        <div class="row">
            <div class="12u">
                <header>
                    <h2>Flash Cards</h2>
                </header>
            </div>
        </div>
        <div class="row">
            <div class="4u">
                <section>
                    <header>
                        <h2>Global Flash Cards</h2>
                    </header>
                    <p>
                        Click on a category name to start studying!  The categories below are based on the AFSC's you are associated with.
                    </p>
                    <div class="sub-menu">
                        <ul>
                        <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                            <?php
                            if($categoryData['categoryType'] != "private" && isset($categoryData['categoryBinding']) && !empty($categoryData['categoryBinding'])){

                                if(!array_key_exists($categoryData['categoryBinding'],$userAFSCList)){
                                    continue;
                                }
                            }
                            ?>
                            <li><a href="/cards/study/<?php echo $categoryUUID; ?>/new"><?php echo $categoryData['categoryName']; ?></a></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="4u">
                <section>
                    <header>
                        <h2>My flash cards</h2>
                    </header>
                    <p>
                        The flash card categories you create will be listed below.  These flash cards are only visible to you.
                    </p>
                    <a href="/cards/categories">Manage Cards and Categories</a>
                    <?php if(is_array($privateCardCategoryList) && !empty($privateCardCategoryList)): ?>
                    <div class="sub-menu">
                        <ul>
                        <?php foreach($privateCardCategoryList as $privateCardCategoryUUID => $privateCardCategoryData): ?>
                            <li><a href="/cards/study/<?php echo $privateCardCategoryUUID; ?>/new"><?php echo $privateCardCategoryData['categoryName']; ?></a></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="clearfix">&nbsp;</div>
                    <strong>You haven't created any card categories yet. Try adding one using the form to the right!</strong>
                    <?php endif; ?>
                </section>
            </div>
            <div class="4u">
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
                                    Enter a name for the Flash Card Category. <em>e.g., My 2W151 Flashcards</em>
                                </p>
                                <input type="text" class="input_full" name="categoryName" id="categoryName" maxlength="255"<?php if(isset($categoryName)): echo ' value="'.$categoryName.'"'; endif; ?>>
                            </li>
                            <li>
                                <label for="categoryComments">Comments</label>
                                <p>
                                    Optional comments for this category.
                                </p>
                                <textarea class="input_full" name="categoryComments" id="categoryComments" style="height:4em;"><?php if(isset($categoryComments)): echo $categoryComments; endif; ?></textarea>
                            </li>
                        </ul>
                        <input type="submit" value="Add Category">
                    </form>
                </section>
            </div>
        </div>
    </div>
<?php
else: ?>
    There are no Flash Card Categories in the database. <a href="/cards/categories/add">Click here to add one!</a>
<?php
endif; ?>
