<?php

if(isset($_POST['confirmClearLogEntries'])){
    if($log->clearLogEntries($userUUID)){
        $_SESSION['messages'][] = "Log entries for this user deleted successfully.";
        $cdcMastery->redirect("/admin/users/" . $userUUID);
    }
    else{
        $_SESSION['messages'][] = "We could not delete the log entries for this user, please <a href=\"http://helpdesk.cdcmastery.com\">submit a ticket</a>.";
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
                            Clear Log Entries
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
                    <form action="/admin/users/<?php echo $userUUID; ?>/log/clear" method="POST">
                        <input type="hidden" name="confirmClearLogEntries" value="1">
                        If you wish to delete all log entries for this user, please press continue.
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