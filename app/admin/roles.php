<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 10/26/14
 * Time: 4:29 PM
 */

if(!$cdcMastery->verifyAdmin()){
    $sysMsg->addMessage("You are not authorized to access that page.","danger");
    $cdcMastery->redirect("/errors/403");
}

$pageAction = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$roleUUID   = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
$formAction = isset($_POST['formAction']) ? $_POST['formAction'] : false;

if($formAction){
    switch($formAction){
        case "role-add":

        break;
        case "role-edit":
            $roleEditUUID = $roleUUID;
            $roleEditName = $_POST['roleName'];
            $roleEditType = $_POST['roleType'];
            $roleEditDescription = $_POST['roleDescription'];

            $error = false;

            if(empty($roleEditUUID)) {
                $sysMsg->addMessage("You must specify a role UUID","warning");
                $error = true;
            }

            if(empty($roleEditName)) {
                $sysMsg->addMessage("You must specify a role name","warning");
                $error = true;
            }

            if(empty($roleEditType)) {
                $sysMsg->addMessage("You must specify a role type","warning");
                $error = true;
            }

            if(!$error){
                $roles->loadRole($roleEditUUID);
                $roles->setRoleName($roleEditName);
                $roles->setRoleType($roleEditType);
                $roles->setRoleDescription($roleEditDescription);

                if($roles->saveRole()){
                    $log->setAction("ROLE_EDIT");
                    $log->setDetail("Role UUID",$roles->getUUID());
                    $log->setDetail("Role Name",$roles->getRoleName());
                    $log->setDetail("Role Type",$roles->getRoleType());
                    $log->setDetail("Role Description",$roles->getRoleDescription());
                    $log->saveEntry();

                    $sysMsg->addMessage("Role edited successfully.","success");
                    $cdcMastery->redirect("/admin/roles");
                }
                else{
                    $log->setAction("ERROR_ROLE_EDIT");
                    $log->setDetail("Role UUID",$roles->getUUID());
                    $log->setDetail("Role Name",$roles->getRoleName());
                    $log->setDetail("Role Type",$roles->getRoleType());
                    $log->setDetail("Role Description",$roles->getRoleDescription());
                    $log->saveEntry();

                    $sysMsg->addMessage("There was an issue editing that role.","danger");
                    $cdcMastery->redirect("/admin/roles/edit/" . $roleEditUUID);
                }
            }
        break;
        case "role-delete":

        break;
        case "role-migrate":
            $currentRole = isset($_POST['currentRole']) ? $_POST['currentRole'] : false;
            $targetRole = isset($_POST['targetRole']) ? $_POST['targetRole'] : false;

            if($roles->verifyRole($currentRole) && $roles->verifyRole($targetRole)){
                if($roles->migrateUserRoles($currentRole,$targetRole)) {
                    $sysMsg->addMessage("User roles migrated successfully.","success");
                }
                else{
                    $sysMsg->addMessage("There was an issue migrating user roles.","danger");
                }
            } else {
                $sysMsg->addMessage("We couldn't verify one or more of the provided roles.","danger");
            }

            $cdcMastery->redirect("/admin/roles");
        break;
        case "default":
            $sysMsg->addMessage("Sorry, we couldn't process your request.","info");
            $cdcMastery->redirect("/errors/500");
        break;
    }
}

$roleList = $roles->listRoles();
if($pageAction == "edit"):
    $roles->loadRole($roleUUID); ?>
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin/roles"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Role Manager</a></li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="4u">
                <section>
                    <header>
                        <h2>Edit Role</h2>
                    </header>
                    <form action="/admin/roles/edit/<?php echo $roleUUID; ?>" method="POST">
                        <input type="hidden" name="formAction" value="role-edit">
                        <?php
                        $roleType = $roles->getRoleType();
                        $roleName = $roles->getRoleName();
                        $roleDescription = $roles->getRoleDescription();
                        ?>
                        <ul>
                            <li>
                                <label for="roleType">Type</label>
                                <br>
                                <select id="roleType" name="roleType">
                                    <option value="admin"<?php if($roleType == "admin"){ echo " SELECTED"; } ?>>Administrator</option>
                                    <option value="editor"<?php if($roleType == "editor"){ echo " SELECTED"; } ?>>Question Editor</option>
                                    <option value="supervisor"<?php if($roleType == "supervisor"){ echo " SELECTED"; } ?>>Supervisor</option>
                                    <option value="trainingManager"<?php if($roleType == "trainingManager"){ echo " SELECTED"; } ?>>Training Manager</option>
                                    <option value="user"<?php if($roleType == "user"){ echo " SELECTED"; } ?>>User</option>
                                </select>
                            </li>
                            <li>
                                <label for="roleName">Name</label>
                                <br>
                                <input type="text" id="roleName" name="roleName" value="<?php echo $roleName; ?>">
                            </li>
                            <li>
                                <label for="roleDescription">Description</label>
                                <br>
                                <textarea id="roleDescription" name="roleDescription"><?php echo $roleDescription; ?></textarea>
                            </li>
                            <li>
                                <br>
                                <input type="submit" value="Edit Role">
                            </li>
                        </ul>
                    </form>
                </section>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="container">
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>Role Manager</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="6u">
            <section>
                <header>
                    <h2>Current Roles</h2>
                </header>
                <p>Click on role name to edit or delete role.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($roleList as $roleUUID => $roleDetails): ?>
                        <tr>
                            <td><a href="/admin/roles/edit/<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></a></td>
                            <td><?php echo $roleDetails['roleType']; ?></td>
                            <td><?php echo $roleDetails['roleDescription']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
        <div class="3u">
            <section>
                <header>
                    <h2>Add Role</h2>
                </header>
                <form action="/admin/roles" method="POST">
                    <input type="hidden" name="formAction" value="role-add">
                    <ul>
                        <li>
                            <label for="roleType">Type</label>
                            <br>
                            <select id="roleType" name="roleType">
                                <option value="">Select Role Type...</option>
                                <option value="admin">Administrator</option>
                                <option value="editor">Question Editor</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="trainingManager">Training Manager</option>
                                <option value="user">User</option>
                            </select>
                        </li>
                        <li>
                            <label for="roleName">Name</label>
                            <br>
                            <input type="text" id="roleName" name="roleName">
                        </li>
                        <li>
                            <label for="roleDescription">Description</label>
                            <br>
                            <textarea id="roleName" name="roleName"></textarea>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Add Role">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
        <div class="3u">
            <section>
                <header>
                    <h2>Migrate User Roles</h2>
                </header>
                <form action="/admin/roles" method="POST">
                    <input type="hidden" name="formAction" value="role-migrate">
                    <ul>
                        <li>
                            <label for="currentRole">Current Role</label>
                            <br>
                            <select id="currentRole" name="currentRole">
                            <?php foreach($roleList as $roleUUID => $roleDetails): ?>
                                <option value="<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></option>
                            <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="targetRole">Target Role</label>
                            <br>
                            <select id="targetRole" name="targetRole">
                            <?php foreach($roleList as $roleUUID => $roleDetails): ?>
                                <option value="<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></option>
                            <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Migrate Roles">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
    </div>
</div>
<?php endif; ?>