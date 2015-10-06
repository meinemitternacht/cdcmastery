<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 3:08 PM
 */
$flashCardManager = new flashCardManager($db,$log);
$cardCategoryList = $flashCardManager->listCardCategories(true);
$userAFSCList = $userStatistics->getAFSCAssociations();

if(!$userAFSCList){
    $sysMsg->addMessage("You are not associated with any AFSC's! Why don't you add some using the page below?");
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
                        <h2>View</h2>
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
            <div class="8u">
                <section>
                    <header>
                        <h2>Create a set of flash cards</h2>
                    </header>
                    <p>
                        This section is coming soon!
                    </p>
                </section>
            </div>
        </div>
    </div>
<?php
else: ?>
    There are no Flash Card Categories in the database. <a href="/cards/add">Click here to add one!</a>
<?php
endif; ?>
