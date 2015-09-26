<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 10/28/14
 * Time: 10:17 PM
 */

$formAction = isset($_POST['formAction']) ? $_POST['formAction'] : false;
$subAction = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$workingAFSC = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

if($formAction){
    switch($formAction){
        case "afsc-add":
            $afscData['afscName'] = isset($_POST['afscName']) ? $_POST['afscName'] : false;
            $afscData['afscFOUO'] = isset($_POST['afscFOUO']) ? $_POST['afscName'] : false;
            $afscData['afscVersion'] = isset($_POST['afscVersion']) ? $_POST['afscName'] : false;
            $afscData['afscDescription'] = isset($_POST['afscDescription']) ? $_POST['afscName'] : false;

            if(!$afscData['afscName']) {
                $sysMsg->addMessage("AFSC Name cannot be blank.");
                $cdcMastery->redirect("/admin/afsc");
            }
            elseif(!$afscData['afscFOUO']) {
                $sysMsg->addMessage("FOUO status must be provided.");
                $cdcMastery->redirect("/admin/afsc");
            }
            else{
                $afsc->newAFSC();
                $afsc->setAFSCName($afscData['afscName']);
                $afsc->setAFSCFOUO($afscData['afscFOUO']);
                $afsc->setAFSCVersion($afscData['afscVersion']);
                $afsc->setAFSCDescription($afscData['afscDescription']);

                if($afsc->saveAFSC()){
                    $log->setAction("AFSC_ADD");
                    $log->setDetail("AFSC Name",$afscData['afscName']);
                    $log->setDetail("AFSC Version",$afscData['afscVersion']);
                    $log->saveEntry();

                    $sysMsg->addMessage("AFSC added successfully.");
                }
                else{
                    $log->setAction("ERROR_AFSC_ADD");
                    $log->setDetail("AFSC Name",$afscData['afscName']);
                    $log->setDetail("AFSC Version",$afscData['afscVersion']);
                    $log->setDetail("AFSC FOUO",var_dump($afscData['afscFOUO']));
                    $log->setDetail("AFSC Description",$afscData['afscDescription']);
                    $log->saveEntry();

                    $sysMsg->addMessage("There was a problem adding that AFSC.");
                }

                $cdcMastery->redirect("/admin/afsc");
            }
        break;
        case "afsc-edit":
            $afscData['afscName'] = isset($_POST['afscName']) ? $_POST['afscName'] : false;
            $afscData['afscFOUO'] = isset($_POST['afscFOUO']) ? $_POST['afscName'] : false;
            $afscData['afscVersion'] = isset($_POST['afscVersion']) ? $_POST['afscName'] : false;
            $afscData['afscDescription'] = isset($_POST['afscDescription']) ? $_POST['afscName'] : false;

            if(!$afsc->loadAFSC($workingAFSC)){
                $sysMsg->addMessage("We could not load that AFSC.");
                $cdcMastery->redirect("/admin/afsc");
            }

            if(!$afscData['afscName']) {
                $sysMsg->addMessage("AFSC Name cannot be blank.");
                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
            elseif(!$afscData['afscFOUO']) {
                $sysMsg->addMessage("FOUO status must be provided.");
                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
            else{
                $afsc->setAFSCName($afscData['afscName']);
                $afsc->setAFSCFOUO($afscData['afscFOUO']);
                $afsc->setAFSCVersion($afscData['afscVersion']);
                $afsc->setAFSCDescription($afscData['afscDescription']);

                if($afsc->saveAFSC()){
                    $log->setAction("AFSC_EDIT");
                    $log->setDetail("AFSC Name",$afscData['afscName']);
                    $log->setDetail("AFSC Version",$afscData['afscVersion']);
                    $log->saveEntry();

                    $sysMsg->addMessage("AFSC edited successfully.");
                }
                else{
                    $log->setAction("ERROR_AFSC_EDIT");
                    $log->setDetail("AFSC UUID",$workingAFSC);
                    $log->setDetail("AFSC Name",$afscData['afscName']);
                    $log->setDetail("AFSC Version",$afscData['afscVersion']);
                    $log->setDetail("AFSC FOUO",$afscData['afscFOUO'] ? "true":"false");
                    $log->setDetail("AFSC Description",$afscData['afscDescription']);
                    $log->saveEntry();

                    $sysMsg->addMessage("There was a problem editing that AFSC.");
                }

                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
        break;
        case "afsc-delete":

        break;
    }
}

if(!$subAction):
    $afscList = $afsc->listAFSC(); ?>
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>AFSC Manager</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                        </ul>
                    </div>
                </section>
                <div class="clearfix">&nbsp;</div>
                <br>
                <section>
                    <header>
                        <h2>Add AFSC</h2>
                    </header>
                    <form action="/admin/afsc" method="POST">
                        <input type="hidden" name="formAction" value="afsc-add">
                        <ul>
                            <li>
                                <label for="afscName">Name</label>
                                <br>
                                <input type="text" class="input_full" name="afscName" id="afscName">
                            </li>
                            <li>
                                <label for="afscFOUO">For Official Use Only?</label>
                                <br>
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="1"> Yes
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="0" CHECKED> No
                            </li>
                            <li>
                                <label for="afscVersion">Version</label>
                                <br>
                                <input type="text" class="input_full" name="afscVersion" id="afscVersion">
                            </li>
                            <li>
                                <label for="afscDescription">Description</label>
                                <br>
                                <textarea id="afscDescription" name="afscDescription"></textarea>
                            </li>
                            <li>
                                <br>
                                <input type="submit" value="Add">
                            </li>
                        </ul>
                    </form>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>AFSC List - <?php echo count($afscList); ?> Total</h2>
                    </header>
                    <table id="afsc-table-1">
                        <thead>
                            <tr>
                                <th>AFSC</th>
                                <th>FOUO</th>
                                <th>Version</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                            <tr>
                                <td><?php echo $afscDetails['afscName']; ?></td>
                                <td><?php echo $afscDetails['afscFOUO'] ? "Yes" : "No"; ?></td>
                                <td><?php echo $afscDetails['afscVersion']; ?></td>
                                <td>
                                    <a href="/admin/afsc/delete/<?php echo $afscUUID; ?>" title="Delete"><i class="icon-inline icon-20 ic-delete"></i></a>
                                    <a href="/admin/afsc/edit/<?php echo $afscUUID; ?>" title="Edit"><i class="icon-inline icon-20 ic-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </div>
<?php elseif($subAction == "edit"):
    if(!$workingAFSC){
        $sysMsg->addMessage("You must select an AFSC to edit.");
        $cdcMastery->redirect("/admin/afsc");
    }
    else{
        if(!$afsc->loadAFSC($workingAFSC)){
            $sysMsg->addMessage("That AFSC does not exist.");
            $cdcMastery->redirect("/admin/afsc");
        }
    }
    ?>
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>AFSC Manager</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin/afsc"><i class="icon-inline icon-20 ic-puzzle"></i>AFSC Manager Menu</a></li>
                            <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>Editing AFSC <?php echo $afsc->getAFSCName(); ?></h2>
                    </header>
                    <form action="/admin/afsc/edit/<?php echo $workingAFSC; ?>" method="POST">
                        <input type="hidden" name="formAction" value="afsc-edit">
                        <ul>
                            <li>
                                <label for="afscName">Name</label>
                                <br>
                                <input type="text" class="input_full" name="afscName" id="afscName" value="<?php echo $afsc->getAFSCName(); ?>">
                            </li>
                            <li>
                                <label for="afscFOUO">For Official Use Only?</label>
                                <br>
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="1" <?php if($afsc->getAFSCFOUO()) echo "CHECKED"; ?>> Yes
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="0" <?php if(!$afsc->getAFSCFOUO()) echo "CHECKED"; ?>> No
                            </li>
                            <li>
                                <label for="afscVersion">Version</label>
                                <br>
                                <input type="text" class="input_full" name="afscVersion" id="afscVersion" value="<?php echo $afsc->getAFSCVersion(); ?>">
                            </li>
                            <li>
                                <label for="afscDescription">Description</label>
                                <br>
                                <textarea id="afscDescription" name="afscDescription" <?php echo $afsc->getAFSCDescription(); ?>></textarea>
                            </li>
                            <li>
                                <br>
                                <input type="submit" value="Save Changes">
                            </li>
                        </ul>
                    </form>
                </section>
            </div>
        </div>
    </div>
<?php elseif($subAction == "delete"): ?>
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>AFSC Manager</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin">Return to Admin Panel</a></li>
                        </ul>
                    </div>
                </section>
                <div class="clearfix">&nbsp;</div>
                <br>
                <section>
                    <header>
                        <h2>Add AFSC</h2>
                    </header>
                    <form action="/admin/afsc" method="POST">
                        <input type="hidden" name="formAction" value="afsc-add">
                        <ul>
                            <li>
                                <label for="afscName">Name</label>
                                <br>
                                <input type="text" class="input_full" name="afscName" id="afscName">
                            </li>
                            <li>
                                <label for="afscFOUO">For Official Use Only?</label>
                                <br>
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="1"> Yes
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="0" CHECKED> No
                            </li>
                            <li>
                                <label for="afscVersion">Version</label>
                                <br>
                                <input type="text" class="input_full" name="afscVersion" id="afscVersion">
                            </li>
                            <li>
                                <label for="afscDescription">Description</label>
                                <br>
                                <textarea id="afscDescription" name="afscDescription"></textarea>
                            </li>
                            <li>
                                <br>
                                <input type="submit" value="Add">
                            </li>
                        </ul>
                    </form>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>AFSC List - <?php echo count($afscList); ?> Total</h2>
                    </header>
                    <table id="afsc-table-1">
                        <thead>
                        <tr>
                            <th>AFSC</th>
                            <th>FOUO</th>
                            <th>Version</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                            <tr>
                                <td><?php echo $afscDetails['afscName']; ?></td>
                                <td><?php echo $afscDetails['afscFOUO'] ? "Y" : "N"; ?></td>
                                <td><?php echo $afscDetails['afscVersion']; ?></td>
                                <td>
                                    <a href="/admin/afsc/delete/<?php echo $afscUUID; ?>" title="Delete"><i class="icon-inline icon-20 ic-delete"></i></a>
                                    <a href="/admin/afsc/edit/<?php echo $afscUUID; ?>" title="Edit"><i class="icon-inline icon-20 ic-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </div>
<?php endif; ?>