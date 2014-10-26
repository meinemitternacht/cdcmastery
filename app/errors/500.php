<div class="container">
	<div class="row">
		<div class="12u">
			<section>
				<header>
					<h1>500 Internal Server Error</h1>
				</header>
				<p>Sorry!  We had some problems fulfilling your request.  Maybe you could try again later, or <a href="http://helpdesk.cdcmastery.com">open a ticket</a>?</p>
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