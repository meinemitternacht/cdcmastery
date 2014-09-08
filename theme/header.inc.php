<!DOCTYPE HTML>
<html>
	<head>
		<title>CDCMastery</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="CDCMastery is a tool to help you succeed on the Air Force CDC EOC tests, Promotion Tests and other assessments of your career knowledge." />
		<meta name="keywords" content="air force cdc, cdc pretest, career development course, career development course pretests, cdc tests, skt study guide, cdc study guide, air force cdc study guide" />
		<meta name="revisit-after" content="30 days" />
		<link href='http://fonts.googleapis.com/css?family=Arimo:400,700' rel='stylesheet' type='text/css'>
		<!--[if lte IE 8]><script src="/js/html5shiv.js"></script><![endif]-->
		<script src="/js/jquery-1.11.1.min.js"></script>
		<script src="/js/jquery-ui.min.js"></script>
		<script src="/js/jquery-ui-timepicker.js"></script>
		<script src="/js/jquery-mask.min.js"></script>
		<script src="/js/tablecloth.js"></script>
		<script src="/js/skel.min.js"></script>
		<script src="/js/skel-panels.min.js"></script>
		<script src="/js/init.js"></script>
		<link rel="stylesheet" href="/css/jquery-ui.min.css" />
		<link rel="stylesheet" href="/css/jquery-ui.structure.min.css" />
		<link rel="stylesheet" href="/css/jquery-ui.theme.min.css" />
		<noscript>
			<link rel="stylesheet" href="/css/skel-noscript.css" />
			<link rel="stylesheet" href="/css/style.css" />
			<link rel="stylesheet" href="/css/style-desktop.css" />
		</noscript>
		<!--[if lte IE 8]><link rel="stylesheet" href="/css/ie/v8.css" /><![endif]-->
		<!--[if lte IE 9]><link rel="stylesheet" href="/css/ie/v9.css" /><![endif]-->
	</head>
	<body>

		<!-- Header -->
		<div id="header">
			<div class="container"> 
				
				<!-- Logo -->
				<div id="logo">
					<h1><a href="/"><img src="/images/logo.png" alt="CDCMastery.com - Learning Enabled" title="CDCMastery.com - Learning Enabled" /></a></h1>
				</div>
				
				<!-- Nav -->
				<nav id="nav">
					<ul>
						<li<?php if($router->getSiteSection() == "index"): ?> class="active"<?php endif; ?>><a href="/">Home</a></li>
						<li<?php if($router->getSiteSection() == "about"): ?> class="active"<?php endif; ?>><a href="/about">About</a></li>
						<li<?php if($router->getSiteSection() == "contact"): ?> class="active"<?php endif; ?>><a href="/contact">Contact</a></li>
						<?php if($cdcMastery->loggedIn()): ?>
							<?php if($cdcMastery->verifyAdmin()): ?>
								<li<?php if($router->getSiteSection() == "admin"): ?> class="active"<?php endif; ?>><a href="/admin">Admin Panel</a></li>
							<?php elseif($cdcMastery->verifyTrainingManager()): ?>
								<li<?php if($router->getSiteSection() == "training"): ?> class="active"<?php endif; ?>><a href="/training/overview">Training Overview</a></li>
							<?php elseif($cdcMastery->verifySupervisor()): ?>
								<li<?php if($router->getSiteSection() == "supervisor"): ?> class="active"<?php endif; ?>><a href="/supervisor/overview">Supervisor Overview</a></li>
							<?php endif; ?>
							<li<?php if($router->getSiteSection() == "auth"): ?> class="active"<?php endif; ?>><a href="/auth/logout">Logout</a></li>
						<?php else: ?>
						<li<?php if($router->getSiteSection() == "auth"): ?> class="active"<?php endif; ?>><a href="/auth/login">Login</a></li>
						<?php endif; ?>
					</ul>
				</nav>
			</div>
		</div>
		
		<!-- Main -->
		<div id="main">
			<div class="container">
				<div class="row">