<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/20/15
 * Time: 7:09 PM
 */

$user->loadUser($_SESSION['userUUID']);

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
    if(empty($_POST['userBase'])){ $sysMsg->addMessage("Base cannot be empty.  If base is not listed, choose \"Other\"."); $error = true; }
    if(empty($_POST['timeZone'])){ $sysMsg->addMessage("Time zone cannot be empty."); $error = true; }

    if($error){
        $cdcMastery->redirect("/user/edit");
    }

    /*
     * Check userHandle and userEmail to ensure collisions don't take place
     */
    if($_POST['userHandle'] != $user->getUserHandle()) {
        if ($user->getUUIDByHandle($_POST['userHandle'])) {
            $sysMsg->addMessage("That username is already in use.  Please choose a different one.");
            $cdcMastery->redirect("/user/edit");
        }
    }

    if($_POST['userEmail'] != $user->getUserEmail()) {
        if ($user->getUUIDByEmail($_POST['userEmail'])) {
            $sysMsg->addMessage("That e-mail address is already in use.  Please choose a different one.");
            $cdcMastery->redirect("/user/edit");
        }
    }

    if(!empty($_POST['user-pw-edit'])) {
        $complexityCheck = $cdcMastery->checkPasswordComplexity($_POST['user-pw-edit'], $_POST['userHandle'], $_POST['userEmail']);

        if (!is_array($complexityCheck)) {
            $user->setUserPassword($_POST['user-pw-edit']);
        } else {
            foreach ($complexityCheck as $complexityCheckError) {
                $sysMsg->addMessage($complexityCheckError);
            }

            $cdcMastery->redirect("/user/edit");
        }
    }

    if(empty($_POST['userOfficeSymbol'])){
        $notListedOfficeSymbol = $officeSymbol->getOfficeSymbolByName("Not Listed");
        if($notListedOfficeSymbol){
            $user->setUserOfficeSymbol($notListedOfficeSymbol);
        }
    }
    else{
        $user->setUserOfficeSymbol($_POST['userOfficeSymbol']);
    }

    $user->setUserEmail($_POST['userEmail']);
    $user->setUserHandle($_POST['userHandle']);
    $user->setUserBase($_POST['userBase']);
    $user->setUserFirstName($_POST['userFirstName']);
    $user->setUserLastName($_POST['userLastName']);
    $user->setUserRank($_POST['userRank']);
    $user->setUserTimeZone($_POST['timeZone']);

    if($user->saveUser()){
        $log->setAction("USER_EDIT");
        $log->setDetail("User UUID",$_SESSION['userUUID']);
        $log->saveEntry();

        $sysMsg->addMessage("Your profile was edited successfully.");
        $cdcMastery->redirect("/user/edit");
    }
    else{
        $log->setAction("ERROR_USER_EDIT");
        $log->setDetail("Error",$user->error);
        $log->setDetail("User UUID",$_SESSION['userUUID']);

        foreach($_POST as $editFormKey => $editFormVal){
            $log->setDetail($editFormKey,$editFormVal);
        }

        $log->saveEntry();
        $sysMsg->addMessage("There was a problem saving your profile information.  Please open a support ticket for assistance.");
        $cdcMastery->redirect("/user/edit");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><em><?php echo $user->getFullName(); ?></em></h2>
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
                                            <?php if($user->getUserRank() == $rankKey): ?>
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
                                   value="<?php echo $user->getUserFirstName(); ?>"
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
                                   value="<?php echo $user->getUserLastName(); ?>"
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
                                   value="<?php echo $user->getUserEmail(); ?>"
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
                                $baseList = $bases->listBases();

                                foreach($baseList as $baseUUID => $baseName): ?>
                                    <?php if($user->getUserBase() == $baseUUID): ?>
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
                                    <?php if($user->getUserOfficeSymbol() == $officeSymbolUUID): ?>
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
                                   value="<?php echo $user->getUserHandle(); ?>"
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
                                        <?php if($user->getUserTimeZone() == $tz): ?>
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