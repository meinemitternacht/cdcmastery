<?php
$testManager = new testManager($db, $log, $afsc);

if(isset($_POST['confirmTestDeleteAll'])){
    $testList = $testManager->getTestUUIDList($userUUID);

    var_dump($testList);

    if($testManager->deleteTests($testList)){
        $_SESSION['messages'][] = "Completed tests deleted successfully.";
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
    else{
        $_SESSION['messages'][] = "We could not delete the completed tests taken by this user, please <a href=\"http://helpdesk.cdcmastery.com\">submit a ticket</a>.";
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
                            <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to user manager</a></li>
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