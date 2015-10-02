<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 7:25 AM
 */

$action = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$workingChild = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Flash Card Categories</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin/flash-card-categories/add">Add Category</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <div class="8u">
        <?php if(!$action): ?>

        <?php else: ?>
            <?php
            switch($action){
                case "add":
                    include_once BASE_PATH . "/includes/modules/admin/flash-cards/categories/add.inc.php";
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
