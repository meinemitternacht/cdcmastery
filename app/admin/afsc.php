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
            $afscData['afscFOUO'] = isset($_POST['afscFOUO']) ? $_POST['afscFOUO'] : false;
            $afscData['afscVersion'] = isset($_POST['afscVersion']) ? $_POST['afscVersion'] : false;
            $afscData['afscDescription'] = isset($_POST['afscDescription']) ? $_POST['afscDescription'] : false;

            if(!$afscData['afscName']) {
                $systemMessages->addMessage("AFSC Name cannot be blank.", "warning");
                $cdcMastery->redirect("/admin/afsc");
            }
            elseif(!isset($_POST['afscFOUO'])) {
                $systemMessages->addMessage("FOUO status must be provided.", "warning");
                $cdcMastery->redirect("/admin/afsc");
            }
            elseif($afscManager->getAFSCUUIDByName($afscData['afscName']) !== false){
                $systemMessages->addMessage("That AFSC already exists.  You must either change the name of the existing AFSC or choose a different name.", "danger");
                $cdcMastery->redirect("/admin/afsc");
            }
            else{
                $afscManager->newAFSC();
                $afscManager->setAFSCName($afscData['afscName']);
                $afscManager->setAFSCFOUO($afscData['afscFOUO']);
                $afscManager->setAFSCVersion($afscData['afscVersion']);
                $afscManager->setAFSCDescription($afscData['afscDescription']);

                if($afscManager->saveAFSC()){
                    $systemLog->setAction("AFSC_ADD");
                    $systemLog->setDetail("AFSC Name", $afscData['afscName']);
                    $systemLog->setDetail("AFSC Version", $afscData['afscVersion']);
                    $systemLog->saveEntry();

                    $systemMessages->addMessage("AFSC added successfully.", "success");
                }
                else{
                    $systemLog->setAction("ERROR_AFSC_ADD");
                    $systemLog->setDetail("AFSC Name", $afscData['afscName']);
                    $systemLog->setDetail("AFSC Version", $afscData['afscVersion']);
                    $systemLog->setDetail("AFSC FOUO", $afscData['afscFOUO']);
                    $systemLog->setDetail("AFSC Description", $afscData['afscDescription']);
                    $systemLog->saveEntry();

                    $systemMessages->addMessage("There was a problem adding that AFSC.", "danger");
                }

                $cdcMastery->redirect("/admin/afsc");
            }
        break;
        case "afsc-edit":
            $afscData['afscName'] = isset($_POST['afscName']) ? $_POST['afscName'] : false;
            $afscData['afscFOUO'] = isset($_POST['afscFOUO']) ? $_POST['afscFOUO'] : false;
            $afscData['afscHidden'] = isset($_POST['afscHidden']) ? $_POST['afscHidden'] : false;
            $afscData['afscVersion'] = isset($_POST['afscVersion']) ? $_POST['afscVersion'] : false;
            $afscData['afscDescription'] = isset($_POST['afscDescription']) ? $_POST['afscDescription'] : false;

            if(!$afscManager->loadAFSC($workingAFSC)){
                $systemMessages->addMessage("We could not load that AFSC.", "danger");
                $cdcMastery->redirect("/admin/afsc");
            }

            if(!$afscData['afscName']) {
                $systemMessages->addMessage("AFSC Name cannot be blank.", "warning");
                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
            elseif(!isset($_POST['afscFOUO'])) {
                $systemMessages->addMessage("FOUO status must be provided.", "warning");
                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
            elseif(!isset($_POST['afscHidden'])) {
                $systemMessages->addMessage("You must select if the AFSC is hidden.", "warning");
                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
            else{
                $afscManager->setAFSCName($afscData['afscName']);

                if($afscManager->getAFSCFOUO() != $afscData['afscFOUO']){
                    if(!$afscManager->toggleFOUO($afscData['afscFOUO'])){
                        $systemMessages->addMessage("There was a problem toggling the FOUO status for that AFSC.  Refer to the site log for details.", "danger");
                        $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
                    }
                }

                $afscManager->setAFSCFOUO($afscData['afscFOUO']);
                $afscManager->setAFSCHidden($afscData['afscHidden']);
                $afscManager->setAFSCVersion($afscData['afscVersion']);
                $afscManager->setAFSCDescription($afscData['afscDescription']);

                if($afscManager->saveAFSC()){
                    $systemLog->setAction("AFSC_EDIT");
                    $systemLog->setDetail("AFSC Name", $afscData['afscName']);
                    $systemLog->setDetail("AFSC Version", $afscData['afscVersion']);
                    $systemLog->saveEntry();

                    $systemMessages->addMessage("AFSC edited successfully.", "success");
                }
                else{
                    $systemLog->setAction("ERROR_AFSC_EDIT");
                    $systemLog->setDetail("AFSC UUID", $workingAFSC);
                    $systemLog->setDetail("AFSC Name", $afscData['afscName']);
                    $systemLog->setDetail("AFSC Version", $afscData['afscVersion']);
                    $systemLog->setDetail("AFSC FOUO", $afscData['afscFOUO'] ? "true":"false");
                    $systemLog->setDetail("AFSC Hidden", $afscData['afscHidden'] ? "true":"false");
                    $systemLog->setDetail("AFSC Description", $afscData['afscDescription']);
                    $systemLog->saveEntry();

                    $systemMessages->addMessage("There was a problem editing that AFSC.", "danger");
                }

                $cdcMastery->redirect("/admin/afsc/edit/".$workingAFSC);
            }
        break;
        case "afsc-hide":
            if($afscManager->loadAFSC($_POST['afscUUID'])){
                $afscManager->setAFSCHidden(true);

                if($afscManager->saveAFSC()){
                    $systemMessages->addMessage("AFSC " . $afscManager->getAFSCName() . " is now hidden.", "success");
                    $cdcMastery->redirect("/admin/afsc");
                }
                else{
                    $systemMessages->addMessage("There was a problem trying to hide " . $afscManager->getAFSCName() . ". Please contact the helpdesk for assistance.", "danger");
                    $cdcMastery->redirect("/admin/afsc");
                }
            }
        break;
        case "afsc-migrate-associations":
            $afscMigrateSource = isset($_POST['afsc-migrate-from']) ? $_POST['afsc-migrate-from'] : false;
            $afscMigrateDestination = isset($_POST['afsc-migrate-to']) ? $_POST['afsc-migrate-to'] : false;
            $afscRemovePrevious = isset($_POST['remove-old-assoc']) ? $_POST['remove-old-assoc'] : false;

            if($afscMigrateSource == $afscMigrateDestination){
                $systemMessages->addMessage("The AFSC you are migrating from cannot be the AFSC you are migrating to.", "warning");
                $cdcMastery->redirect("/admin/afsc");
            }
            elseif(!$afscMigrateSource || !$afscMigrateDestination){
                $systemMessages->addMessage("You must select an AFSC to migrate from and an AFSC to migrate to.", "warning");
                $cdcMastery->redirect("/admin/afsc");
            }
            elseif(!isset($_POST['remove-old-assoc'])){
                $systemMessages->addMessage("You must specify whether or not to remove previous AFSC associations.", "warning");
                $cdcMastery->redirect("/admin/afsc");
            }

            if($associationManager->migrateAFSCAssociations($afscMigrateSource, $afscMigrateDestination, $afscRemovePrevious)){
                $systemMessages->addMessage("Associations migrated successfully.", "success");
            }
            else{
                $systemMessages->addMessage("Associations were not migrated successfully. Please check the log for details.", "danger");
            }
            break;
    }
}

if(!$subAction):
    $afscList = $afscManager->listAFSC(true);?>
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
                <div class="clearfix">&nbsp;</div>
                <section>
                    <script>
                        $(document).ready(function(){
                            $('select').on('change', function(event ) {
                                //restore previously selected value
                                var prevValue = $(this).data('previous');
                                $('select').not(this).find('option[value="'+prevValue+'"]').show();

                                //hide option selected now
                                var value = $(this).val();
                                //update previously selected data
                                $(this).data('previous',value);
                                $('select').not(this).find('option[value="'+value+'"]').hide();
                            });
                        });
                    </script>
                    <header>
                        <h2>Migrate Associations</h2>
                    </header>
                    <p>
                        This will migrate all user associations with a target AFSC to a destination AFSC.  Please be certain the correct AFSC's are selected: <br><strong>there is no going back</strong>.
                    </p>
                    <form action="/admin/afsc" method="POST">
                        <input type="hidden" name="formAction" value="afsc-migrate-associations">
                        <ul>
                            <li>
                                <label for="afsc-migrate-from">Migrate from</label>
                                <select class="input_full" name="afsc-migrate-from" id="afsc-migrate-from" size="1">
                                    <option value="">Select AFSC...</option>
                                <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                                    <?php if($afscDetails['afscFOUO'] == true): ?>
                                    <option value="<?php echo $afscUUID; ?>" class="text-warning"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php else: ?>
                                    <option value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </select>
                                <div class="clearfix">&nbsp;</div>
                            </li>
                            <li>
                                <label for="afsc-migrate-to">Migrate to</label>
                                <select class="input_full" name="afsc-migrate-to" id="afsc-migrate-to" size="1">
                                    <option value="">Select AFSC...</option>
                                <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                                    <?php if($afscDetails['afscFOUO'] == true): ?>
                                    <option value="<?php echo $afscUUID; ?>" class="text-warning"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php else: ?>
                                    <option value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </select>
                                <div class="clearfix">&nbsp;</div>
                            </li>
                            <li>
                                <label for="remove-old-assoc">Remove associations with previous AFSC?</label>
                                <br>
                                <input type="radio" name="remove-old-assoc" value="1"> <span class="text-warning-bold">Yes</span>
                                <input type="radio" name="remove-old-assoc" value="0" CHECKED> No
                                <div class="clearfix">&nbsp;</div>
                            </li>
                            <li>
                                <div class="clearfix">&nbsp;</div>
                                <input type="submit" value="Migrate">
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
                                <th>Hidden</th>
                                <th>Version</th>
                                <th>Users</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($afscList as $afscUUID => $afscDetails): ?>
                            <tr>
                                <td><?php echo $afscDetails['afscName']; ?></td>
                                <td><?php echo $afscDetails['afscFOUO'] ? "<span style=\"color:red\">Y</span>" : "N"; ?></td>
                                <td><?php echo $afscDetails['afscHidden'] ? "<span style=\"color:red\">Y</span>" : "N"; ?></td>
                                <td><?php echo $afscDetails['afscVersion']; ?></td>
                                <td><?php echo $associationManager->listUserCountByAFSC($afscUUID); ?></td>
                                <td>
                                    <a href="/admin/afsc/hide/<?php echo $afscUUID; ?>" title="Hide"><i class="icon-inline icon-20 ic-delete"></i></a>
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
        $systemMessages->addMessage("You must select an AFSC to edit.", "warning");
        $cdcMastery->redirect("/admin/afsc");
    }
    else{
        if(!$afscManager->loadAFSC($workingAFSC)){
            $systemMessages->addMessage("That AFSC does not exist.", "danger");
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
                        <h2>Editing AFSC <?php echo $afscManager->getAFSCName(); ?></h2>
                    </header>
                    <form action="/admin/afsc/edit/<?php echo $workingAFSC; ?>" method="POST">
                        <input type="hidden" name="formAction" value="afsc-edit">
                        <ul>
                            <li>
                                <label for="afscName">Name</label>
                                <br>
                                <input type="text" class="input_full" name="afscName" id="afscName" value="<?php echo $afscManager->getAFSCName(); ?>">
                            </li>
                            <?php if($afscManager->getTotalQuestions() > 0): ?>
                            <?php else: ?>
                            <?php endif; ?>
                            <?php /*<li>
                                <label for="afscFOUO">For Official Use Only?</label>
                                <br>
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="1" <?php if($afsc->getAFSCFOUO()) echo "CHECKED"; ?> DISABLED="true"> Yes
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="0" <?php if(!$afsc->getAFSCFOUO()) echo "CHECKED"; ?> DISABLED="true"> No
                            </li> */ ?>
                            <li>
                                <label for="afscFOUO">Materials marked For Official Use Only?</label>
                                <br>
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="1" <?php if($afscManager->getAFSCFOUO()) echo "CHECKED"; ?>> Yes
                                <input type="radio" name="afscFOUO" id="afscFOUO" value="0" <?php if(!$afscManager->getAFSCFOUO()) echo "CHECKED"; ?>> No
                            </li>
                            <li>
                                <label for="afscHidden">Hide on registration view?</label>
                                <br>
                                <input type="radio" name="afscHidden" id="afscHidden" value="1" <?php if($afscManager->getAFSCHidden()) echo "CHECKED"; ?>> Yes
                                <input type="radio" name="afscHidden" id="afscHidden" value="0" <?php if(!$afscManager->getAFSCHidden()) echo "CHECKED"; ?>> No
                            </li>
                            <li>
                                <label for="afscVersion">Version</label>
                                <br>
                                <input type="text" class="input_full" name="afscVersion" id="afscVersion" value="<?php echo $afscManager->getAFSCVersion(); ?>">
                            </li>
                            <li>
                                <label for="afscDescription">Description</label>
                                <br>
                                <textarea id="afscDescription" name="afscDescription" <?php echo $afscManager->getAFSCDescription(); ?>></textarea>
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
<?php elseif($subAction == "hide"): ?>
    <?php
    if(!$afscManager->loadAFSC($workingAFSC)){
        $systemMessages->addMessage("That AFSC does not exist.", "danger");
        $cdcMastery->redirect("/admin/afsc");
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
            <div class="6u">
                <section>
                    <header>
                        <h2>Hide AFSC <?php echo $afscManager->getAFSCName(); ?></h2>
                    </header>
                    <form action="/admin/afsc" method="POST">
                        <input type="hidden" name="formAction" value="afsc-hide">
                        <input type="hidden" name="afscUUID" value="<?php echo $workingAFSC; ?>">
                        <p>
                            After hiding this AFSC, it will no longer appear in AFSC lists on the administration menu and registration page, however, the data will still be stored in the database
                            and users can still take tests using the data.  If you would like to remove it completely, please <a href="http://helpdesk.cdcmastery.com">open a support ticket</a>.
                        </p>
                        <input type="submit" value="I Understand">
                    </form>
                </section>
            </div>
        </div>
    </div>
<?php endif; ?>