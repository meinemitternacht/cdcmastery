<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/2/2015
 * Time: 9:59 AM
 */

if(isset($_POST['confirmCategoryAFSCAdd'])){
    $afscUUID = isset($_POST['afscUUID']) ? $_POST['afscUUID'] : false;

    $addError = false;

    if(!$afscUUID){
        $sysMsg->addMessage("You must select an AFSC.","warning");
        $addError = true;
    }

    if(!$flashCardManager->checkCategoryBinding($afscUUID)){
        $sysMsg->addMessage("The flash card category for that AFSC has already been created.","warning");
        $addError = true;
    }

    if(!$addError) {
        $flashCardManager->newFlashCardCategory();

        if($flashCardManager->createCategoryFromAFSC($afscUUID,$afsc,$_SESSION['userUUID'])){
            $sysMsg->addMessage("Flash card category added successfully.","success");
        }
        else{
            $sysMsg->addMessage("The flash card category could not be added.  Contact the support help desk for assistance.","danger");
        }
    }
}

$afscList = $afsc->listAFSC(false);

foreach($afscList as $afscUUID => $afscData){
    if($flashCardManager->checkCategoryBinding($afscUUID)){
        $formAFSCList[$afscUUID] = $afscData['afscName'];
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
    <?php if(!empty($formAFSCList)): ?>
    <form action="/admin/flash-card-categories/add-afsc" method="POST">
        <input type="hidden" name="confirmCategoryAFSCAdd" value="1">
        <p>
            Enter the details of the flash card category below. You can create one without using existing CDC data by
            <a href="/admin/flash-card-categories/add">clicking here</a>.
        </p>
        <ul class="form-field-list">
            <li>
                <label for="afscUUID">AFSC</label>
                <p>
                    Choose the AFSC that this category will use to obtain data.
                </p>
                <select name="afscUUID" class="input_full" size="1">
                    <?php foreach($formAFSCList as $afscUUID => $afscName): ?>
                    <option value="<?php echo $afscUUID; ?>"><?php echo $afscName; ?></option>
                    <?php endforeach; ?>
                </select>
            </li>
        </ul>
        <input type="submit" value="Add Category">
    </form>
    <?php else: ?>
        All AFSC flash card categories have been previously created.
    <?php endif; ?>
</section>