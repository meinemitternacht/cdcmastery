<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/20/15
 * Time: 7:09 PM
 */

$userManager->loadUser($_SESSION['userUUID']);

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
    if(empty($_POST['userBase'])){ $systemMessages->addMessage("Base cannot be empty.  If your base is not listed, choose \"Other\".", "warning"); $error = true; }
    if(empty($_POST['timeZone'])){ $systemMessages->addMessage("Time zone cannot be empty.", "warning"); $error = true; }

    if($error){
        $cdcMastery->redirect("/user/edit");
    }

    /*
     * Check userHandle and userEmail to ensure collisions don't take place
     */
    if($_POST['userHandle'] != $userManager->getUserHandle()) {
        if ($userManager->getUUIDByHandle($_POST['userHandle'])) {
            $systemMessages->addMessage("That username is already in use.  Please choose a different one.", "warning");
            $cdcMastery->redirect("/user/edit");
        }
    }

    if($_POST['userEmail'] != $userManager->getUserEmail()) {
        if ($userManager->getUUIDByEmail($_POST['userEmail'])) {
            $systemMessages->addMessage("That e-mail address is already in use.  Please choose a different one.", "warning");
            $cdcMastery->redirect("/user/edit");
        }
    }

    if(!empty($_POST['user-pw-edit'])) {
        $complexityCheck = $cdcMastery->checkPasswordComplexity($_POST['user-pw-edit'], $_POST['userHandle'], $_POST['userEmail']);

        if (!is_array($complexityCheck)) {
            $userManager->setUserPassword($_POST['user-pw-edit']);
        } else {
            foreach ($complexityCheck as $complexityCheckError) {
                $systemMessages->addMessage($complexityCheckError, "warning");
            }

            $cdcMastery->redirect("/user/edit");
        }
    }

    if(empty($_POST['userOfficeSymbol'])){
        $notListedOfficeSymbol = $officeSymbolManager->getOfficeSymbolByName("Not Listed");
        if($notListedOfficeSymbol){
            $userManager->setUserOfficeSymbol($notListedOfficeSymbol);
        }
    }
    else{
        $userManager->setUserOfficeSymbol($_POST['userOfficeSymbol']);
    }

    $userManager->setUserEmail($_POST['userEmail']);
    $userManager->setUserHandle($_POST['userHandle']);
    $userManager->setUserBase($_POST['userBase']);
    $userManager->setUserFirstName($_POST['userFirstName']);
    $userManager->setUserLastName($_POST['userLastName']);
    $userManager->setUserRank($_POST['userRank']);
    $userManager->setUserTimeZone($_POST['timeZone']);

    if($userManager->saveUser()){
        $systemLog->setAction("USER_EDIT");
        $systemLog->setDetail("User UUID", $_SESSION['userUUID']);
        $systemLog->saveEntry();

        $systemMessages->addMessage("Your profile was edited successfully.", "success");
        $cdcMastery->redirect("/user/edit");
    }
    else{
        $systemLog->setAction("ERROR_USER_EDIT");
        $systemLog->setDetail("Error", $userManager->error);
        $systemLog->setDetail("User UUID", $_SESSION['userUUID']);

        foreach($_POST as $editFormKey => $editFormVal){
            $systemLog->setDetail($editFormKey, $editFormVal);
        }

        $systemLog->saveEntry();
        $systemMessages->addMessage("There was a problem saving your profile information.  Please open a support ticket for assistance.", "danger");
        $cdcMastery->redirect("/user/edit");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><em><?php echo $userManager->getFullName(); ?></em></h2>
                </header>
            </section>
            <div class="sub-menu">
                <div class="menu-heading">
                    Edit User
                </div>
                <ul>
                    <li><a href="/user/profile"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to profile</a></li>
                </ul>
            </div>
        </div>
        <div class="8u">
            <section>
                <header>
                    <h2>Edit User</h2>
                </header>
                <p><em>Note: Only enter a new password if it needs to be changed.  If left blank, your password will not be changed.</em></p>
                <form action="/user/edit" method="POST">
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
                                    data-validation-error-msg="You must select your rank">
                                <option value="">Select rank...</option>
                                <?php
                                $rankList = $cdcMastery->listRanks();
                                foreach($rankList as $rankGroupLabel => $rankGroup){
                                    echo '<optgroup label="'.$rankGroupLabel.'">';
                                    foreach($rankGroup as $rankOrder){
                                        foreach($rankOrder as $rankKey => $rankVal): ?>
                                            <?php if($userManager->getUserRank() == $rankKey): ?>
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
                                   value="<?php echo $userManager->getUserFirstName(); ?>"
                                   data-validation="required"
                                   data-validation-error-msg="You must provide your first name">
                        </li>
                        <li>
                            <label for="userLastName">Last Name</label>
                            <br>
                            <input id="userLastName"
                                   name="userLastName"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $userManager->getUserLastName(); ?>"
                                   data-validation="required"
                                   data-validation-error-msg="You must provide your last name">
                        </li>
                        <li>
                            <label for="userEmail">E-mail</label>
                            <br>
                            <input id="userEmail"
                                   name="userEmail"
                                   type="text"
                                   class="input_full"
                                   value="<?php echo $userManager->getUserEmail(); ?>"
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
                                    data-validation-error-msg="You must provide your base">
                                <option value="">Select base...</option>
                                <?php
                                $baseList = $baseManager->listBases();

                                foreach($baseList as $baseUUID => $baseName): ?>
                                    <?php if($userManager->getUserBase() == $baseUUID): ?>
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
                                    <?php if($userManager->getUserOfficeSymbol() == $officeSymbolUUID): ?>
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
                                   value="<?php echo $userManager->getUserHandle(); ?>"
                                   data-validation="length"
                                   data-validation-length="3-32"
                                   data-validation-error-msg="Your username must be between 3 and 32 characters">
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
                                        <?php if($userManager->getUserTimeZone() == $tz): ?>
                                            <option value="<?php echo $tz; ?>" SELECTED><?php echo $tz; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $tz; ?>"><?php echo $tz; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </li>
                    </ul>
                    <input type="submit" value="Save">
                </form>
                <div class="clearfix">&nbsp;</div>
                <a href="/user/disable" class="text-warning">Disable my account</a>
            </section>
        </div>
    </div>
</div>