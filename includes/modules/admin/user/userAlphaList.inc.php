<?php
$alpha = Array(
		1 => "A",
		2 => "B",
		3 => "C",
		4 => "D",
		5 => "E",
		6 => "F",
		7 => "G",
		8 => "H",
		9 => "I",
		10 => "J",
		11 => "K",
		12 => "L",
		13 => "M",
		14 => "N",
		15 => "O",
		16 => "P",
		17 => "Q",
		18 => "R",
		19 => "S",
		20 => "T",
		21 => "U",
		22 => "V",
		23 => "W",
		24 => "X",
		25 => "Y",
		26 => "Z");

$userList = $user->listUsers();
$userCount = count($userList) + 1;

if($userList): ?>
	<h2><?php echo $userCount; ?> Total Users</h2>
	<div class="container">
		<div class="row">
			<div class="8u">
				<section>
					<p>
					<?php
					foreach($alpha as $val){
						echo ' <a href="#'.$val.'">'.$val.'</a> ';
					}
					?>
					</p>
				</section>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row">
			<div class="4u">
				<section>
				<br />
				<?php
				$curLetter = "";
				
				$firstColComplete = false;
				$secondColComplete = false;
				
				foreach($userList as $uuid => $userRow){
					$letter = substr($userRow['userLastName'],0,1);
					
					if($letter == "H" && $firstColComplete == false){ ?>
						</section>
						</div>
						<div class="4u">
						<section>
						<br />
						<?php
						$firstColComplete = true;
					}
					
					if($letter == "Q" && $secondColComplete == false){ ?>
						</section>
						</div>
						<div class="4u">
						<section>
						<br />
						<?php
						$secondColComplete = true;
					}
					
					if($letter != $curLetter){
						echo '<h2><a id="'.ucfirst($letter).'">'.$letter.'</a></h2>';
						$curLetter = $letter;
					}
					
					echo '<a href="/'.$linkBaseURL.'/'.$uuid.'">'.$userRow['userLastName'].', '.$userRow['userFirstName'].' '.$userRow['userRank'].'</a><br />';
				}
				?>
				</section>
			</div>
		</div>
	</div>
	<?php
else:
	echo "There are no users in the database.";
endif;
?>