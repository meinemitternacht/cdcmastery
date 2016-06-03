<?php
$testManager = new testManager($db, $log, $afsc);
$testList = $testManager->listUserTests($userUUID);

if(isset($_POST['confirmTestDelete'])){
    if(!isset($_POST['testUUIDList']) || empty($_POST['testUUIDList'])){
        $sysMsg->addMessage("You must specify at least one test to delete.","warning");
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
    else {
        $testList = $_POST['testUUIDList'];
        if ($testManager->deleteTests($testList)) {
            $sysMsg->addMessage("Selected tests deleted successfully.","success");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/tests/delete");
        } else {
            $sysMsg->addMessage("We could not delete the selected tests, please contact the support helpdesk.","danger");
            $cdcMastery->redirect("/admin/users/" . $userUUID . "/tests/delete");
        }
    }
}
else{ ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#selectAll').click(function(event) {
            if(this.checked) {
                $('.deleteTestCheckbox').each(function() {
                    this.checked = true;
                });
            }else{
                $('.deleteTestCheckbox').each(function() {
                    this.checked = false;
                });
            }
        });

    });
</script><!--[if !IE]><!-->
<style type="text/css">
    @media
    only screen and (max-width: 760px),
    (min-device-width: 768px) and (max-device-width: 1024px)  {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        tr { border: 1px solid #ccc; }
        td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 25%;
        }
        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 20%;
            padding-right: 10px;
            white-space: nowrap;
        }
        table#history-table-1 td:nth-of-type(1):before { content: "Mark"; }
        table#history-table-1 td:nth-of-type(2):before { content: "Completed"; }
        table#history-table-1 td:nth-of-type(3):before { content: "AFSC"; }
        table#history-table-1 td:nth-of-type(4):before { content: "Questions"; }
        table#history-table-1 td:nth-of-type(5):before { content: "Score"; }
    }

    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body {
            padding: 0;
            margin: 0;
            width: 320px; }
    }

    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
        body {
            width: 495px;
        }
    }
</style>
<!--<![endif]-->
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                </header>
                <div class="sub-menu">
                    <div class="menu-heading">
                        Delete Completed Tests
                    </div>
                    <ul>
                        <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <div class="8u">
            <section>
                <header>
                    <h2>Confirm Deletion</h2>
                </header>
                <form action="/admin/users/<?php echo $userUUID; ?>/tests/delete" method="POST">
                    <input type="hidden" name="confirmTestDelete" value="1">
                    Select the tests below that you would like to delete and press "Continue".  If you do not wish to complete this action, <a href="/admin/users/<?php echo $userUUID; ?>">return to the user manager</a>.
                    <table>
                        <thead>

                        </thead>
                    </table>
                    <table id="history-table-1">
                        <thead>
                            <tr>
                                <th><input type="checkbox" name="selectAll" id="selectAll"></th>
                                <th>Date Completed</th>
                                <th>AFSC</th>
                                <th>Questions</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i=0;
                        foreach($testList as $testUUID => $testDetails):
                            if(is_array($testDetails['afscList'])){
                                $rawAFSCList = $testDetails['afscList'];

                                foreach($rawAFSCList as $key => $val){
                                    $rawAFSCList[$key] = $afsc->getAFSCName($val);
                                }

                                if(count($rawAFSCList) > 1){
                                    $testAFSCList = implode(",",$rawAFSCList);
                                }
                                else{
                                    $testAFSCList = $rawAFSCList[0];
                                }
                            }
                            else{
                                $testAFSCList = $testDetails['afscList'];
                            }

                            if(strlen($testAFSCList) > 11){
                                $testAFSCList = substr($testAFSCList,0,12) . "...";
                            }
                            ?>
                            <tr>
                                <td><input type="checkbox" class="deleteTestCheckbox" name="testUUIDList[]" value="<?php echo $testUUID; ?>"></td>
                                <td><?php echo $cdcMastery->outputDateTime($testDetails['testTimeCompleted'], $_SESSION['timeZone']); ?></td>
                                <td><?php echo $testAFSCList; ?></td>
                                <td><?php echo $testDetails['totalQuestions']; ?></td>
                                <td><strong><?php echo $testDetails['testScore']; ?>%</strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <input type="submit" value="Continue">
                </form>
            </section>
        </div>
    </div>
</div>
<?php
}