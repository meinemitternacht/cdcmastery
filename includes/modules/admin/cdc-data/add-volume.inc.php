<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/22/15
 * Time: 10:01 PM
 */

if(!empty($_POST)){
    if(isset($_POST['confirmVolumeAdd']) && $_POST['confirmVolumeAdd'] == true){
        if(!isset($_POST['volumeName']) || empty($_POST['volumeName'])){
            $sysMsg->addMessage("Volume Name cannot be empty.");
            $cdcMastery->redirect("/admin/cdc-data/" . $workingAFSC . "/add-volume/" . $workingChild);
        }
        else{
            $volumeName = $_POST['volumeName'];
        }

        if(empty($_POST['volumeVersion'])){
            $volumeVersion = " ";
        }
        else{
            $volumeVersion = $_POST['volumeVersion'];
        }

        if($qManager->addVolume($volumeName,$volumeVersion,$workingAFSC,$workingChild)){
            $sysMsg->addMessage("Volume added.");
            $cdcMastery->redirect("/admin/cdc-data/" . $workingAFSC);
        }
        else{
            $sysMsg->addMessage("There was a problem adding the volume.  The error has been logged.");
            $cdcMastery->redirect("/admin/cdc-data" . $workingAFSC . "/add-volume/" . $workingChild);
        }
    }
}
?>
<div class="9u">
    <section>
        <header>
            <h2>Add AFSC Volume</h2>
        </header>
        <form action="/admin/cdc-data/<?php echo $workingAFSC; ?>/add-volume/<?php echo $workingChild; ?>" method="POST">
            <input type="hidden" name="confirmVolumeAdd" value="1">
            <p>
                Enter the name and version of the volume below and click Add Volume to continue.
            </p>
            <label for="volumeName">Volume Name (required)</label>
            <input type="text" class="input_full" name="volumeName" maxlength="255">
            <br>
            <br>
            <label for="volumeVersion">Volume Version</label>
            <br>
            <em>Example: 2W151B 01 0905, Edit Code 04.  The "01" after "2W151B" is the volume number.</em>
            <input type="text" class="input_full" name="volumeVersion" maxlength="255">
            <br>
            <br>
            <input type="submit" value="Add Volume">
        </form>
    </section>
</div>