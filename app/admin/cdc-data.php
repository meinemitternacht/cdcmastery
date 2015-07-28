<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/16/2015
 * Time: 11:06 PM
 */

$formAction = isset($_POST['formAction']) ? $_POST['formAction'] : false;
$workingAFSC = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$subsection = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
$workingChild = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : false;
$action = isset($_SESSION['vars'][3]) ? $_SESSION['vars'][3] : false;

if($workingAFSC):
    if(!$afsc->loadAFSC($workingAFSC)){
        $sysMsg->addMessage("That AFSC does not exist.");
        $cdcMastery->redirect("/admin/cdc-data");
    }

    $aManager = new answerManager($db,$log);
    $qManager = new questionManager($db,$log,$afsc,$aManager);
    ?>
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>CDC Data Manager</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin/cdc-data"><i class="icon-inline icon-20 ic-arrow-left"></i>CDC Data Manager Menu</a></li>
                            <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Admin Panel</a></li>
                        </ul>
                    </div>
                    <div class="clearfix"></div>
                    <br>
                    <ul>
                        <li><strong>AFSC:</strong> <a href="/admin/cdc-data/<?php echo $workingAFSC; ?>"><?php echo $afsc->getAFSCName(); ?></a></li>
                        <li><strong>Version:</strong> <?php echo $cdcMastery->formatOutputString($afsc->getAFSCVersion()); ?></li>
                        <li><strong>FOUO:</strong> <?php echo $afsc->getAFSCFOUO() ? "Yes" : "No"; ?></li>
                        <li><strong>Hidden:</strong> <?php echo $afsc->getAFSCHidden() ? "Yes" : "No"; ?></li>
                        <li><strong>Description:</strong> <?php echo $cdcMastery->formatOutputString($afsc->getAFSCDescription()); ?></li>
                        <li><strong>Questions:</strong> <?php echo $afsc->getTotalQuestions(); ?></li>
                    </ul>
                </section>
            </div>
            <?php if(!$subsection): ?>
            <div class="4u">
                <section>
                    <header>
                        <h2>Question Management</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/list-questions"><i class="icon-inline icon-20 ic-arrow-right"></i>Show Questions</a></li>
                            <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-questions"><i class="icon-inline icon-20 ic-plus"></i>Add Questions</a></li>
                            <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/delete-questions"><i class="icon-inline icon-20 ic-delete"></i>Delete Questions</a></li>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="5u">
                <section>
                    <header>
                        <h2>Tree View</h2>
                    </header>
                </section>
                <?php $childSets = $qManager->listChildSets($workingAFSC); ?>
                <ul class="tree">
                    <li><?php echo $afsc->getAFSCName(); ?>
                        <ul>
                            <?php if(!empty($childSets)): ?>
                                <?php foreach($childSets as $childSet):
                                    $childVolumes = $qManager->listChildVolumes($childSet); ?>
                                    <li><strong><?php echo $qManager->getSetName($childSet); ?></strong>
                                        <ul>
                                            <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/set/<?php echo $childSet; ?>/edit">Edit</a></li>
                                            <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/set/<?php echo $childSet; ?>/delete">Delete</a></li>
                                            <li>Volumes
                                                <ul>
                                                    <?php if(!empty($childVolumes)): ?>
                                                        <?php foreach($childVolumes as $childVolume): ?>
                                                            <li><strong><?php echo $qManager->getVolumeName($childVolume); ?></strong>
                                                                <ul>
                                                                    <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/volume/<?php echo $childVolume; ?>/view">Details</a></li>
                                                                    <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/volume/<?php echo $childVolume; ?>/edit">Edit</a></li>
                                                                    <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/volume/<?php echo $childVolume; ?>/delete">Delete</a></li>
                                                                </ul>
                                                            </li>
                                                        <?php endforeach; ?>
                                                        <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-volume/<?php echo $childSet; ?>">Add Volume</a></li>
                                                    <?php else: ?>
                                                        <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-volume/<?php echo $childSet; ?>">Add Volume</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                                <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-set">Add Set</a></li>
                            <?php else: ?>
                                <li><a href="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-set">Add Set</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php
            else:
                switch($subsection){
                    case "list-questions":
                        require_once BASE_PATH . "/includes/modules/admin/cdc-data/list-questions.inc.php";
                        break;
                    case "add-set":
                        require_once BASE_PATH . "/includes/modules/admin/cdc-data/add-set.inc.php";
                        break;
                    case "add-volume":
                        require_once BASE_PATH . "/includes/modules/admin/cdc-data/add-volume.inc.php";
                        break;
                    case "question":
                        switch($action){
                            case "view":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/question/view.inc.php";
                                break;
                            case "edit":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/question/edit.inc.php";
                                break;
                            case "archive":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/question/archive.inc.php";
                                break;
                        }
                        break;
                    case "set":
                        switch($action){
                            case "view":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/set/view.inc.php";
                                break;
                            case "edit":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/set/edit.inc.php";
                                break;
                            case "delete":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/set/delete.inc.php";
                                break;
                        }
                        break;
                    case "volume":
                        switch($action){
                            case "view":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/volume/view.inc.php";
                                break;
                            case "edit":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/volume/edit.inc.php";
                                break;
                            case "delete":
                                require_once BASE_PATH . "/includes/modules/admin/cdc-data/volume/delete.inc.php";
                                break;
                        }
                        break;
                }
            endif; ?>
        </div>
    </div>
<?php else: ?>
<div class="container">
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>CDC Data Manager</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <div class="9u">
            <section>
                <header>
                    <h2>Choose AFSC to Manage</h2>
                </header>
                <br>
                <?php $afscList = $afsc->listAFSC(); ?>
                <div style="-webkit-column-count: 3; /* Chrome, Safari, Opera */ -moz-column-count: 3; /* Firefox */ column-count: 3;">
                    <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                        <a href="/admin/cdc-data/<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></a><br>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php endif; ?>