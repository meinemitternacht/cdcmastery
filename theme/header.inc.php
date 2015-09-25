<!DOCTYPE HTML>
<html>
	<head>
		<title>CDCMastery</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="CDCMastery is a tool to help you succeed on the Air Force CDC EOC tests, Promotion Tests and other assessments of your career knowledge." />
		<meta name="keywords" content="air force cdc, cdc pretest, career development course, career development course pretests, cdc tests, skt study guide, cdc study guide, air force cdc study guide" />
		<meta name="revisit-after" content="30 days" />
		<!--[if lte IE 8]><script src="/js/html5shiv.js"></script><![endif]-->
		<script src="/js/jquery-1.11.1.min.js"></script>
		<script src="/js/jquery-ui.min.js"></script>
		<script src="/js/jquery-ui-timepicker.js"></script>
		<script src="/js/jquery-mask.min.js"></script>
		<script src="/js/skel.min.js"></script>
		<script src="/js/skel-panels.min.js"></script>
		<script src="/js/init.js"></script>
		<script src="/js/jquery.formalize.min.js"></script>
		<script src="/js/jquery.timeago.js"></script>
		<link href="/css/jquery-ui.min.css" rel="stylesheet" type="text/css" />
		<link href="/css/jquery-ui.structure.min.css" rel="stylesheet" type="text/css" />
		<link href="/css/jquery-ui.theme.min.css" rel="stylesheet" type="text/css" />
		<link href="/css/formalize.css" rel="stylesheet" type="text/css" />
        <link href="/css/icons.css" rel="stylesheet" type="text/css" />
		<noscript>
			<link rel="stylesheet" href="/css/skel-noscript.css" />
			<link rel="stylesheet" href="/css/style.css" />
			<link rel="stylesheet" href="/css/style-desktop.css" />
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
                                        <li><a href="/admin/tests" title="Test Manager">Test Manager</a></li>
                                        <li><a href="/admin/office-symbols" title="Office Symbols">Office Symbols</a></li>
                                        <li><a href="/admin/users" title="User Manager">User Manager</a></li>
                                        <li><a href="/admin/profile" title="User Profiles">User Profiles</a></li>
                                        <li><a href="/admin/log" title="Log">Log</a></li>
                                        <li><a href="/admin/roles" title="Role Manager">Role Manager</a></li>
                                        <li><a href="/admin/upload" title="Upload File">Upload File</a></li>
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
				<div class="clearfix"></div>
			</div>
		</div>
		<!-- Main -->
		<div id="main">
			<?php if($router->getRoute() == "index"): ?>
			<div class="container">
				<div class="row">
					<div class="12u">
						<div class="informationMessages">
							This site is in beta testing status.  There are many incomplete features, but core testing functionality and some administration functionality is working.  <strong>The main
							goal of the redesign is to provide site usability to phone and tablet devices</strong>, and ensure speedy server response times.  Please take tests, and if you have the appropriate
							permissions, utilize administration features as you deem fit.  Two explicitly disabled features are registration and e-mail functionality (to prevent confusing other members).
							If you have any questions, or you encounter issues with the site, please <a href="http://helpdesk.cdcmastery.com">open a support ticket</a>.
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<div class="container" id="system-messages-container-block" style="display:none;">
				<div class="row">
					<div class="12u">
						<div class="systemMessages" id="system-messages-block">
							&nbsp;
						</div>
					</div>
				</div>
			</div>