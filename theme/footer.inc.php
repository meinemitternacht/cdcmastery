			<!--
			<div class="container">
				<div class="row">
					<div class="12u">
						<section>
							<h2>Debug Information</h2>
							<p><?php /*var_dump($_SESSION);*/ ?></p>
						</section>
					</div>
				</div>
			</div>
			-->
		</div>
		<!-- Footer -->
		<div id="footer">
			<div class="container">
				<div class="row">
					<div class="4u">
						<section>
							<h2>Latest Posts</h2>
							<ul class="default">
								<li><a href="#">Pellentesque lectus gravida blandit</a></li>
								<li><a href="#">Lorem ipsum consectetuer adipiscing</a></li>
								<li><a href="#">Phasellus nibh pellentesque congue</a></li>
								<li><a href="#">Cras vitae metus aliquam pharetra</a></li>
								<li><a href="#">Maecenas vitae orci feugiat eleifend</a></li>
							</ul>
						</section>
					</div>
					<div class="4u">
						<section>
							<h2>Ultrices fringilla</h2>
							<ul class="default">
								<li><a href="#">Pellentesque lectus gravida blandit</a></li>
								<li><a href="#">Lorem ipsum consectetuer adipiscing</a></li>
								<li><a href="#">Phasellus nibh pellentesque congue</a></li>
								<li><a href="#">Cras vitae metus aliquam pharetra</a></li>
								<li><a href="#">Maecenas vitae orci feugiat eleifend</a></li>
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
		
	</body>
</html>