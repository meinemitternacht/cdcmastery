<?php
if(isset($_POST['messageBody'])){
    $messageSender      = $_SESSION['userEmail'];
    $messageRecipient   = $objUser->getUserEmail();
    $messageSubject     = "New Message from " . $userManager->getFullName() . " at CDCMastery.com";
    $messageQueueUser   = $_SESSION['userUUID'];
    $messageBody        = $_POST['messageBody'];

    $emailBodyHTML	= "<html><head><title>New Message from " . $userManager->getFullName() . " at CDCMastery.com</title></head><body>";
    $emailBodyHTML .= $objUser->getFullName().",";
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= "You have a new message from a manager at CDCMastery.com:";
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= nl2br($messageBody,true);
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= "Regards,";
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= $userManager->getUserNameByUUID($_SESSION['userUUID']);
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= "<i>Note: If you do not know the person who sent this message, or the contents are unprofessional, please forward this e-mail to admin@cdcmastery.com</i>";
    $emailBodyHTML .= "</body></html>";

    $emailBodyText = $objUser->getFullName().",";
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= "You have a new message from a manager at CDCMastery.com:";
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= $messageBody;
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= "Regards,";
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= $userManager->getUserNameByUUID($_SESSION['userUUID']);
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= "Note: If you do not know the person who sent this message, or the contents are unprofessional, please forward this e-mail to admin@cdcmastery.com";

    if(!$emailQueue->queueEmail($messageSender,$messageRecipient,$messageSubject,$emailBodyHTML,$emailBodyText,$messageQueueUser)){
        $systemLog->setAction("ERROR_EMAIL_QUEUE_ADD");
        $systemLog->setDetail("Calling Script", "user/".$objUser->getUUID()."/message/send");
        $systemLog->setDetail("Message Sender", $messageSender);
        $systemLog->setDetail("Message Recipient", $messageRecipient);
        $systemLog->setDetail("Message Subject", $messageSubject);
        $systemLog->setDetail("HTML Body", $emailBodyHTML);
        $systemLog->setDetail("Text Body", $emailBodyText);
        $systemLog->setDetail("Queue User", $messageQueueUser);
        $systemLog->saveEntry();

        $systemMessages->addMessage("There was a problem queueing the message for delivery.  Contact the Help Desk for assistance.", "danger");
    }
    else{
        $systemLog->setAction("SEND_USER_MESSAGE");
        $systemLog->setDetail("Calling Script", "user/".$objUser->getUUID()."/message/send");
        $systemLog->setDetail("Message Sender", $messageSender);
        $systemLog->setDetail("Message Recipient", $messageRecipient);
        $systemLog->setDetail("Message Subject", $messageSubject);
        $systemLog->setDetail("HTML Body", $emailBodyHTML);
        $systemLog->setDetail("Text Body", $emailBodyText);
        $systemLog->setDetail("Queue User", $messageQueueUser);
        $systemLog->saveEntry();

        $systemMessages->addMessage("Message queued for delivery.", "success");
    }
}
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2><em><?php echo $objUser->getFullName(); ?></em></h2>
                </header>
                <div class="sub-menu">
                    <div class="menu-heading">
                        Send Message
                    </div>
                    <ul>
                        <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to user manager</a></li>
                    </ul>
                </div>
                <div class="clearfix">&nbsp;</div>
            </section>
        </div>
        <div class="6u">
            <section>
                <header>
                    <h2>Message Form</h2>
                </header>
                <p>
                    Please be professional and courteous when sending messages to users, <strong>especially if they do not know you</strong>.
                    All messages are logged for security purposes, and the user is instructed to forward all questionable messages to the site
                    administrator.
                </p>
                <form action="/admin/users/<?php echo $userUUID; ?>/message/send" method="POST">
                    <ul>
                        <li>
                            <label for="messageSubject">Subject</label>
                            <br>
                            <input class="input_full" type="text" id="messageSubject" name="messageSubject" value="New Message from <?php echo $userManager->getFullName(); ?> at CDCMastery.com" DISABLED="true">
                        </li>
                        <li>
                            <label for="messageBody">Body</label>
                            <br>
                            <textarea class="input_full" id="messageBody" name="messageBody" style="height:6em;"></textarea>
                        </li>
                        <li>
                            <br>
                            <input type="submit" value="Send">
                        </li>
                    </ul>
                </form>
            </section>
        </div>
    </div>
</div>