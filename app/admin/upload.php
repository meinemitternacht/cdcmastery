<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 5/20/15
 * Time: 7:37 PM
 */

if(!empty($_POST)){
    if($_FILES['fileField']['error'] > 0){
        $sysMsg->addMessage("Upload error " . $_FILES['fileField']['error']);

        $log->setAction("ERROR_FILE_UPLOAD");
        $log->setDetail("Error",$_FILES['fileField']['error']);
        $log->saveEntry();
    }
    else{
        $fileName = date("YmdHis",time()) . "_" . $_FILES['fileField']['name'];

        if(!move_uploaded_file($_FILES['fileField']['tmp_name'], BASE_PATH . "/uploads/" . $fileName)){
            $log->setAction("ERROR_FILE_UPLOAD");
            $log->setDetail("Error","Could not move file after upload. Check permissions.");
            $log->setDetail("File Name",$fileName);
            $log->saveEntry();

            $sysMsg->addMessage($fileName . " could not be uploaded.  Please open a support ticket.");
        }
        else{
            $log->setAction("FILE_UPLOAD");
            $log->setDetail("File Name",$fileName);
            $log->setDetail("File Description",$_POST['fileDescription']);
            $log->saveEntry();
        }

        $sysMsg->addMessage($fileName . " uploaded successfully.");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Upload Files</h2>
                </header>
                <p>
                    Use this form to upload large files to CDCMastery and notify the administrator of their upload.  Common
                    uses are PDF's of CDC data or other attachments too large for e-mail.  If you encounter issues, please
                    open a support ticket.
                </p>
            </section
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <div class="clearfix"><br></div>
                <form action="/admin/upload" method="POST" enctype="multipart/form-data">
                    <label for="fileField">File to upload:</label><br>
                    <input type="file" name="fileField" id="fileField" />
                    <br>
                    <label for="fileDescription">File description:</label><br>
                    <input type="text" name="fileDescription" id="fileDescription" size="30" />
                    <br>
                    <br>
                    <input type="submit" name="submit" value="Upload" />
                </form>
                <div class="clearfix"><br></div>
            </section>
        </div>
    </div>
</div>