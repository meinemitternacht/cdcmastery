<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/30/2015
 * Time: 11:30 AM
 */

$formAction = isset($_POST['formAction']) ? $_POST['formAction'] : false;

$section = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$action = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
$workingChild = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : false;

?>
<div class="container">
    <header>
        <h2>Test Manager</h2>
    </header>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Section</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin/tests/completed" title="Completed Tests">Completed Tests</a></li>
                        <li><a href="/admin/tests/incomplete" title="Incomplete Tests">Incomplete Tests</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <?php
        if($section) {
            switch ($section) {
                case "completed":
                    if (!empty($action)) {
                        switch ($action) {
                            case "view":
                                require_once BASE_PATH . "/includes/modules/admin/tests/completed/view.inc.php";
                                break;
                            case "edit":
                                require_once BASE_PATH . "/includes/modules/admin/tests/completed/edit.inc.php";
                                break;
                            case "delete":
                                require_once BASE_PATH . "/includes/modules/admin/tests/completed/delete.inc.php";
                                break;
                        }
                    }
                    else {
                        require_once BASE_PATH . "/includes/modules/admin/tests/completed/list.inc.php";
                    }
                    break;
                case "incomplete":
                    if (!empty($action)) {
                        switch ($action) {
                            case "view":
                                require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/view.inc.php";
                                break;
                            case "edit":
                                require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/edit.inc.php";
                                break;
                            case "delete":
                                require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/delete.inc.php";
                                break;
                            case "score":
                                require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/score.inc.php";
                                break;
                            case "reset":
                                require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/reset.inc.php";
                                break;
                        }
                    }
                    else {
                        require_once BASE_PATH . "/includes/modules/admin/tests/incomplete/list.inc.php";
                    }
                    break;
            }
        }
        ?>
    </div>
</div>