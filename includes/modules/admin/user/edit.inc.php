<?php
if(!$userManager->verifyUser($userUUID)){
    $systemMessages->addMessage("That user does not exist.", "warning");
    $cdcMastery->redirect("/admin/users");
}

if($roleManager->getRoleType($objUser->getUserRole()) == "admin" && $roleManager->getRoleType($userManager->getUserRole()) != "admin"){
    $systemMessages->addMessage("You cannot edit administrators unless you are an administrator.");
    $cdcMastery->redirect("/admin/users/".$userUUID);
}

if(!empty($_POST) && $_POST['saveUser'] == true){
    /*
     * Check for empty fields that are required
     */
    $error = false;

    if(empty($_POST['userHandle'])){ $systemMessages->addMessage("Username cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userRank'])){ $systemMessages->addMessage("Rank cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userEmail'])){ $systemMessages->addMessage("E-mail address cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userFirstName'])){ $systemMessages->addMessage("First name cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userLastName'])){ $systemMessages->addMessage("Last name cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userBase'])){ $systemMessages->addMessage("Base cannot be empty.  If their base is not listed, choose 'Other'.", "warning"); $error = true; }
    if(empty($_POST['timeZone'])){ $systemMessages->addMessage("Time zone cannot be empty.", "warning"); $error = true; }
    if(empty($_POST['userRole'])){ $systemMessages->addMessage("User role cannot be empty.", "warning"); $error = true; }

    if($error){
        $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
    }

    /*
     * Check userHandle and userEmail to ensure collisions don't take place
     */
    if($_POST['userHandle'] != $objUser->getUserHandle()) {
        if ($userManager->getUUIDByHandle($_POST['userHandle'])) {
            $systemMessages->addMessage("That username is already in use.  Please choose a different one.", "warning");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if($_POST['userEmail'] != $objUser->getUserEmail()) {
        if ($userManager->getUUIDByEmail($_POST['userEmail'])) {
            $systemMessages->addMessage("That e-mail address is already in use.  Please choose a different one.", "warning");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if(!empty($_POST['user-pw-edit'])) {
        $complexityCheck = $cdcMastery->checkPasswordComplexity($_POST['user-pw-edit'], $_POST['userHandle'], $_POST['userEmail']);

        if (!is_array($complexityCheck)) {
            $objUser->setUserPassword($_POST['user-pw-edit']);
        } else {
            foreach ($complexityCheck as $complexityCheckError) {
                $systemMessages->addMessage($complexityCheckError, "warning");
            }

            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if(empty($_POST['userOfficeSymbol'])){
        $notListedOfficeSymbol = $officeSymbolManager->getOfficeSymbolByName("Not Listed");
        if($notListedOfficeSymbol){
            $objUser->setUserOfficeSymbol($notListedOfficeSymbol);
        }
    }
    else{
        $objUser->setUserOfficeSymbol($_POST['userOfficeSymbol']);
    }

    $objUser->setUserEmail($_POST['userEmail']);
    $objUser->setUserHandle($_POST['userHandle']);
    $objUser->setUserBase($_POST['userBase']);
    $objUser->setUserFirstName($_POST['userFirstName']);
    $objUser->setUserLastName($_POST['userLastName']);
    $objUser->setUserRank($_POST['userRank']);
    $objUser->setUserTimeZone($_POST['timeZone']);

    $objUserStatistics = new UserStatisticsModule($db, $systemLog, $roleManager, $memcache);
    $objUserStatistics->setUserUUID($objUser->getUUID());

    /**
     * Migrate Supervisor Subordinates to Training Manager
     */
    if($roleManager->getRoleType($objUser->getUserRole()) == "supervisor" && $roleManager->getRoleType($_POST['userRole']) == "trainingManager"){
        if($objUserStatistics->getSupervisorSubordinateCount() > 0){
            $subordinateList = $objUserStatistics->getSupervisorAssociations();

            if(count($subordinateList) > 1){
                foreach($subordinateList as $subordinateUUID){
                    $associationManager->addTrainingManagerAssociation($objUser->getUUID(), $subordinateUUID);
                    $associationManager->deleteSupervisorAssociation($objUser->getUUID(), $subordinateUUID);
                }
            }
            else{
                $associationManager->addTrainingManagerAssociation($objUser->getUUID(), $subordinateList[0]);
                $associationManager->deleteSupervisorAssociation($objUser->getUUID(), $subordinateList[0]);
            }

            if($objUserStatistics->getSupervisorSubordinateCount() > 0){
                $systemLog->setAction("ERROR_MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
                $systemLog->setDetail("Source Role", $roleManager->getRoleName($objUser->getUserRole()));
                $systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
                $systemLog->setDetail("User UUID", $objUser->getUUID());
                $systemLog->setDetail("Error", "After migration attempt, old associations still remained in the database.");
                $systemLog->saveEntry();

                $systemMessages->addMessage("After migration attempt, old associations still remained in the database. Contact CDCMastery Support for assistance with changing this user's role.", "danger");
                $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
            }
            else{
                $systemLog->setAction("MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
                $systemLog->setDetail("User UUID", $objUser->getUUID());
                $systemLog->setDetail("Source Role", $roleManager->getRoleName($objUser->getUserRole()));
                $systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
                $systemLog->saveEntry();
            }
        }
    }

    /**
     * Migrate Training Manager Subordinates to Supervisor
     */
    elseif($roleManager->getRoleType($objUser->getUserRole()) == "trainingManager" && $roleManager->getRoleType($_POST['userRole']) == "supervisor"){
        if($objUserStatistics->getTrainingManagerSubordinateCount() > 0){
            $subordinateList = $objUserStatistics->getTrainingManagerAssociations();

            if(sizeof($subordinateList) > 1){
                foreach($subordinateList as $subordinateUUID){
                    $associationManager->addSupervisorAssociation($objUser->getUUID(), $subordinateUUID);
                    $associationManager->deleteTrainingManagerAssociation($objUser->getUUID(), $subordinateUUID);
                }
            }
            else{
                $associationManager->addSupervisorAssociation($objUser->getUUID(), $subordinateList[0]);
                $associationManager->deleteTrainingManagerAssociation($objUser->getUUID(), $subordinateList[0]);
            }

            if($objUserStatistics->getTrainingManagerSubordinateCount() > 0){
                $systemLog->setAction("ERROR_MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
                $systemLog->setDetail("Source Role", $roleManager->getRoleName($objUser->getUserRole()));
                $systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
                $systemLog->setDetail("User UUID", $objUser->getUUID());
                $systemLog->setDetail("Error", "After migration attempt, old associations still remained in the database.");
                $systemLog->saveEntry();

                $systemMessages->addMessage("After migration attempt, old associations still remained in the database. Contact CDCMastery Support for assistance with changing this user's role.", "danger");
                $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
            }
            else{
                $systemLog->setAction("MIGRATE_SUBORDINATE_ASSOCIATIONS_ROLE_TYPE");
                $systemLog->setDetail("User UUID", $objUser->getUUID());
                $systemLog->setDetail("Source Role", $roleManager->getRoleName($objUser->getUserRole()));
                $systemLog->setDetail("Destination Role", $roleManager->getRoleName($_POST['userRole']));
                $systemLog->saveEntry();
            }
        }
    }

    $objUser->setUserRole($_POST['userRole']);

    if($objUser->saveUser()){
        $systemLog->setAction("USER_EDIT");
        $systemLog->setDetail("User UUID", $userUUID);
        $systemLog->saveEntry();

        $systemMessages->addMessage("User " . $objUser->getFullName() . " was edited successfully.", "success");
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
    else{
        $systemLog->setAction("ERROR_USER_EDIT");
        $systemLog->setDetail("Error", $objUser->error);
        $systemLog->setDetail("User UUID", $userUUID);

        foreach($_POST as $editFormKey => $editFormVal){
            $systemLog->setDetail($editFormKey, $editFormVal);
        }

        $systemLog->saveEntry();
        $systemMessages->addMessage("There was a problem saving the information for that user.  Please open a support ticket for assistance.", "danger");
        $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                </header>
            </section>
            <div class="sub-menu">
                <div class="menu-heading">
                    Edit User
                </div>
                <ul>
                    <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                </ul>
            </div>
        </div>
        <div class="8u">
            <section>
                <header>
                    <h2>Edit User</h2>
                </header>
                <p><em>Note: Only enter a new password if it needs to be changed.  If left blank, the user's password will not be changed.</em></p>
                <form action="/admin/users/<?php echo $userUUID; ?>/edit" method="POST">
                    <input type="hidden" name="saveUser" value="1">
                    <ul class="userEditForm">
                        <li>
                            <label for="userRank">Rank</label>
                            <br>
                            <select id="userRank"
                                    name="userRank"
                                    size="1"
                                    class="input_full"
                                    data-validation="required"
                                    data-validation-error-msg="You must select the user's rank">
                                <option value="">Select rank...</option>
                                <?php
                                $rankList = $cdcMastery->listRanks();
                                foreach($rankList as $rankGroupLabel => $rankGroup){
                                    echo '<optgroup label="'.$rankGroupLabel.'">';
                                    foreach($rankGroup as $rankOrder){
                                        foreach($rankOrder as $rankKey => $rankVal): ?>
                                            <?php if($objUser->getUserRank() == $rankKey): ?>
                                                <option value="<?php echo $rankKey; ?>" SELECTED><?php echo $rankVal; ?></option>
                                            <?php else: ?>
                                                <option value="<?php echo $rankKey; ?>"><?php echo $rankVal; ?></option>
                                            <?php endif;
                                        endforeach;
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="userFirstName">First Name</label>
                            <br>
                            <input id="userFirstName"
                                   name="userFirstName"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $objUser->getUserFirstName(); ?>"
                                   data-validation="required"
                                   data-validation-error-msg="You must provide a first name">
                        </li>
                        <li>
                            <label for="userLastName">Last Name</label>
                            <br>
                            <input id="userLastName"
                                   name="userLastName"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $objUser->getUserLastName(); ?>"
                                   data-validation="required"
                                   data-validation-error-msg="You must provide a last name">
                        </li>
                        <li>
                            <label for="userEmail">E-mail</label>
                            <br>
                            <input id="userEmail"
                                   name="userEmail"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $objUser->getUserEmail(); ?>"
                                   data-validation="email">
                        </li>
                        <li>
                            <label for="userBase">Base</label>
                            <br>
                            <select id="userBase"
                                    name="userBase"
                                    size="1"
                                    class="input_full"
                                    data-validation="required"
                                    data-validation-error-msg="You must provide the user's base">
                                <option value="">Select base...</option>
                                <?php
                                $baseList = $baseManager->listBases();

                                foreach($baseList as $baseUUID => $baseName): ?>
                                    <?php if($objUser->getUserBase() == $baseUUID): ?>
                                        <option value="<?php echo $baseUUID; ?>" SELECTED><?php echo $baseName; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $baseUUID; ?>"><?php echo $baseName; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="userOfficeSymbol">Office Symbol</label>
                            <br>
                            <select id="userOfficeSymbol"
                                    name="userOfficeSymbol"
                                    size="1"
                                    class="input_full">
                                <option value="">Select office symbol...</option>
                                <?php
                                $officeSymbolList = $officeSymbolManager->listOfficeSymbols();

                                foreach($officeSymbolList as $officeSymbolUUID => $officeSymbolName): ?>
                                    <?php if($objUser->getUserOfficeSymbol() == $officeSymbolUUID): ?>
                                        <option value="<?php echo $officeSymbolUUID; ?>" SELECTED><?php echo $officeSymbolName; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $officeSymbolUUID; ?>"><?php echo $officeSymbolName; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="userHandle">Username</label>
                            <br>
                            <input id="userHandle"
                                   name="userHandle"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $objUser->getUserHandle(); ?>"
                                   data-validation="length"
                                   data-validation-length="3-32"
                                   data-validation-error-msg="The username must be between 3 and 32 characters">
                        </li>
                        <li>
                            <label for="userPassword_confirmation">Password</label>
                            <br>
                            <input id="user-pw-edit"
                                   name="user-pw-edit"
                                   type="password"
                                   class="input_full">
                        </li>
                        <li>
                            <label for="timeZone">Time Zone</label>
                            <br>
                            <?php $tzList = $cdcMastery->listTimeZones(); ?>
                            <select id="timeZone"
                                    name="timeZone"
                                    size="1"
                                    class="input_full"
                                    data-validation="required"
                                    data-validation-error-msg="Time Zone cannot be empty">
                                <option value="">Select Time Zone...</option>
                                <?php foreach($tzList as $tzGroup): ?>
                                    <?php foreach($tzGroup as $tz): ?>
                                        <?php if($objUser->getUserTimeZone() == $tz): ?>
                                            <option value="<?php echo $tz; ?>" SELECTED><?php echo $tz; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $tz; ?>"><?php echo $tz; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="userRole">Permission Role</label>
                            <br>
                            <select id="userRole"
                                    name="userRole"
                                    size="1"
                                    class="input_full"
                                    data-validation="required"
                                    data-validation-error-msg="You must provide the User Role">
                                <?php
                                $roleList = $roleManager->listRoles();
                                foreach($roleList as $roleUUID => $roleDetails): ?>
                                    <?php if(!$cdcMastery->verifyAdmin() && $roleDetails['roleType'] == "admin") continue; ?>
                                    <?php if($objUser->getUserRole() == $roleUUID): ?>
                                        <option value="<?php echo $roleUUID; ?>" SELECTED><?php echo $roleDetails['roleName']; ?></option>
                                    <?php else: ?>
                                        <option value="<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></option>
                                    <?php endif;
                                endforeach; ?>
                            </select>
                        </li>
                    </ul>
                    <input type="submit" value="Save">
                </form>
            </section>
        </div>
    </div>
</div>