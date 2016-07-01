<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 1:59 PM
 */

$flashCardManager = new FlashCardManager($db, $systemLog);
$workingChild = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$action = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
$actionChild = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : false;
?>
<div class="container">
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>Flash Card Manager</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <?php if(!$action): ?>
                            <li><a href="/cards/categories"><i class="icon-inline icon-20 ic-arrow-right"></i>Manage Categories</a></li>
                        <?php else: ?>
                            <li><a href="/cards/data"><i class="icon-inline icon-20 ic-arrow-left"></i>Flash Cards</a></li>
                            <?php if(!empty($workingChild)): ?>
                                <li><a href="/cards/data/<?php echo $workingChild; ?>"><i class="icon-inline icon-20 ic-arrow-left-silver"></i>List Flash Cards</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if(!empty($workingChild)): ?>
                            <li><a href="/cards/data/<?php echo $workingChild; ?>/add"><i class="icon-inline icon-20 ic-plus"></i>Add Flash Cards</a></li>
                        <?php endif; ?>
                        <li><a href="/cards/data/<?php echo $workingChild; ?>/delete"><i class="icon-inline icon-20 ic-delete"></i>Delete Flash Cards</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <div class="9u">
            <?php
            if(!$workingChild):
                $cardCategoryList = $flashCardManager->listPrivateCardCategories($_SESSION['userUUID']);
                if($cardCategoryList): ?>
                    <section>
                        <header>
                            <h2>Category List</h2>
                        </header>
                        <p>
                            Click on a category name to view flash card data.
                        </p>
                        <table>
                            <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Total Cards</th>
                                <th>Type</th>
                                <th>Encrypted</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                                <tr>
                                    <td><a href="/cards/data/<?php echo $categoryUUID; ?>/list"><?php echo $categoryData['categoryName']; ?></a></td>
                                    <td><?php echo $flashCardManager->getCardCount($categoryUUID); ?></td>
                                    <td><?php echo $categoryData['categoryType']; ?></td>
                                    <td><?php echo ($categoryData['categoryEncrypted']) ? "<strong>Yes</strong>" : "No"; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                    <?php
                else:
                    $systemMessages->addMessage("You do not have any flash card categories.  Add one below!", "info");
                    $cdcMastery->redirect("/cards/categories/add");
                endif; ?>
                <?php
            else:
                if(!$flashCardManager->loadCardCategory($workingChild)){
                    $systemMessages->addMessage("That flash card category does not exist.", "warning");
                    $cdcMastery->redirect("/cards/data");
                }

                if($flashCardManager->getCategoryBinding() != $_SESSION['userUUID']){
                    $systemMessages->addMessage("That flash card category does not belong to you.", "danger");
                    $cdcMastery->redirect("/cards/data");
                }

                if(empty($action)){
                    $action = "list";
                }

                switch($action){
                    case "add":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/data/add.inc.php";
                        break;
                    case "edit":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/data/edit.inc.php";
                        break;
                    case "delete":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/data/delete.inc.php";
                        break;
                    case "list":
                        include_once BASE_PATH . "/includes/modules/user/flash-cards/data/list.inc.php";
                        break;
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>
