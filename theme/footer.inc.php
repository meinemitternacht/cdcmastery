		</div>
		<!-- Footer -->
		<div id="footer">
			<div class="container">
				<div class="row">
					<div class="4u">
						<section>
							<h2>Quick Links</h2>
							<ul class="default">
								<?php if($cdcMastery->loggedIn()): ?>
									<li><a href="/test/take">New Test</a></li>
									<li><a href="/user/history">Test History</a></li>
									<li><a href="/user/profile">Your Profile</a></li>
									<li><a href="/about">Site Information</a></li>
									<?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
									<li><a href="/admin">Administration Panel</a></li>
									<?php endif; ?>
								<?php else: ?>
									<li><a href="/about">Site Information</a></li>
								<?php endif; ?>
                                <li><a href="/about/privacy">Privacy Policy</a></li>
                                <li><a href="/about/terms">Terms of Use</a></li>
                                <li><a href="/about/disclaimer">Disclaimer</a></li>
							</ul>
						</section>
					</div>
					<div class="4u">
						<section>
							<h2>Latest Changes</h2>
							<ul class="default">
								<li>Added a Pending AFSC Queue for users</li>
								<li>Added ability to reset a user's password from the admin interface</li>
								<li>Improved supervisor and training manager associations interface</li>
								<li>Updated the role manager interface</li>
								<li>Completely rebuilt the site</li>
							</ul>
						</section>
					</div>
					<div class="4u">
						<section>
							<h2>Performance Information</h2>
							<ul class="default">
								<li>Rendered in 
									<?php
									$time_end = microtime(true);
									$time = $time_end - $time_start;
									echo round($time,5);
									?>s using <?php echo round((memory_get_usage(true) / 1048576),2); ?> MB
								</li>
								<?php
								if($_SERVER['HTTP_HOST'] != "localhost"){
									//GET SERVER LOADS
									$loadresult = @exec('uptime');
									@preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);
					
									//GET SERVER UPTIME
									$uptime = @explode(' up ', $loadresult);
									$uptime = @explode(',', $uptime[1]);
									$uptime = $uptime[0].', '.$uptime[1];
									$data = "<li>Load: $avgs[1], $avgs[2], $avgs[3]</li>";
									$data .= "<li>Uptime: $uptime</li>";
									echo $data;
								}
								?>
							</ul>
						</section>
					</div>
				</div>
			</div>
		</div>

		<!-- Copyright -->
		<div id="copyright">
			<div class="container">
				&copy;<?php echo date("Y",time()); ?> CDCMastery.com
			</div>
		</div>
		<?php
		if($sysMsg->getMessageCount() > 0):
			$systemMessageHTML = "<ul>";
			foreach($sysMsg->retrieveMessages() as $systemMessage){
				$systemMessageHTML .= "<li><strong>".$systemMessage."</strong></li>";
			}
			$systemMessageHTML .= "</ul>";
			?>
		<script type="text/javascript">
			var sysMsgHTML = "<?php echo $systemMessageHTML; ?>";
			$('#system-messages-block').html(sysMsgHTML);
			$('#system-messages-container-block').show();
		</script>
		<?php endif; ?>
	</body>
</html>