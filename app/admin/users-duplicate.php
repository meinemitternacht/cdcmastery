<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/30/2015
 * Time: 6:41 AM
 */

if(isset($_POST) && !empty($_POST)){
    $i=0;
    $errors=0;
    foreach($_POST['userUUID'] as $userUUIDDelete){
        if($user->deleteUser($userUUIDDelete)){
            $i++;
        }
        else{
            $errors++;
        }
    }

    $sysMsg->addMessage("Processed ".$i." users with ".$errors." errors. Check the system log for any issues.");
}

$res = $db->query("SELECT CONCAT(`userFirstName`, ' ', `userLastName`) AS concatenatedName
                      FROM userData
                      GROUP BY CONCAT(`userFirstName`, ' ', `userLastName`)
                      HAVING COUNT(*) > 1
                      ORDER BY userData.userLastName, userData.userFirstName ASC");

if($res->num_rows > 0){
    while($row = $res->fetch_assoc()){
        $duplicateUserList[] = $row['concatenatedName'];
    }

    $res->close();

    if(isset($duplicateUserList) && !empty($duplicateUserList)){
        $userSearchString = "('" . implode("','",$duplicateUserList) . "')";
    }
}
?>
<?php if($cdcMastery->verifyAdmin()): ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#selectAll').click(function(event) {
            if(this.checked) {
                $('.duplicateUserCheckbox').each(function() {
                    this.checked = true;
                });
            }else{
                $('.duplicateUserCheckbox').each(function() {
                    this.checked = false;
                });
            }
        });
    });
</script>
<?php endif; ?>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2>Duplicate User List</h2>
                </header>
                <a href="/admin">&laquo; Return to Admin Panel</a>
                <br>
                <br>
                <?php if($cdcMastery->verifyAdmin()): ?>
                <form action="/admin/users-duplicate" method="POST">
                    <p>
                        Mark the users in the list that you wish to delete.  Note:  <strong>If you don't know what you're doing, please don't assume a user is duplicate; submit a ticket instead.</strong>
                    </p>
                <?php endif; ?>
                    <table style="font-size: 11px;">
                        <tr>
                            <?php if($cdcMastery->verifyAdmin()): ?>
                            <th><input type="checkbox" id="selectAll"></th>
                            <?php endif; ?>
                            <th>Rank</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>E-mail</th>
                            <th>Base</th>
                            <th>Date Registered</th>
                            <th>Last Login</th>
                            <th>Last Active</th>
                            <th>Tests</th>
                        </tr>
                        <?php
                        $res = $db->query("SELECT   userData.uuid
                                                FROM userData
                                                WHERE
                                                  CONCAT(`userFirstName`, ' ',`userLastName`) IN ".$userSearchString."
                                                ORDER BY userData.userLastName, userData.userFirstName ASC");

                        if($res->num_rows > 0){
                            $userObj = new user($db,$log,$emailQueue);
                            while($row = $res->fetch_assoc()){
                                $userObj->loadUser($row['uuid']);
                                $userStatistics->setUserUUID($row['uuid']);
                        ?>
                            <tr>
                                <?php if($cdcMastery->verifyAdmin()): ?>
                                <td><input class="duplicateUserCheckbox" type="checkbox" name="userUUID[]" value="<?php echo $row['uuid']; ?>"></td>
                                <?php endif; ?>
                                <td><?php echo $userObj->getUserRank(); ?></td>
                                <td><?php echo $userObj->getUserLastName(); ?></td>
                                <td><?php echo $userObj->getUserFirstName(); ?></td>
                                <td><?php echo $userObj->getUserEmail(); ?></td>
                                <td><?php echo $bases->getBaseName($userObj->getUserBase()); ?></td>
                                <td><?php echo $userObj->getUserDateRegistered(); ?></td>
                                <td><?php echo $userObj->getUserLastLogin(); ?></td>
                                <td><?php echo $userObj->getUserLastActive(); ?></td>
                                <td><?php echo $userStatistics->getTotalTests(); ?></td>
                            </tr>
                        <?php
                            }
                        }
                        ?>
                    </table>
                    <?php if($cdcMastery->verifyAdmin()): ?>
                    <div class="clearfix">&nbsp;</div>
                    <input type="submit" value="Delete Users">
                </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
