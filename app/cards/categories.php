<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 7:25 AM
 */

$flashCardManager = new flashCardManager($db,$log);
$action = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$workingChild = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
?>
<div class="container">
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>Flash Card Categories</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                            <li><a href="/cards"><i class="icon-inline icon-20 ic-home"></i>Flash Cards Home</a></li>
                        <?php if(!$action): ?>
                            <li><a href="/cards/categories/add"><i class="icon-inline icon-20 ic-plus"></i>Add Category</a></li>
                        <?php else: ?>
                            <li><a href="/cards/categories"><i class="icon-inline icon-20 ic-arrow-left"></i>Category Manager</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
        </div>
        <div class="9u">
            <?php
            if(!$action):
                $cardCategoryList = $flashCardManager->listPrivateCardCategories($_SESSION['userUUID']);
                if($cardCategoryList): ?>
                    <section>
                        <header>
                            <h2>Category List</h2>
                        </header>
                        <p>
                            Click on the category name to manage flash card data for that particular category.  To edit or delete the category, click on the appropriate icons
                            show in the last column.
                        </p>
                        <table>
                            <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Total Cards</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                                <tr>
                                    <td><a href="/cards/data/<?php echo $categoryUUID; ?>" title="Manage Flash Card Data"><?php echo $categoryData['categoryName']; ?></a></td>
                                    <td><?php echo $flashCardManager->getCardCount($categoryUUID); ?></td>
                                    <td>
                                        <a href="/cards/categories/delete/<?php echo $categoryUUID; ?>" title="Delete Category"><i class="icon-inline icon-20 ic-delete"></i></a>
                                        <a href="/cards/categories/edit/<?php echo $categoryUUID; ?>" title="Edit Category"><i class="icon-inline icon-20 ic-pencil"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                    <?php
                else:
                    $sysMsg->addMessage("You do not have any flash card categories.  Add one below!","info");
                    $cdcMastery->redirect("/cards/categories/add");
                endif; ?>
                <?php
            else: ?>
                <?php
                switch($action){
                    case "add":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/categories/add.inc.php";
                        break;
                    case "edit":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/categories/edit.inc.php";
                        break;
                    case "delete":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/categories/delete.inc.php";
                        break;
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>
