<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 7:25 AM
 */

$flashCardManager = new CDCMastery\FlashCardManager($db, $systemLog);
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
                        <?php if(!$action): ?>
                        <li><a href="/admin/flash-card-categories/add"><i class="icon-inline icon-20 ic-plus"></i>Add Category</a></li>
                        <?php else: ?>
                            <li><a href="/admin/flash-card-categories"><i class="icon-inline icon-20 ic-arrow-left"></i>Category Manager</a></li>
                            <?php if($action == "add"): ?>
                            <li><a href="/admin/flash-card-categories/add-afsc"><i class="icon-inline icon-20 ic-plus"></i>Add From AFSC</a></li>
                            <?php elseif($action == "add-afsc"): ?>
                            <li><a href="/admin/flash-card-categories/add"><i class="icon-inline icon-20 ic-plus"></i>Add (without AFSC)</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>
        </div>
        <div class="9u">
        <?php
        if(!$action):
            $cardCategoryList = $flashCardManager->listCardCategories();
            if($cardCategoryList): ?>
                <script>
                    $(document).ready(function()
                        {
                            $("#flashCardCategoryTable").tablesorter();
                        }
                    );
                </script>
                <section>
                    <header>
                        <h2>Category List</h2>
                    </header>
                    <p>
                        Click on the category name to manage flash card data for that particular category, and click column headers to sort the table by that column.  To edit or delete the
                        category, click on the appropriate icons shown in the last column.
                    </p>
                    <table id="flashCardCategoryTable">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Total Cards</th>
                                <th>Times Viewed</th>
                                <th>Type</th>
                                <th>Encrypted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                            <?php
                            switch($categoryData['categoryType']){
                                case "afsc":
                                    $titleText = "This category is shown to all users associated with ".$afscManager->getAFSCName($categoryData['categoryBinding']);
                                    break;
                                case "global":
                                    if(!empty($categoryData['categoryBinding'])){
                                        $titleText = "This category is shown to all users associated with ".$afscManager->getAFSCName($categoryData['categoryBinding']);
                                    }
                                    else{
                                        $titleText = "This category is shown to all users.";
                                    }
                                    break;
                                case "private":
                                    $titleText = "This category is only shown to ".$userManager->getUserNameByUUID($categoryData['categoryBinding']);
                                    break;
                                default:
                                    $titleText = "Undefined";
                                    break;
                            }
                            ?>
                            <tr>
                                <td><a href="/admin/card-data/<?php echo $categoryUUID; ?>" title="Manage Flash Card Data"><?php echo $categoryData['categoryName']; ?></a></td>
                                <td><?php echo $flashCardManager->getCardCount($categoryUUID); ?></td>
                                <td><?php echo $flashCardManager->getTimesViewed($categoryUUID); ?></td>
                                <td title="<?php echo $titleText; ?>"><?php echo $categoryData['categoryType']; ?></td>
                                <td><?php echo ($categoryData['categoryEncrypted']) ? "<strong>Yes</strong>" : "No"; ?></td>
                                <td>
                                    <a href="/admin/flash-card-categories/delete/<?php echo $categoryUUID; ?>" title="Delete Category"><i class="icon-inline icon-20 ic-delete"></i></a>
                                    <a href="/admin/flash-card-categories/edit/<?php echo $categoryUUID; ?>" title="Edit Category"><i class="icon-inline icon-20 ic-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php
            else: ?>
            There are no Flash Card Categories in the database.
            <?php
            endif; ?>
        <?php
        else: ?>
            <?php
            switch($action){
                case "add":
                    include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/add.inc.php";
                    break;
                case "add-afsc":
                    include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/add-afsc.inc.php";
                    break;
                case "edit":
                    if(isset($_SESSION['vars'][1])){
                        if($flashCardManager->loadCardCategory($_SESSION['vars'][1])){
                            include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/edit.inc.php";
                        }
                        else{
                            $systemMessages->addMessage("That flash card category does not exist.", "danger");
                            $cdcMastery->redirect("/errors/404");
                        }
                    }
                    else{
                        $systemMessages->addMessage("You must specify a card category to edit.", "warning");
                        $cdcMastery->redirect("/errors/500");
                    }
                    break;
                case "delete":
                    include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/delete.inc.php";
                    break;
            }
            ?>
        <?php endif; ?>
        </div>
    </div>
</div>
