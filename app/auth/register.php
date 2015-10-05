<?php
if(isset($_SESSION['auth'])){
    $sysMsg->addMessage("You are already registered.");
    $cdcMastery->redirect("/");
}

if(isset($_SESSION['vars'][0]) && !empty($_SESSION['vars'][0])) {
    $accountType = $_SESSION['vars'][0];
}
else{
    $accountType = false;
}

if(isset($_GET['referral'])){
    $_SESSION['referralID'] = $_GET['referral'];
}

if(isset($_SESSION['vars'][1]))
    $step = $_SESSION['vars'][1];

if(isset($_POST['registrationFormStep'])):
    $formStep = $_POST['registrationFormStep'];
    $error = false;

    if($formStep == 1){
        /*
         * Validate form and store data.
         */
        $registrationArray['userHandle']['data'] = isset($_POST['userHandle']) ? $_POST['userHandle'] : false;
        $registrationArray['userPassword']['data'] = isset($_POST['userPassword']) ? $_POST['userPassword'] : false;
        $registrationArray['userPassword_confirmation']['data'] = isset($_POST['userPassword_confirmation']) ? $_POST['userPassword_confirmation'] : false;
        $registrationArray['userTimeZone']['data'] = isset($_POST['timeZone']) ? $_POST['timeZone'] : false;
        $registrationArray['userRank']['data'] = isset($_POST['userRank']) ? $_POST['userRank'] : false;
        $registrationArray['userFirstName']['data'] = isset($_POST['userFirstName']) ? ucfirst($_POST['userFirstName']) : false;
        $registrationArray['userLastName']['data'] = isset($_POST['userLastName']) ? ucfirst($_POST['userLastName']) : false;
        $registrationArray['userEmail']['data'] = isset($_POST['userEmail']) ? $_POST['userEmail'] : false;
        $registrationArray['userBase']['data'] = isset($_POST['userBase']) ? $_POST['userBase'] : false;
        $registrationArray['userOfficeSymbol']['data'] = isset($_POST['userOfficeSymbol']) ? $_POST['userOfficeSymbol'] : false;

        $registrationArray['userHandle']['description'] = "Username";
        $registrationArray['userPassword']['description'] = "Password";
        $registrationArray['userPassword_confirmation']['description'] = "Password confirmation";
        $registrationArray['userTimeZone']['description'] = "Time Zone";
        $registrationArray['userRank']['description'] = "Rank";
        $registrationArray['userFirstName']['description'] = "First Name";
        $registrationArray['userLastName']['description'] = "Last Name";
        $registrationArray['userEmail']['description'] = "E-mail Address";
        $registrationArray['userBase']['description'] = "Base";
        $registrationArray['userOfficeSymbol']['description'] = "Office Symbol";

        $_SESSION['registrationArray'] = $registrationArray;

        foreach($registrationArray as $userAttributeKey => $userAttribute){
            if(empty($userAttribute['data'])){
                $sysMsg->addMessage($userAttribute['description'] . " cannot be blank.");
                $error = true;
            }
        }

        if($user->getUUIDByHandle($_SESSION['registrationArray']['userHandle']['data'])){
            $sysMsg->addMessage("That username is already in use.  Please choose a different one.");
            $error = true;
        }

        if($user->getUUIDByEmail($_SESSION['registrationArray']['userEmail']['data'])){
            $sysMsg->addMessage("That e-mail address is already in use.  Please choose a different one or reset your password by clicking the link on the login page.");
            $error = true;
        }

        if($registrationArray['userPassword']['data'] != $registrationArray['userPassword_confirmation']['data']){
            $sysMsg->addMessage("Your passwords do not match.");
            $error = true;
        }

        $complexityCheck = $cdcMastery->checkPasswordComplexity(
            $registrationArray['userPassword_confirmation']['data'],
            $registrationArray['userHandle']['data'],
            $registrationArray['userEmail']['data']
        );

        if (is_array($complexityCheck)) {
            foreach ($complexityCheck as $complexityCheckError) {
                $sysMsg->addMessage($complexityCheckError);
            }
            $error = true;
        }

        if(!$cdcMastery->checkEmailAddress($registrationArray['userEmail']['data'])){
            $sysMsg->addMessage("You did not provide a valid Air Force e-mail address.");
            $error = true;
        }

        if(!$error){
            $cdcMastery->redirect("/auth/register/" . $accountType . "/2");
        }
    }
    elseif($formStep == 2) {
        if(!isset($_SESSION['registrationArray'])){
            $cdcMastery->redirect("/auth/register");
        }

        $userAFSCList = isset($_POST['userAFSCList']) ? $_POST['userAFSCList'] : false;
        $userSupervisor = isset($_POST['userSupervisor']) ? $_POST['userSupervisor'] : false;
        $userTrainingManager = isset($_POST['userTrainingManager']) ? $_POST['userTrainingManager'] : false;

        if(empty($userAFSCList)){
            $sysMsg->addMessage("You must select at least one AFSC to be associated with.");
            $cdcMastery->redirect("/auth/register/" . $accountType . "/2");
        }

        $registerUser = new user($db,$log,$emailQueue);

        $registerUser->setUserHandle($_SESSION['registrationArray']['userHandle']['data']);
        $registerUser->setUserPassword($_SESSION['registrationArray']['userPassword']['data']);
        $registerUser->setUserEmail($_SESSION['registrationArray']['userEmail']['data']);
        $registerUser->setUserFirstName($_SESSION['registrationArray']['userFirstName']['data']);
        $registerUser->setUserLastName($_SESSION['registrationArray']['userLastName']['data']);
        $registerUser->setUserRank($_SESSION['registrationArray']['userRank']['data']);
        $registerUser->setUserTimeZone($_SESSION['registrationArray']['userTimeZone']['data']);
        $registerUser->setUserBase($_SESSION['registrationArray']['userBase']['data']);
        $registerUser->setUserOfficeSymbol($_SESSION['registrationArray']['userOfficeSymbol']['data']);
        $registerUser->setUserDateRegistered(date("Y-m-d H:i:s"),time());
        $registerUser->setUserRole($roles->getRoleUUIDByName("Users"));
        $registerUser->setUserDisabled(false);

        $_SESSION['timeZone'] = $registerUser->getUserTimeZone();

        $authorizationQueue = new userAuthorizationQueue($db,$log,$emailQueue);

        if($registerUser->saveUser()) {

            if ($accountType == "supervisor") {
                $authorizationQueue->queueRoleAuthorization($registerUser->getUUID(),$roles->getRoleUUIDByName("Supervisors"));
            }
            elseif ($accountType == "training-manager") {
                $authorizationQueue->queueRoleAuthorization($registerUser->getUUID(),$roles->getRoleUUIDByName("Training Managers"));
            }

            foreach ($userAFSCList as $afscUUID) {
                if ($afsc->loadAFSC($afscUUID)) {
                    if ($afsc->getAFSCFOUO()) {
                        $assoc->addPendingAFSCAssociation($registerUser->getUUID(), $afscUUID);
                    } else {
                        $assoc->addAFSCAssociation($registerUser->getUUID(), $afscUUID);
                    }
                }
            }

            if(!empty($_SESSION['referralID'])){
                if($roles->verifyUserRole($_SESSION['referralID']) == "supervisor"){
                    $assoc->addSupervisorAssociation($_SESSION['referralID'],$registerUser->getUUID());
                }
                elseif($roles->verifyUserRole($_SESSION['referralID']) == "trainingManager"){
                    $assoc->addTrainingManagerAssociation($_SESSION['referralID'],$registerUser->getUUID());
                }
            }

            if (!empty($userSupervisor)) {
                $registerUser->setUserSupervisor($userSupervisor);
                $assoc->addSupervisorAssociation($userSupervisor, $registerUser->getUUID());
            }

            if (!empty($userTrainingManager)) {
                $assoc->addTrainingManagerAssociation($userTrainingManager, $registerUser->getUUID());
            }

            $userActivation = new userActivation($db, $log, $emailQueue);

            if($userActivation->queueActivation($registerUser->getUUID())){
                $log->setAction("USER_REGISTER");
                $log->setUserUUID($registerUser->getUUID());
                $log->setDetail("User UUID",$registerUser->getUUID());
                $log->setDetail("User Name",$registerUser->getFullName());
                $log->setDetail("User Handle",$registerUser->getUserHandle());
                $log->saveEntry();

                $sysMsg->addMessage("Thank you for creating an account! An activation link will be sent to your e-mail address in the next few minutes. If you don't receive the link, open a ticket with our helpdesk by clicking the support link near the top of the page.");
                $_SESSION['queueActivation'] = true;
                $cdcMastery->redirect("/");
            }
            else {
                $log->setAction("ERROR_USER_REGISTER");
                $log->setUserUUID($registerUser->getUUID());

                foreach($_SESSION['registrationArray'] as $registrationArrayKey => $registrationSubArray){
                    $log->setDetail($registrationArrayKey,$registrationSubArray['data']);
                }

                $log->setDetail("Error Reason","Unable to queue user activation");
                $log->saveEntry();

                $sysMsg->addMessage("Something went wrong and we couldn't finish the registration process. The good news is that we were able to save most of your information, so just open a ticket with the helpdesk by clicking the support link near the top of the page and we'll get this sorted out as soon as possible.");

                $cdcMastery->redirect("/errors/500");
            }
        }
        else{
            $log->setAction("ERROR_USER_REGISTER");
            $log->setUserUUID($registerUser->getUUID());

            foreach($_SESSION['registrationArray'] as $registrationArrayKey => $registrationSubArray){
                $log->setDetail($registrationArrayKey,$registrationSubArray['data']);
            }

            $log->setDetail("Error reason","Unable to save user data");
            $log->saveEntry();

            $sysMsg->addMessage("Something went terribly wrong and we couldn't save your information.  We attempted to log most of it, so just open a ticket with the helpdesk by clicking the support link near the top of the page and we'll get this sorted out as soon as possible.");

            $cdcMastery->redirect("/errors/500");
        }
    }
endif;

if($accountType):
    if(isset($step) && $step == 2):
        if(!isset($_SESSION['registrationArray'])){
            $cdcMastery->redirect("/auth/register");
        }

        if($bases->loadBase($_SESSION['registrationArray']['userBase']['data'])):
            $supervisorList = $roles->listSupervisorsByBase($bases->getUUID());
            $trainingManagerList = $roles->listTrainingManagersByBase($bases->getUUID()); ?>
            <form id="registrationFormPartTwo" action="/auth/register/<?php echo $accountType; ?>/2" method="POST">
            <input type="hidden" name="registrationFormStep" value="2">
            <div class="container">
                <div class="row">
                    <div class="12u">
                        <section>
                            <header>
                                <h2>Create Account &raquo; Step 2</h2>
                            </header>
                        </section>
                    </div>
                </div>
                <div class="row">
                    <div class="3u">
                        <section class="registration-panel-1">
                            <header>
                                <h3>Select AFSC(s)</h3>
                            </header>
                            <p>Choose the AFSC's you would like to be associated with below.
                            You can select multiple items by holding the CTRL key while clicking.</p>
                            <label for="userAFSCList">AFSC List</label>
                            <br>
                            <select id="userAFSCList"
                                    name="userAFSCList[]"
                                    size="10"
                                    class="input_full"
                                    MULTIPLE>
                                <?php foreach($afsc->listAFSC(false) as $afscUUID => $afscDetails): ?>
                                    <option value="<?php echo $afscUUID; ?>">
                                        <?php
                                            echo $afscDetails['afscName'];
                                            if($afscDetails['afscFOUO']){ echo "*"; }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <br>
                            <em>An asterisk (*) denotes a FOUO AFSC</em>
                        </section>
                    </div>
                    <?php if($supervisorList): ?>
                    <div class="3u">
                        <section class="registration-panel-2">
                            <header>
                                <h3>Select Supervisor</h3>
                            </header>
                            <p>Choose the individual who is either your direct supervisor or someone that will be
                                tracking your progress.  This information is optional.  If your supervisor is not listed,
                                encourage them to create a supervisor account with us.</p>
                                <label for="userSupervisor">Supervisor</label>
                                <br>
                                <select id="userSupervisor"
                                        name="userSupervisor"
                                        size="1"
                                        class="input_full">
                                    <option value="">None Selected</option>
                                    <?php foreach($supervisorList as $supervisorUUID): ?>
                                        <option value="<?php echo $supervisorUUID; ?>">
                                            <?php echo $user->getUserNameByUUID($supervisorUUID); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        </section>
                    </div>
                    <?php endif; ?>
                    <?php if($trainingManagerList): ?>
                    <div class="3u">
                        <section class="registration-panel-3">
                            <header>
                                <h3>Select Training Manager</h3>
                            </header>
                            <p>Choose the individual who is either your direct training manager or someone that will be
                                tracking your shop's progress.  This information is optional and can be added at a later date.
                                If your training manager is not listed, encourage them to create a Training Manager account with
                                us.</p>
                                <label for="userTrainingManager">Training Manager</label>
                                <br>
                                <select id="userTrainingManager"
                                        name="userTrainingManager"
                                        size="1"
                                        class="input_full">
                                    <option value="">None Selected</option>
                                    <?php foreach($trainingManagerList as $trainingManagerUUID): ?>
                                        <option value="<?php echo $trainingManagerUUID; ?>">
                                            <?php echo $user->getUserNameByUUID($trainingManagerUUID); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        </section>
                    </div>
                    <?php endif; ?>
                    <div class="3u">
                        <section class="registration-panel-4">
                            <header>
                                <h3>Complete Registration</h3>
                            </header>
                            <p>That's all the information we need!  Click "Finish" below to complete
                            the registration process.  An activation e-mail will be sent to your provided e-mail
                            address containing a link to verify your information.
                            <br>
                            <br>
                            <strong>Please note:  If you selected a FOUO AFSC for your account, it will require approval
                            from a Training Manager or Administrator.  If you have not received access after 24 hours,
                            please open a ticket with the <a href="http://helpdesk.cdcmastery.com/">CDCMastery Helpdesk</a>.</strong></p>
                            <div class="sub-menu">
                                <ul>
                                    <li>
                                        <a id="createAccountButton">
                                            <i class="icon-inline icon-20 ic-arrow-right"></i>
                                            Finish
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="clearfix">&nbsp;</div>
                        </section>
                    </div>
                </div>
            </div>
            </form>
        <script>
        $(function() {
            $("#createAccountButton").click(function(){
                $("#registrationFormPartTwo").submit();
            });
        });
        </script>
        <?php else: ?>
            <div class="systemMessages">
                There was a problem loading data for the base you selected.  Please open a ticket
                at the <a href=\"http://helpdesk.cdcmastery.com/\">CDCMastery Helpdesk</a>.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <script type="text/javascript">

            $(document).ready(function () {
                $('#userHandle').change(function () {
                    var userHandle = $('#userHandle').val();
                    $.ajax({
                        type: "POST",
                        url: "/ajax/registration/checkHandle",
                        data: {'userHandle': userHandle },
                        success: function (response) {
                            if(response > 0) {
                                finishAjax('system-messages-block', '<ul><li><strong>That username is already in use.</strong></li></ul>');
                            }
                            else{
                                $('#system-messages-container-block').hide();
                            }
                        }
                    });

                    return false;

                });

                $('#userEmail').change(function () {
                    var userEmail = $('#userEmail').val();
                    $.ajax({
                        type: "POST",
                        url: "/ajax/registration/checkEmail",
                        data: {'userEmail': userEmail },
                        success: function (response) {
                            if(response != 0) {
                                finishAjax('system-messages-block', '<ul><li><strong>' + escape(response) + '</strong></li></ul>');
                            }
                            else{
                                $('#system-messages-container-block').hide();
                            }
                        }
                    });

                    return false;

                });

                $('#userEmail_confirmation').change(function () {
                    var userEmail = $('#userEmail_confirmation').val();
                    $.ajax({
                        type: "POST",
                        url: "/ajax/registration/checkEmail",
                        data: {'userEmail': userEmail },
                        success: function (response) {
                            if(response != 0) {
                                finishAjax('system-messages-block', '<ul><li><strong>' + escape(response) + '</strong></li></ul>');
                            }
                            else{
                                $('#system-messages-container-block').hide();
                            }
                        }
                    });

                    return false;

                });

            });

            function finishAjax(id, response) {
                $('#' + id).html(unescape(response));
                $('#system-messages-container-block').show();
            }

        </script>
        <form id="registrationForm" action="/auth/register/<?php echo $accountType; ?>" method="POST">
        <input type="hidden" name="registrationFormStep" value="1">
        <div class="container">
            <div class="row">
                <div class="12u">
                    <section>
                        <header>
                            <h2>Create Account &raquo; Step 1</h2>
                        </header>
                    </section>
                </div>
            </div>
            <div class="row">
                <div class="4u">
                    <section class="registration-panel-1">
                        <header>
                            <h3>Username / Password</h3>
                        </header>
                        <ul>
                            <li>
                                <label for="userHandle">Username</label>
                                <br>
                                <input id="userHandle"
                                       name="userHandle"
                                       type="text"
                                       class="input_full"
                                       <?php if(isset($_SESSION['registrationArray']['userHandle']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userHandle']['data']; ?>"
                                       <?php endif; ?>
                                       data-validation="length"
                                       data-validation-length="3-32"
                                       data-validation-error-msg="Your username must be between 3 and 32 characters">
                            </li>
                            <li>
                                <label for="userPassword_confirmation">Password</label>
                                <br>
                                <input id="userPassword_confirmation"
                                       name="userPassword_confirmation"
                                       type="password"
                                       class="input_full"
                                       <?php if(isset($_SESSION['registrationArray']['userPassword_confirmation']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userPassword_confirmation']['data']; ?>"
                                       <?php endif; ?>
                                       data-validation="strength"
                                       data-validation-strength="2">
                            </li>
                            <li>
                                <label for="userPassword">Confirm Password</label>
                                <br>
                                <input id="userPassword"
                                       name="userPassword"
                                       type="password"
                                       class="input_full"
                                       <?php if(isset($_SESSION['registrationArray']['userPassword']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userPassword']['data']; ?>"
                                       <?php endif; ?>
                                       data-validation="confirmation"
                                       data-validation-error-msg="The passwords do not match">
                            </li>
                        </ul>
                    </section>
                    <section class="registration-panel-2">
                        <header>
                            <h3>Time Zone</h3>
                        </header>
                        <ul>
                            <li>
                                <label for="timeZone">Time Zone</label>
                                <br>
                                <?php $tzList = $cdcMastery->listTimeZones(); ?>
                                <select id="timeZone"
                                        name="timeZone"
                                        size="1"
                                        class="input_full"
                                        data-validation="required"
                                        data-validation-error-msg="You must select a time zone">
                                    <option value="">Select Time Zone...</option>
                                    <?php foreach($tzList as $tzGroup): ?>
                                        <?php foreach($tzGroup as $tz): ?>
                                            <?php if(isset($_SESSION['registrationArray']['userTimeZone']['data']) && $_SESSION['registrationArray']['userTimeZone']['data'] == $tz): ?>
                                            <option value="<?php echo $tz; ?>" SELECTED><?php echo $tz; ?></option>
                                            <?php else: ?>
                                            <option value="<?php echo $tz; ?>"><?php echo $tz; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                            </li>
                        </ul>
                    </section>
                </div>
                <div class="4u">
                    <section class="registration-panel-3">
                        <header>
                            <h3>Your Details</h3>
                        </header>
                        <ul>
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
                                                <?php if(isset($_SESSION['registrationArray']['userRank']['data']) && $_SESSION['registrationArray']['userRank']['data'] == $rankKey): ?>
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
                                       <?php if(isset($_SESSION['registrationArray']['userFirstName']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userFirstName']['data']; ?>"
                                       <?php endif; ?>
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
                                       <?php if(isset($_SESSION['registrationArray']['userLastName']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userLastName']['data']; ?>"
                                       <?php endif; ?>
                                       data-validation="required"
                                       data-validation-error-msg="You must provide your last name">
                            </li>
                            <li>
                                <label for="userEmail_confirmation">E-mail</label>
                                <br>
                                <input id="userEmail_confirmation"
                                       name="userEmail_confirmation"
                                       type="text"
                                       class="input_full"
                                       <?php if(isset($_SESSION['registrationArray']['userEmail_confirmation']['data'])): ?>
                                       value="<?php echo $_SESSION['registrationArray']['userEmail_confirmation']['data']; ?>"
                                       <?php endif; ?>
                                       data-validation="email">
                            </li>
                            <li>
                                <label for="userEmail">Confirm E-mail</label>
                                <br>
                                <input id="userEmail"
                                       name="userEmail"
                                       type="text"
                                       class="input_full"
                                    <?php if(isset($_SESSION['registrationArray']['userEmail']['data'])): ?>
                                        value="<?php echo $_SESSION['registrationArray']['userEmail']['data']; ?>"
                                    <?php endif; ?>
                                       data-validation="confirmation"
                                       data-validation-error-msg="The e-mail addresses do not match">
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
                                        <?php if(isset($_SESSION['registrationArray']['userBase']['data']) && $_SESSION['registrationArray']['userBase']['data'] == $baseUUID): ?>
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
                                        <?php if(isset($_SESSION['registrationArray']['userOfficeSymbol']['data']) && $_SESSION['registrationArray']['userOfficeSymbol']['data'] == $officeSymbolUUID): ?>
                                            <option value="<?php echo $officeSymbolUUID; ?>" SELECTED><?php echo $officeSymbolName; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $officeSymbolUUID; ?>"><?php echo $officeSymbolName; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </li>
                        </ul>
                    </section>
                </div>
                <div class="4u">
                    <section class="registration-panel-4">
                        <header>
                            <h3>Continue</h3>
                        </header>
                        <p>When you've finished with this part, click "Next Step" to select
                        your AFSC and supervisor/training manager.</p>
                        <div class="sub-menu">
                            <ul>
                                <li><a id="regFormStep2"><i class="icon-inline icon-20 ic-arrow-right"></i> Next Step</a></li>
                            </ul>
                        </div>
                        <div class="clearfix">&nbsp;</div>
                    </section>
                </div>
            </div>
        </div>
        </form>
        <script src="/js/form-validator/jquery.form-validator.min.js"></script>
        <script>
            $(function() {
                $("#regFormStep2").click(function(){
                    $("#registrationForm").submit();
                });
            });

            $.validate({
                modules : 'security',
                onModulesLoaded : function() {
                    var optionalConfig = {
                        fontSize: '12pt',
                        padding: '4px',
                        bad : 'Very bad',
                        weak : 'Weak',
                        good : 'Good',
                        strong : 'Strong'
                    };

                    $('input[name="userPassword_confirmation"]').displayPasswordStrength(optionalConfig);
                }
            });
        </script>
        <?php endif; ?>
<?php else: ?>
<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h2>Before you begin</h2>
				</header>
				<ul>
					<li><i class="icon-inline icon-20 ic-lightbulb"></i>You must register with an e-mail address ending with ".mil"</li>
					<li><i class="icon-inline icon-20 ic-lightbulb"></i>Only one account may be registered per e-mail address</li>
					<li><i class="icon-inline icon-20 ic-lightbulb"></i>You may change your account type at any time by sending a message to our support team</li>
					<li><i class="icon-inline icon-20 ic-lightbulb"></i><strong>Supervisor and Training manager accounts require approval. Your account will have user permissions until approval occurs</strong></li>
				</ul>
			</section>
		</div>
	</div>
	<div class="row text-center">
		<div class="4u">
			<section id="reg-user" style="border-bottom: 6px solid #693;">
				<header>
					<h2>User Account</h2>
				</header>
				<p>Choose this account if you are not a supervisor or training manager.</p>
				<br>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/auth/register/user">Create user account &raquo;</a></li>
                    </ul>
                </div>
                <div class="clearfix">&nbsp;</div>
			</section>
		</div>
		<div class="4u">
			<section style="border-bottom: 6px solid #369;">
				<header>
					<h2>Supervisor Account</h2>
				</header>
				<p>Select this account if you require an overview of your subordinates.</p>
				<br>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/auth/register/supervisor">Create Supervisor Account &raquo;</a></li>
                    </ul>
                </div>
                <div class="clearfix">&nbsp;</div>
			</section>
		</div>
		<div class="4u">
			<section style="border-bottom: 6px solid #933;">
				<header>
					<h2>Training Manager Account</h2>
				</header>
				<p>Choose this account to manage questions and answers, as well as view subordinate progress.</p>
				<br>
				<div class="sub-menu">
                    <ul>
                        <li><a href="/auth/register/training-manager">Create Training Manager Account &raquo;</a></li>
                    </ul>
				</div>
                <div class="clearfix">&nbsp;</div>
			</section>
		</div>
	</div>
</div>
<?php endif; ?>