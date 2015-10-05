<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmCategoryAdd'])){
    $categoryName = isset($_POST['categoryName']) ? $_POST['categoryName'] : false;
    $categoryEncrypted = isset($_POST['categoryEncrypted']) ? $_POST['categoryEncrypted'] : false;
    $categoryType = isset($_POST['categoryType']) ? $_POST['categoryType'] : false;
    $categoryBinding = isset($_POST['categoryBinding']) ? $_POST['categoryBinding'] : false;
    $categoryComments = isset($_POST['categoryComments']) ? $_POST['categoryComments'] : false;

    $addError = false;

    if(!$categoryName){
        $sysMsg->addMessage("Category name cannot be empty.");
        $addError = true;
    }

    if(!isset($_POST['categoryEncrypted'])){
        $sysMsg->addMessage("You must choose a category encryption value (Yes/No).");
        $addError = true;
    }

    if(!$categoryType){
        $sysMsg->addMessage("You must choose a category type. (Global/Private)");
        $addError = true;
    }
    elseif($categoryType == "private" && !$categoryBinding){
        $sysMsg->addMessage("This category was marked private. You must choose a user to bind the category to.");
        $addError = true;
    }

    if(!$addError) {
        $flashCardManager->newFlashCardCategory();
        $flashCardManager->setCategoryName($categoryName);
        $flashCardManager->setCategoryEncrypted($categoryEncrypted);
        $flashCardManager->setCategoryType($categoryType);

        if($categoryType == "private"){
            $flashCardManager->setCategoryBinding($categoryBinding);
            $flashCardManager->setCategoryPrivate(true);
        }
        else{
            $flashCardManager->setCategoryPrivate(false);
        }

        $flashCardManager->setCategoryComments($categoryComments);
        $flashCardManager->setCategoryCreatedBy($_SESSION['userUUID']);

        if($flashCardManager->saveFlashCardCategory()){
            $sysMsg->addMessage("Flash card category added successfully.");

            unset($categoryName);
            unset($categoryEncrypted);
            unset($categoryType);
            unset($categoryBinding);
            unset($categoryComments);
        }
        else{
            $sysMsg->addMessage("The flash card category could not be added.  Contact the support help desk for assistance.");
        }
    }
}
?>
<script>
    $(document).ready(function(){
        $('#categoryBindingBlock').hide();

        $('#selectPrivate').click(function(){
            $('#categoryBindingBlock').show();
        });

        $('#selectGlobal').click(function(){
            $('#categoryBindingBlock').hide();
        });
    });
</script>
<section>
    <header>
        <h2>Add Flash Card Category</h2>
    </header>
    <form action="/admin/flash-card-categories/add" method="POST">
        <input type="hidden" name="confirmCategoryAdd" value="1">
        <p>
            Enter the details of the flash card category below. You may create a new category, or you can create one using existing CDC data by
            <a href="/admin/flash-card-categories/add-by-afsc">clicking here</a>.
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
                <input type="radio" name="categoryEncrypted" id="encryptedTrue" value="1"<?php if(isset($categoryEncrypted) && $categoryEncrypted == true): echo " CHECKED"; endif; ?>> Yes<br>
                <input type="radio" name="categoryEncrypted" id="encryptedFalse" value="0"<?php if(isset($categoryEncrypted) && $categoryEncrypted == false): echo " CHECKED"; elseif(!isset($categoryEncrypted)): echo " CHECKED"; endif; ?>> No
            </li>
            <li>
                <label for="categoryType">Type</label>
                <p>
                    There are two different category types.  <strong>Global</strong> will allow all users to utilize the data, and
                    <strong>Private</strong> will only allow a specified user to view the data.  Only utilize private categories for one-off scenarios,
                    or for when you know no one else will be utilizing the data.
                </p>
                <input type="radio" name="categoryType" id="selectGlobal" value="global"<?php if(isset($categoryType) && $categoryType == "global"): echo " CHECKED"; elseif(!isset($categoryType)): echo " CHECKED"; endif; ?>> Global<br>
                <input type="radio" name="categoryType" id="selectPrivate" value="private"<?php if(isset($categoryType) && $categoryType == "private"): echo " CHECKED"; endif; ?>> Private<br>
            </li>
            <li id="categoryBindingBlock">
                <label for="categoryBinding">Bind to user</label>
                <p>
                    Select the user to bind this category to.  After clicking on the drop-down list, type the first few letters of the user's last name
                    to jump to that user.  This field is required if "<strong>Private</strong>" is selected above.
                </p>
                <select id="categoryBinding"
                        name="categoryBinding"
                        class="input_full"
                        size="1">
                    <option value="">Select a user...</option>
                    <?php
                    $userList = $user->listUsers();
                    foreach($userList as $userUUID => $userDetails): ?>
                        <?php if(isset($categoryBinding) && $categoryBinding == $userUUID): ?>
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
            <li>
                <label for="categoryComments">Comments</label>
                <p>
                    Optional administrative comments for this category.  You may want to include the CDC version here as a reference.
                </p>
                <textarea class="input_full" name="categoryComments" id="categoryComments" style="height:8em;"><?php if(isset($categoryComments)): echo $categoryComments; endif; ?></textarea>
            </li>
        </ul>
        <input type="submit" value="Add Category">
    </form>
</section>