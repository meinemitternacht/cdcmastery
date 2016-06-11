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
                                <li>Add top-missed question overview for Training Managers and Supervisors</li>
                                <li>Added site statistics</li>
                                <li>Added detailed statistics to test reviews</li>
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
								$loadresult = @exec('uptime');
								@preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);
								$uptime = @explode(' up ', $loadresult);
								$uptime = @explode(',', $uptime[1]);
								$uptime = $uptime[0].', '.$uptime[1];
								$data = "<li>Load: $avgs[1], $avgs[2], $avgs[3]</li>";
								$data .= "<li>Uptime: $uptime</li>";
								echo $data;
								?>
							</ul>
                            <br>
						</section>
					</div>
				</div>
			</div>
		</div>

		<!-- Copyright -->
		<div id="copyright">
			<div class="container">
				&copy;<?php echo date("Y",time()); ?> CDCMastery.com<br>
				<em>This application is not endorsed by, affiliated with, or an official product of the United States Air Force.</em>
			</div>
		</div>
		<?php
		if($sysMsg->getMessageCount() > 0):
			$systemMessageHTML = "";
            $validMessageTypes = $sysMsg->getValidMessageTypes();
            $messageArray = $sysMsg->retrieveMessages();
                
            foreach($validMessageTypes as $messageType){
                if(!isset($messageArray[$messageType])){
                    continue;
                }

                $systemMessageHTML .= '<ul class=\"sysmsg-' . $messageType . '\">';

                foreach($messageArray[$messageType] as $systemMessage){
                    $systemMessageHTML .= "<li><strong>" . $systemMessage . "</strong></li>";
                }
                
                $systemMessageHTML .= "</ul>";
            }
			?>
		<script type="text/javascript">
			var sysMsgHTML = "<?php echo $systemMessageHTML; ?>";
			$('#system-messages-block').html(sysMsgHTML);
			$('#system-messages-container-block').fadeIn();
		</script>
		<?php endif; ?>
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            <?php
            // New Google Analytics code to set User ID.
            // $userId is a unique, persistent, and non-personally identifiable string ID.
            if (isset($_SESSION['userUUID']) && !empty($_SESSION['userUUID'])) {
              $gacode = "ga('create', 'UA-30696456-1', 'auto', {'userId': '%s'});";
              echo sprintf($gacode, $_SESSION['userUUID']);
            } else {
              $gacode = "ga('create', 'UA-30696456-1', 'auto');";
              echo sprintf($gacode);
            }?>
            ga('send', 'pageview');

        </script>
	</body>
</html>