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
                <section>
                    <header>
                        <h2>Category List</h2>
                    </header>
                    <table>
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Total Cards</th>
                                <th>Type</th>
                                <th>Encrypted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($cardCategoryList as $categoryUUID => $categoryData): ?>
                            <tr>
                                <td><?php echo $categoryData['categoryName']; ?></td>
                                <td><?php echo $flashCardManager->getCardCount($categoryUUID); ?></td>
                                <td><?php echo $categoryData['categoryType']; ?></td>
                                <td><?php echo ($categoryData['categoryEncrypted']) ? "<strong>Yes</strong>" : "No"; ?></td>
                                <td><a href="/admin/flash-card-categories/delete/<?php echo $categoryUUID; ?>">[delete]</a></td>
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
                    include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/edit.inc.php";
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
