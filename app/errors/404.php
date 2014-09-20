<!-- Content -->
<div id="content" class="12u skel-cell-important">
	<section>
		<header>
			<h2>404 Not Found</h2>
		</header>
		<p>Sorry!  We couldn't find the page you were looking for.  Maybe you could try different page?</p>
		<?php 
		if(!empty($_SESSION['error'])):
			"<br><br><p><strong>Additionally, we encountered the following ";
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
		endif;
		?>
	</section>
</div>