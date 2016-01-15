<!DOCTYPE HTML>
<html>
	<head>
		<?php if($router->getRoute() == "about/afsc"): ?>
		<title>CDCMastery - <?php $afsc->loadAFSC($_SESSION['vars'][0]); echo $afsc->getAFSCName(); ?> Practice Test</title>
		<?php else: ?>
		<title>CDCMastery<?php if(isset($pageTitleArray[$router->getRoute()])) { echo " - ".$pageTitleArray[$router->getRoute()]; } ?></title>
		<?php endif; ?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="CDCMastery is a tool to help you succeed on the Air Force CDC EOC tests, Promotion Tests and other assessments of your career knowledge by answering questions and studying flash cards." />
		<?php
		$metaAFSCList = $afsc->listAFSC(true);
		foreach($metaAFSCList as $metaAFSCData){
			$afscNameArray[] = $metaAFSCData['afscName'];
		}

		$metaAFSCKeywords = implode(", ",$afscNameArray);
		?>
		<meta name="keywords" content="weapons cdc pretest, air force cdc, cdc pretest, cdc flash cards, flashcards, flash cards, career development course, career development course pretests, cdc tests, skt study guide, cdc study guide, air force cdc study guide, <?php echo $metaAFSCKeywords; ?>" />
		<!--[if lte IE 8]><script src="/js/html5shiv.js"></script><![endif]-->
        <?php
        /*
         * Generate URL's to utilize minify subproject
         */

        $scriptArray = Array(   '/js/jquery-1.11.3.min.js',
                                '/js/jquery-ui.min.js',
                                '/js/jquery-ui-timepicker.js',
                                '/js/jquery-mask.min.js',
                                '/js/skel.min.js',
                                '/js/skel-panels.min.js',
                                '/js/init.js',
                                '/js/jquery.formalize.min.js',
                                '/js/jquery.timeago.js',
                                '/js/jquery.canvasjs.min.js',
								'/js/jquery.tablesorter.min.js',
								'/js/jquery.touchSwipe.min.js');

        $cssArray = Array(  '/css/jquery-ui.min.css',
                            '/css/jquery-ui.structure.min.css',
                            '/css/jquery-ui.theme.min.css',
                            '/css/formalize.css',
                            '/css/icons.css');

        $noScriptCSSArray = Array(  '/css/skel-noscript.css',
                                    '/css/style.css',
                                    '/css/style-desktop.css');

        $scriptURL = "/minify/min/?f=" . implode(",",$scriptArray) . "&rand=51F71816";
        $cssURL = "/minify/min/?f=" . implode(",",$cssArray) . "&rand=51F71815";
        $noScriptCSSURL = "/minify/min/?f=" . implode(",",$noScriptCSSArray) . "&rand=51F71814";
        ?>
		<script src="<?php echo $scriptURL; ?>"></script>
		<link href="<?php echo $cssURL; ?>" rel="stylesheet" type="text/css" />
		<noscript>
			<link rel="stylesheet" href="<?php echo $noScriptCSSURL; ?>" />
		</noscript>
		<!--[if lte IE 8]><link rel="stylesheet" href="/css/ie/v8.css" /><![endif]-->
		<!--[if lte IE 9]><link rel="stylesheet" href="/css/ie/v9.css" /><![endif]-->
	</head>
	<body>
		<script>
			$(function() {
				$( document ).tooltip();
				$('abbr.timeago').timeago();
			});
		</script>
		<!-- Header -->
		<div id="header">
			<div class="container">
				<!-- Logo -->
				<div id="logo">
					<h1><a href="/"><img src="/images/logo-20140930-230-60.png" alt="CDCMastery.com - Learning Enabled" title="CDCMastery.com - Learning Enabled" /></a></h1>
				</div>
				<!-- Nav -->
				<nav id="nav">
					<ul>
						<li<?php if($router->getSiteSection() == "index"): ?> class="active"<?php endif; ?>><a href="/">Home</a></li>
						<li<?php if($router->getSiteSection() == "about"): ?> class="active"<?php endif; ?>><a href="/about">About</a></li>
						<li><a href="http://helpdesk.cdcmastery.com" target="_blank">Support</a></li>
						<?php if($cdcMastery->loggedIn()): ?>
							<?php if($cdcMastery->verifyAdmin() || $cdcMastery->verifyTrainingManager()): ?>
								<li<?php if($router->getSiteSection() == "admin"): ?> class="active"<?php endif; ?>>
                                    <a href="/admin">Admin Panel</a>
                                    <ul>
                                        <li><a href="/admin/afsc" title="AFSC Manager">AFSC Manager</a></li>
                                        <li><a href="/admin/cdc-data" title="CDC Data">CDC Data</a></li>
										<li><a href="/admin/base-overview" title="Base Overview">Base Overview</a></li>
                                        <li><a href="/admin/office-symbols" title="Office Symbols">Office Symbols</a></li>
                                        <li><a href="/admin/users" title="User Manager">User Manager</a></li>
                                        <li><a href="/admin/profile" title="User Profiles">User Profiles</a></li>
                                        <li><a href="/admin/log" title="Log">Log</a></li>
										<li><a href="/admin/search" title="Search">Search</a></li>
										<li><a href="/admin/statistics" title="Statistics">Statistics</a></li>
                                        <li><a href="/admin/roles" title="Role Manager">Role Manager</a></li>
                                        <li><a href="/admin/upload" title="Upload Files">Upload Files</a></li>
                                    </ul>
                                </li>
								<?php if($cdcMastery->verifyTrainingManager()): ?>
									<li<?php if($router->getSiteSection() == "training"): ?> class="active"<?php endif; ?>><a href="/training/overview">Training Overview</a></li>
								<?php endif; ?>
							<?php elseif($cdcMastery->verifySupervisor()): ?>
								<li<?php if($router->getSiteSection() == "supervisor"): ?> class="active"<?php endif; ?>><a href="/supervisor/overview">Supervisor Overview</a></li>
							<?php endif; ?>
							<li<?php if($router->getSiteSection() == "auth"): ?> class="active"<?php endif; ?>><a href="/auth/logout">Logout</a></li>
						<?php else: ?>
						<li<?php if($router->getSiteSection() == "auth"): ?> class="active"<?php endif; ?>><a href="/auth/login">Login</a></li>
                        <li<?php if($router->getSiteSection() == "register"): ?> class="active"<?php endif; ?>><a href="/auth/register">Register</a></li>
						<?php endif; ?>
					</ul>
				</nav>
				<div class="clearfix">&nbsp;</div>
			</div>
		</div>
		<!-- Main -->
		<div id="main">
			<div class="container" id="system-messages-container-block" style="display:none;">
				<div class="row">
					<div class="12u">
						<div class="systemMessages" id="system-messages-block">
							&nbsp;
						</div>
					</div>
				</div>
			</div>