<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmCategoryEdit'])){
    $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : false;
    $categoryEncrypted = isset($_POST['categoryEncrypted']) ? $_POST['categoryEncrypted'] : false;
    $categoryType = isset($_POST['categoryType']) ? $_POST['categoryType'] : false;
    $categoryBindingUser = isset($_POST['categoryBindingUser']) ? $_POST['categoryBindingUser'] : false;
    $categoryBindingAFSC = isset($_POST['categoryBindingAFSC']) ? $_POST['categoryBindingAFSC'] : false;
    $categoryComments = isset($_POST['categoryComments']) ? $_POST['categoryComments'] : false;

    $editError = false;

    if(!$categoryName){
        $sysMsg->addMessage("Category name cannot be empty.");
        $editError = true;
    }

    if(!isset($_POST['categoryEncrypted'])){
        $sysMsg->addMessage("You must choose a category encryption value (Yes/No).");
        $editError = true;
    }

    if(!$categoryType){
        $sysMsg->addMessage("You must choose a category type. (AFSC/Global/Private)");
        $editError = true;
    }
    elseif($categoryType == "private" && !$categoryBindingUser){
        $sysMsg->addMessage("This category was marked private. You must choose a user to bind the category to.");
        $editError = true;
    }
    elseif($categoryType == "afsc" && !$categoryBindingAFSC){
        $sysMsg->addMessage("This category was marked as URE Bound. You must choose an AFSC to bind the category to.");
        $editError = true;
    }

    if(($categoryType == "afsc") && ($flashCardManager->getCategoryType() != "afsc")){
        /*
         * Check to see if another category is already bound to the select AFSC
         */
        if ($flashCardManager->checkCategoryBinding($categoryBindingAFSC)) {
            $sysMsg->addMessage("There is already a URE Bound category associated with the selected AFSC.  There can only be, at most, one.");
            $editError = true;
        }
    }

    /*
     * Delete flash card data if changed to AFSC
     */
    if($categoryType == "afsc" && $flashCardManager->getCategoryType() != "afsc"){
        if(!$flashCardManager->deleteCategoryFlashCardData($workingChild)){
            $sysMsg->addMessage("We could not delete the existing data for that flash card category.  Please contact the support help desk for assistance.");
            $editError = true;
        }
    }

    if(!$editError) {
        $flashCardManager->setCategoryName($categoryName);
        $flashCardManager->setCategoryEncrypted($categoryEncrypted);
        $flashCardManager->setCategoryType($categoryType);

        if($categoryType == "private"){
            $flashCardManager->setCategoryBinding($categoryBindingUser);
            $flashCardManager->setCategoryPrivate(true);
        }
        else{
            if(!empty($categoryBindingAFSC)){
                $flashCardManager->setCategoryBinding($categoryBindingAFSC);
            }
            $flashCardManager->setCategoryPrivate(false);
        }

        $flashCardManager->setCategoryComments($categoryComments);
        $flashCardManager->setCategoryCreatedBy($_SESSION['userUUID']);

        if($flashCardManager->saveFlashCardCategory()){
            $log->setAction("FLASH_CARD_CATEGORY_EDIT");
            $log->setDetail("Category UUID",$flashCardManager->getCategoryUUID());
            $log->saveEntry();

            $sysMsg->addMessage("Flash card category edited successfully.");

            unset($categoryName);
            unset($categoryEncrypted);
            unset($categoryType);
            unset($categoryBindingAFSC);
            unset($categoryBindingUser);
            unset($categoryComments);
        }
        else{
            $sysMsg->addMessage("The flash card category could not be edited.  Contact the support help desk for assistance.");
        }
    }
}

$categoryType = $flashCardManager->getCategoryType();
$categoryName = $flashCardManager->getCategoryName();
$categoryEncrypted = $flashCardManager->getCategoryEncrypted();
$categoryComments = $flashCardManager->getCategoryComments();

if($categoryType == "afsc" || $categoryType == "global"){
    $categoryBindingAFSC = $flashCardManager->getCategoryBinding();
}
elseif($categoryType == "private"){
    $categoryBindingUser = $flashCardManager->getCategoryBinding();
}
?>
<script>
    $(document).ready(function(){
        <?php if($categoryType == "afsc" || $categoryType == "global"): ?>
        $('#categoryBindingBlockUser').hide();
        <?php elseif($categoryType == "private"): ?>
        $('#categoryBindingBlockAFSC').hide();
        <?php endif; ?>

        $('#selectPrivate').click(function(){
            $('#categoryBindingBlockUser').show();
            $('#categoryBindingBlockAFSC').hide();
        });

        $('#selectGlobal').click(function(){
            $('#categoryBindingBlockUser').hide();
            $('#categoryBindingBlockAFSC').show();
        });

        $('#selectAFSC').click(function(){
            $('#categoryBindingBlockUser').hide();
            $('#categoryBindingBlockAFSC').show();
        });
    });
</script>
<section>
    <header>
        <h2>Edit Flash Card Category <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <form action="/admin/flash-card-categories/edit/<?php echo $workingChild; ?>" method="POST">
        <input type="hidden" name="confirmCategoryEdit" value="1">
        <p>
            Change the parameters of the flash card category below.  Note:  If you change the category type to AFSC and there is flash card data in the database,
            <span class="text-warning-bold">the data will be removed!</span>
        </p>
        <ul class="form-field-list">
            <li>
                <label for="categoryName">Name</label>
                <p>
                    Enter a name for the Flash Card Category.  This should be a unique identifier that clearly conveys the content within the category.
                    <em>e.g. 2W151A Self Test Questions</em>
                </p>
                <input type="text" class="input_full" name="categoryName" id="categoryName" maxlength="255"<?php if(isset($categoryName)): echo ' value="'.$categoryName.'"'; endif; ?>>
            </li>
            <li>
                <label for="categoryEncrypted">Encrypt Data</label>
                <p>
                    If set to "Yes", the data for this category will be encrypted in the database.  Use this for FOUO CDC data.
                </p>
                <input type="radio" name="categoryEncrypted" id="encryptedTrue" value="1"<?php if(isset($categoryEncrypted) && $categoryEncrypted == true): echo " CHECKED"; endif; ?> DISABLED> Yes<br>
                <input type="radio" name="categoryEncrypted" id="encryptedFalse" value="0"<?php if(isset($categoryEncrypted) && $categoryEncrypted == false): echo " CHECKED"; elseif(!isset($categoryEncrypted)): echo " CHECKED"; endif; ?> DISABLED> No
            </li>
            <li>
                <label for="categoryType">Type</label>
                <p>
                    There are two different category types.  <strong>Global</strong> will allow all users to utilize the data, and
                    <strong>Private</strong> will only allow a specified user to view the data.  Only utilize private categories for one-off scenarios,
                    or for when you know no one else will be utilizing the data. <strong>URE Bound</strong> will only show question and answer data from
                    the normal testing database (unit review exercies).  There can be only one URE-bound flash card category per AFSC.
                </p>
                <input type="radio" name="categoryType" id="selectGlobal" value="global"<?php if(isset($categoryType) && $categoryType == "global"): echo " CHECKED"; elseif(!isset($categoryType)): echo " CHECKED"; endif; ?>> Global<br>
                <input type="radio" name="categoryType" id="selectPrivate" value="private"<?php if(isset($categoryType) && $categoryType == "private"): echo " CHECKED"; endif; ?>> Private<br>
                <input type="radio" name="categoryType" id="selectAFSC" value="afsc"<?php if(isset($categoryType) && $categoryType == "afsc"): echo " CHECKED"; endif; ?>> URE Bound
            </li>
            <li id="categoryBindingBlockUser">
                <label for="categoryBindingUser">Bind to user</label>
                <p>
                    Select the user to bind this category to.  After clicking on the drop-down list, type the first few letters of the user's last name
                    to jump to that user.  This field is required if "<strong>Private</strong>" is selected above.
                </p>
                <select id="categoryBindingUser"
                        name="categoryBindingUser"
                        class="input_full"
                        size="1">
                    <option value="">Select a user...</option>
                    <?php
                    $userList = $user->listUsers();
                    foreach($userList as $userUUID => $userDetails): ?>
                        <?php if(isset($categoryBindingUser) && $categoryBindingUser == $userUUID): ?>
                            <option value="<?php echo $userUUID; ?>" SELECTED>
                                <?php echo $userDetails['userLastName'] . ", " . $userDetails['userFirstName'] . " " . $userDetails['userRank']; ?>
                            </option>
                        <?php else: ?>
                            <option value="<?php echo $userUUID; ?>">
                                <?php echo $userDetails['userLastName'] . ", " . $userDetails['userFirstName'] . " " . $userDetails['userRank']; ?>
                            </option>
                        <?php endif; ?>
                        <?php
                    endforeach;
                    ?>
                </select>
            </li>
            <li id="categoryBindingBlockAFSC">
                <label for="categoryBindingAFSC">Bind to AFSC</label>
                <p>
                    Select the AFSC to bind this category to.  <span class="text-warning-bold">While this field is not required, if an AFSC is not selected here, FOUO materials may be accessible to
                    unauthorized users.</span>
                </p>
                <select id="categoryBindingAFSC"
                        name="categoryBindingAFSC"
                        class="input_full"
                        size="1">
                    <option value="">Select an AFSC...</option>
                    <?php
                    $afscList = $afsc->listAFSC(false);
                    foreach($afscList as $afscUUID => $afscData): ?>
                        <?php if(isset($categoryBindingAFSC) && $categoryBindingAFSC == $afscUUID): ?>
                            <option value="<?php echo $afscUUID; ?>" SELECTED>
                                <?php echo $afscData['afscName']; ?>
                            </option>
                        <?php else: ?>
                            <option value="<?php echo $afscUUID; ?>">
                                <?php echo $afscData['afscName']; ?>
                            </option>
                        <?php endif; ?>
                        <?php
                    endforeach;
                    ?>
                </select>
            </li>
            <li>
                <label for="categoryComments">Comments</label>
                <p>
                    Optional administrative comments for this category.  You may want to include the CDC version here as a reference.
                </p>
                <textarea class="input_full" name="categoryComments" id="categoryComments" style="height:8em;"><?php if(isset($categoryComments)): echo $categoryComments; endif; ?></textarea>
            </li>
        </ul>
        <input type="submit" value="Edit Category">
    </form>
</section>
