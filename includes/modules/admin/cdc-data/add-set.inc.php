<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/22/15
 * Time: 10:01 PM
 */

if(!empty($_POST)){
    if(isset($_POST['confirmSetAdd']) && $_POST['confirmSetAdd'] == true){
        if(!isset($_POST['setName']) || empty($_POST['setName'])){
            $sysMsg->addMessage("Set Name cannot be empty.","warning");
            $cdcMastery->redirect("/admin/cdc-data/" . $workingAFSC . "/add-set");
        }

        if($qManager->addSet($_POST['setName'],$workingAFSC)){
            $sysMsg->addMessage("Set added.","success");
            $cdcMastery->redirect("/admin/cdc-data/" . $workingAFSC);
        }
        else{
            $sysMsg->addMessage("There was a problem adding the set.  The error has been logged.","danger");
            $cdcMastery->redirect("/admin/cdc-data" . $workingAFSC . "/add-set");
        }
    }
}
?>
<div class="9u">
    <section>
        <header>
            <h2>Add AFSC Set</h2>
        </header>
        <form action="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-set" method="POST">
            <input type="hidden" name="confirmSetAdd" value="1">
            <p>
                Enter the name of the set below.  You may then add volumes as children of this set and associate questions
                with them.
            </p>
            <label for="setName">Set Name</label>
            <input type="text" class="input_full" name="setName" maxlength="255">
            <br>
            <br>
            <input type="submit" value="Add Set">
        </form>
    </section>
</div>