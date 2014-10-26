<?php
if(isset($_SESSION['vars'][0]))
	$accountType = $_SESSION['vars'][0];

if(isset($accountType)): ?>
<form action="/auth/register/<?php echo $accountType; ?>" method="POST">
<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h2>Create Account</h2>
				</header>
			</section>
		</div>
	</div>
	<div class="row">
		<div class="4u">
			<section>
				<header>
					<h3>Username and Password</h3>				
				</header>
				<ul>
					<li>
						<label for="userHandle">Username</label>
						<br>
						<input id="userHandle" name="userHandle" type="text">
					</li>
					<li>
						<label for="userPassword">Password</label>
						<br>
						<input id="userPassword" name="userPassword" type="text">
					</li>
					<li>
						<label for="userPasswordConfirm">Confirm Password</label>
						<br>
						<input id="userPasswordConfirm" name="userPasswordConfirm" type="text">
					</li>
				</ul>
			</section>
			<section>
				<header>
					<h3>Time Zone</h3>				
				</header>
				<ul>
					<li>
						<label for="timeZone">Time Zone</label>
						<br>
						<select id="timeZone" name="timeZone" size="1">
							<option value="">Select Time Zone...</option>
						</select>
					</li>
				</ul>
			</section>
		</div>
		<div class="4u">
			<section>
				<header>
					<h3>Your Details</h3>
				</header>
				<ul>
					<li>
						<label for="userRank">Rank</label>
						<br>
						<select id="userRank" name="userRank" size="1">
							<option value="">Select rank...</option>
							<?php 
							$rankList = $cdcMastery->listRanks();
							foreach($rankList as $rankGroupLabel => $rankGroup){
								echo '<optgroup label="'.$rankGroupLabel.'">';
								foreach($rankGroup as $rankOrder){
									foreach($rankOrder as $rankKey => $rankVal): ?>
									<option value="<?php echo $rankKey; ?>"><?php echo $rankVal; ?></option>
									<?php
									endforeach;
								}
								echo '</optgroup>';
							}
							?>
						</select>
					</li>
					<li>
						<label for="userFirstName">First Name</label>
						<br>
						<input id="userFirstName" name="userFirstName" type="text">
					</li>
					<li>
						<label for="userLastName">Last Name</label>
						<br>
						<input id="userLastName" name="userLastName" type="text">
					</li>
					<li>
						<label for="userEmail">E-mail</label>
						<br>
						<input id="userEmail" name="userEmail" type="text">
					</li>
					<li>
						<label for="userBase">Base</label>
						<br>
						<select id="userBase" name="userBase" size="1">
							<option value="">Select base...</option>
							<?php 
							$baseList = $bases->listBases();
							
							foreach($baseList as $baseUUID => $baseName): ?>
							<option value="<?php echo $baseUUID; ?>"><?php echo $baseName; ?></option>
							<?php endforeach; ?>
						</select>
					</li>
				</ul>
			</section>
		</div>
		<div class="4u">
			<section>
				<header>
					<h3>Testing Details</h3>
				</header>
				<ul>
					<li>
						AFSC
					</li>
					<?php if($accountType == "user"): ?>
					<li>
						Training Manager
					</li>
					<li>
						Supervisor
					</li>
					<?php elseif($accountType == "supervisor"): ?>
					<li>
						Training Manager
					</li>
					<?php endif; ?>
				</ul>
			</section>
		</div>
	</div>
</div>
</form>
<?php else:?>
<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h2>Before you begin</h2>
				</header>
				<ul>
					<li><i class="fa fa-lightbulb-o fa-fw"></i>You must register with an e-mail address ending with ".mil"</li>
					<li><i class="fa fa-lightbulb-o fa-fw"></i>Only one account may be registered per e-mail address</li>
					<li><i class="fa fa-lightbulb-o fa-fw"></i>You may change your account type at any time by sending a message to our support team</li>
					<li><i class="fa fa-lightbulb-o fa-fw"></i>Supervisor and Training manager accounts require approval. Your account will have user permissions until approval occurs</li>
				</ul>
			</section>
		</div>
	</div>
	<div class="row text-center">
		<div class="4u">
			<section id="reg-user" style="border-bottom: 6px solid #693;">
				<i class="fa fa-user fa-5x"></i>
				<br>
				<br>
				<header>
					<h2>User Account</h2>
				</header>
				<p>Choose this account if you are not a supervisor or training manager.</p>
				<br>
				<h2><a href="/auth/register/user">Create user account &raquo;</a></h2>
			</section>
		</div>
		<div class="4u">
			<section style="border-bottom: 6px solid #369;">
				<i class="fa fa-users fa-5x"></i>
				<br>
				<br>
				<header>
					<h2>Supervisor Account</h2>
				</header>
				<p>Select this account if you require an overview of your subordinates.</p>
				<br>
				<h2><a href="/auth/register/supervisor">Create Supervisor Account &raquo;</a></h2>
			</section>
		</div>
		<div class="4u">
			<section style="border-bottom: 6px solid #933;">
				<i class="fa fa-cog fa-5x"></i>
				<br>
				<br>
				<header>
					<h2>Training Manager Account</h2>
				</header>
				<p>Choose this account to manage questions and answers, as well as view subordinate progress.</p>
				<br>
				<h2><a href="/auth/register/training-manager">Create Training Manager Account &raquo;</a></h2>
			</section>
		</div>
	</div>
</div>
<?php endif; ?>