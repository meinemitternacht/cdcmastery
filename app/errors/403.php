<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h1>403 Unauthorized</h1>
				</header>
				<p>You are not authorized to view that portion of the site.  If you feel this is in error, please <a href="http://helpdesk.cdcmastery.com">open a ticket</a>.</p>
				<?php 
				if(!empty($_SESSION['error'])):
					echo "<br><br><p><strong>Additionally, we encountered the following ";
					if(is_array($_SESSION['error'])){
						echo "errors:</strong></p>";
						foreach($_SESSION['error'] as $error){
							echo "<p>".$error."</p>\n";
						}
					}
					else{
						echo "error:</strong></p>";
						echo $_SESSION['error'];
					}
					
					unset($_SESSION['error']);
				endif;
				?>
			</section>
		</div>
	</div>
</div>