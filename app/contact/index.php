<?php 
if(!empty($_POST) && isset($_POST['contactReason'])){
	$contactReason = $_POST['contactReason'];
	$userMessage = htmlspecialchars($_POST['userMessage']);
	
	if($contactReason && $userMessage){
		$user->loadUser($_SESSION['userUUID']);
		$userEmail = $user->getUserEmail();
		$userFullName = $user->getFullName();
		$userRole = $roles->getRoleName($user->getUserRole());
		
		$emailSender = "<".$userEmail.">";
		$emailRecipient = "support@cdcmastery.com";
		$emailSubject = "New message from ".$userFullName;
		
		$emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
		$emailBodyHTML .= "CDCMastery.com Administrator,";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "You have received a new message from ".$userFullName.".  ";
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "Contact Reason: ".$contactReason;
		$emailBodyHTML .= "<br /><br />";
		$emailBodyHTML .= "Message:<br />";
		$emailBodyHTML .= $userMessage;
		$emailBodyHTML .= "</body></html>";
		
		$emailBodyText = $this->getFullName().",";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "You have received a new message from ".$userFullName.".  ";
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Contact Reason: ".$contactReason;
		$emailBodyText .= "\r\n\r\n";
		$emailBodyText .= "Message:\r\n";
		$emailBodyText .= htmlspecialchars_decode($userMessage);
		
		if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $_SESSION['userUUID'])){
			$this->log->setAction("SITE_CONTACT_FORM_SUBMIT");
			$this->log->setDetail("User E-mail",$userEmail);
			$this->log->setDetail("Message",$userMessage);
			$this->log->setDetail("Contact Reason",$contactReason);
			$this->log->saveEntry();
			
			$messages[] = "Message sent.  Someone will be in touch with you shortly!";
		}
		else{
			$this->log->setAction("ERROR_SITE_CONTACT_FORM_SUBMIT");
			$this->log->setDetail("Calling Page","/contact");
			$this->log->setDetail("Function","emailQueue->queueEmail()");
			$this->log->setDetail("User E-mail",$userEmail);
			$this->log->setDetail("Message",$userMessage);
			$this->log->setDetail("Contact Reason",$contactReason);
			$this->log->saveEntry();

			$messages[] = "Sorry, we could not send your message.  Please e-mail CDCMastery Support at support@cdcmastery.com";
		}
	}
	else{
		$messages[] = "You must provide a contact reason and message.";
	}
}
?>
<div class="container">
	<div class="row">
		<div class="6u">
			<section>
				<header>
					<h2>Contact Us</h2>
				</header>
			</section>
			<p>Thank you for your interest in CDCMastery.com!  If you have any questions or concerns about this site, please do not hesitate to contact us using the methods listed below.</p>
			<h3><strong>E-mail</strong></h3>
			<div class="tablecloth">
				<table cellspacing="0" cellpadding="0">
					<tr>
						<th>General Information</th>
						<td>info@cdcmastery.com</td>
					</tr>
					<tr>
						<th>Support</th>
						<td>support@cdcmastery.com</td>
					</tr>
					<tr>
						<th>Site Administration</th>
						<td>admin@cdcmastery.com</td>
					</tr>
				</table>
			</div>
		</div>
		<?php if($_SESSION['auth']): ?>
		<div class="6u">
			<p>If you would prefer to send a message using the site, please fill out the form below:</p>
			<form action="/contact" method="POST">
				<strong>Select a contact reason</strong>
				<br />
				<select name="contactReason" size="1">
					<option value="general_feedback" SELECTED>General Feedback</option>
					<option value="cdc_access">Request Access to CDC Data</option>
					<option value="technical_support">Technical Support</option>
					<option value="feature_request">Feature Request</option>
					<option value="cdc_questions_answers">Incorrect CDC Questions / Answers</option>
					<option value="add_cdc_data">Add CDC Data to the Database</option>
				</select>
				<br />
				<br />
				<strong>Message Body</strong>
				<br />
				<textarea name="userMessage" style="width:100%;height:150px;"></textarea>
				<br />
				<br />
				<input type="submit" value="Send Message" />
			</form>
		</div>
		<?php endif; ?>
	</div>
</div>