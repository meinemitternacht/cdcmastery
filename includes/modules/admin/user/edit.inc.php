<?php
if(!$user->verifyUser($userUUID)){
    $sysMsg->addMessage("That user does not exist.");
    $cdcMastery->redirect("/admin/users");
}

if(!empty($_POST) && $_POST['saveUser'] == true){
    /*
     * Check for empty fields that are required
     */
    $error = false;

    if(empty($_POST['userHandle'])){ $sysMsg->addMessage("Username cannot be empty."); $error = true; }
    if(empty($_POST['userRank'])){ $sysMsg->addMessage("Rank cannot be empty."); $error = true; }
    if(empty($_POST['userEmail'])){ $sysMsg->addMessage("E-mail address cannot be empty."); $error = true; }
    if(empty($_POST['userFirstName'])){ $sysMsg->addMessage("First name cannot be empty."); $error = true; }
    if(empty($_POST['userLastName'])){ $sysMsg->addMessage("Last name cannot be empty."); $error = true; }
    if(empty($_POST['userBase'])){ $sysMsg->addMessage("Base cannot be empty.  If base is not listed, choose 'Other'."); $error = true; }
    if(empty($_POST['timeZone'])){ $sysMsg->addMessage("Time zone cannot be empty."); $error = true; }
    if(empty($_POST['userRole'])){ $sysMsg->addMessage("User role cannot be empty."); $error = true; }

    if($error){
        $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
    }

    /*
     * Check userHandle and userEmail to ensure collisions don't take place
     */
    if($_POST['userHandle'] != $objUser->getUserHandle()) {
        if ($user->getUUIDByHandle($_POST['userHandle'])) {
            $sysMsg->addMessage("That username is already in use.  Please choose a different one.");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if($_POST['userEmail'] != $objUser->getUserEmail()) {
        if ($user->getUUIDByEmail($_POST['userEmail'])) {
            $sysMsg->addMessage("That e-mail address is already in use.  Please choose a different one.");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if(!empty($_POST['user-pw-edit'])) {
        $complexityCheck = $cdcMastery->checkPasswordComplexity($_POST['user-pw-edit'], $_POST['userHandle'], $_POST['userEmail']);

        if (!is_array($complexityCheck)) {
            $objUser->setUserPassword($_POST['user-pw-edit']);
        } else {
            foreach ($complexityCheck as $complexityCheckError) {
                $sysMsg->addMessage($complexityCheckError);
            }

            $cdcMastery->redirect("/admin/users/" . $userUUID . "/edit");
        }
    }

    if(empty($_POST['userOfficeSymbol'])){
        $notListedOfficeSymbol = $officeSymbol->getOfficeSymbolByName("Not Listed");
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
    $objUser->setUserRole($_POST['userRole']);

    if($objUser->saveUser()){
        $log->setAction("USER_EDIT");
        $log->setDetail("User UUID",$userUUID);
        $log->saveEntry();

        $sysMsg->addMessage("User " . $objUser->getFullName() . " was edited successfully.");
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
    else{
        $log->setAction("ERROR_USER_EDIT");
        $log->setDetail("Error",$objUser->error);
        $log->setDetail("User UUID",$userUUID);

        foreach($_POST as $editFormKey => $editFormVal){
            $log->setDetail($editFormKey,$editFormVal);
        }

        $log->saveEntry();
        $sysMsg->addMessage("There was a problem saving the information for that user.  Please open a support ticket for assistance.");
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
                                $baseList = $bases->listBases();

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
                                $officeSymbolList = $officeSymbol->listOfficeSymbols();

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
                                $roleList = $roles->listRoles();
                                foreach($roleList as $roleUUID => $roleDetails): ?>
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