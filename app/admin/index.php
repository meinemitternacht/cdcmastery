<?php
$pendingAssociations = $assoc->listPendingAFSCAssociations();
if(is_array($pendingAssociations)){
    $pendingAssociationsCount = count($pendingAssociations);
}
else{
    $pendingAssociationsCount = 0;
}

$userActivation = new userActivation($db,$log,$emailQueue);
$unactivatedUsers = $userActivation->listUnactivatedUsers();
if(is_array($unactivatedUsers)){
    $unactivatedUsersCount = count($unactivatedUsers);
}
else{
    $unactivatedUsersCount = 0;
}

$userAuthorization = new userAuthorizationQueue($db,$log,$emailQueue);
$authorizationQueue = $userAuthorization->listUserAuthorizeQueue();
if(is_array($authorizationQueue)){
    $authorizationQueueCount = count($authorizationQueue);
}
else{
    $authorizationQueueCount = 0;
}
?>
<div class="container">
	<header>
		<h2>Administration Panel</h2>
	</header>
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h2>Testing</h2>
				</header>
				<div class="sub-menu">
					<ul>
						<li><a href="/admin/afsc" title="AFSC Manager"><i class="icon-inline icon-20 ic-puzzle"></i>AFSC Manager</a></li>
						<li><a href="/admin/cdc-data" title="CDC Data"><i class="icon-inline icon-20 ic-book"></i>CDC Data</a></li>
						<li><a href="/admin/base-overview" title="Base Overview"><i class="icon-inline icon-20 ic-clipboard"></i>Base Overview</a></li>
						<li><a href="/admin/afsc-pending" title="Approve Pending AFSC Associations"><i class="icon-inline icon-20 ic-relationship"></i>Pending AFSC Associations (<?php echo ($pendingAssociationsCount > 0) ? '<span style="color:red;">'.$pendingAssociationsCount.'</span>' : $pendingAssociationsCount ; ?>)</a></li>
                        <li><a href="/admin/flash-card-categories" title="Manage Flash Card Categories"><i class="icon-inline icon-20 ic-chalkboard"></i>Flash Card Categories</a></li>
                        <li><a href="/admin/card-data" title="Manage Flash Card Data"><i class="icon-inline icon-20 ic-chalkboard"></i>Flash Card Data</a></li>
					</ul>
				</div>
			</section>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>Users</h2>
				</header>
				<div class="sub-menu">
					<ul>
						<li><a href="/admin/office-symbols" title="Office Symbols"><i class="icon-inline icon-20 ic-office"></i>Office Symbols</a></li>
						<li><a href="/admin/users" title="User Manager"><i class="icon-inline icon-20 ic-user-multiple"></i>User Manager</a></li>
						<li><a href="/admin/profile" title="User Profiles"><i class="icon-inline icon-20 ic-user-single"></i>User Profiles</a></li>
                        <li><a href="/admin/activate-users" title="Activate Users"><i class="icon-inline icon-20 ic-relationship"></i>Unactivated Users (<?php echo ($unactivatedUsersCount > 0) ? '<span style="color:red;">'.$unactivatedUsersCount.'</span>' : $unactivatedUsersCount ; ?>)</a></li>
                        <li><a href="/admin/authorize-users" title="Authorize Group for Users"><i class="icon-inline icon-20 ic-relationship"></i>Users Pending Authorization (<?php echo ($authorizationQueueCount > 0) ? '<span style="color:red;">'.$authorizationQueueCount.'</span>' : $authorizationQueueCount ; ?>)</a></li>
						<li><a href="/admin/users-duplicate" title="View Duplicate Users"><i class="icon-inline icon-20 ic-user-multiple"></i>View Duplicate Users</a></li>
					</ul>
				</div>
			</section>
		</div>
		<div class="4u">
			<section>
				<header>
					<h2>System</h2>
				</header>
				<div class="sub-menu">
					<ul>
						<li><a href="/admin/log" title="Log"><i class="icon-inline icon-20 ic-log"></i>Log</a></li>
						<li><a href="/admin/search" title="Search"><i class="icon-inline icon-20 ic-hammer"></i>Search</a></li>
						<li><a href="/admin/statistics" title="Statistics"><i class="icon-inline icon-20 ic-clipboard"></i>Statistics</a></li>
						<li><a href="/admin/memcache" title="Memcache Statistics"><i class="icon-inline icon-20 ic-chalkboard"></i>Memcache Statistics</a></li>
						<li><a href="/admin/bases" title="Base Manager"><i class="icon-inline icon-20 ic-office"></i>Base Manager</a></li>
						<?php if($cdcMastery->verifyAdmin()): ?>
						<li><a href="/admin/roles" title="Role Manager"><i class="icon-inline icon-20 ic-user-multiple"></i>Role Manager</a></li>
						<?php endif; ?>
						<li><a href="/admin/upload" title="Upload Files"><i class="icon-inline icon-20 ic-upload"></i>Upload Files</a></li>
					</ul>
				</div>
			</section> 
		</div>
	</div>
</div>