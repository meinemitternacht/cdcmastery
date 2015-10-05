<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 3:08 PM
 */
$flashCardManager = new flashCardManager($db,$log);
$cardCategoryList = $flashCardManager->listCardCategories(true);

if($cardCategoryList): ?>
    <div class="container">
        <div class="row">
            <div class="12u">
                <section>
                    <header>
                        <h2>Choose a category</h2>
                    </header>
                    <p>
                        Click on a category name to start studying!
                    </p>
                        <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                            <a href="/cards/study/<?php echo $categoryUUID; ?>"><?php echo $categoryData['categoryName']; ?></a>
                        <?php endforeach; ?>
                </section>
            </div>
        </div>
    </div>
<?php
else: ?>
    There are no Flash Card Categories in the database. <a href="/cards/add">Click here to add one!</a>
<?php
endif; ?>
