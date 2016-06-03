<?php
$testManager = new testManager($db, $log, $afsc);
$testList = $testManager->getTestUUIDList($userUUID);

if(!empty($testList)):
    if(isset($_POST['confirmTestDeleteAll'])){
        if($testManager->deleteTests($testList)){
            $sysMsg->addMessage("Completed tests deleted successfully.","success");
            $cdcMastery->redirect("/admin/users/" . $userUUID);
        }
        else{
            $sysMsg->addMessage("We could not delete the completed tests taken by this user, please contact the support helpdesk.","danger");
            $cdcMastery->redirect("/admin/users/" . $userUUID);
        }
    }
    else{ ?>
        <div class="container">
            <div class="row">
                <div class="4u">
                    <section>
                        <header>
                            <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                        </header>
                        <div class="sub-menu">
                            <div class="menu-heading">
                                Delete All Completed Tests
                            </div>
                            <ul>
                                <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                            </ul>
                        </div>
                    </section>
                </div>
                <div class="6u">
                    <section>
                        <header>
                            <h2>Confirm Deletion</h2>
                        </header>
                        <form action="/admin/users/<?php echo $userUUID; ?>/tests/delete" method="POST">
                            <input type="hidden" name="confirmTestDeleteAll" value="1">
                            If you wish to delete all completed tests taken by this user, please press continue.
                            Otherwise, <a href="/admin/users/<?php echo $userUUID; ?>">return to the user manager</a>.
                            <br>
                            <br>
                            <input type="submit" value="Continue">
                        </form>
                    </section>
                </div>
            </div>
        </div>
    <?php
    }
else:
    $sysMsg->addMessage("This user has not completed any tests.","info");
    $cdcMastery->redirect("/admin/users/".$userUUID);
endif;