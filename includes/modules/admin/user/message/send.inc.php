<?php
if(isset($_POST['messageBody'])){
    $messageSender      = $_SESSION['userEmail'];
    $messageRecipient   = $objUser->getUserEmail();
    $messageSubject     = $_POST['messageSubject'];
    $messageQueueUser   = $_SESSION['userUUID'];
    $messageBody        = $_POST['messageBody'];

    $emailBodyHTML	= "<html><head><title>".$messageSubject."</title></head><body>";
    $emailBodyHTML .= $objUser->getFullName().",";
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= nl2br($messageBody,true);
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= "Regards,";
    $emailBodyHTML .= "<br /><br />";
    $emailBodyHTML .= $user->getUserNameByUUID($_SESSION['userUUID']);
    $emailBodyHTML .= "</body></html>";

    $emailBodyText = $objUser->getFullName().",";
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= $messageBody;
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= "Regards,";
    $emailBodyText .= "\r\n\r\n";
    $emailBodyText .= $user->getUserNameByUUID($_SESSION['userUUID']);

    if(!$emailQueue->queueEmail($messageSender,$messageRecipient,$messageSubject,$emailBodyHTML,$emailBodyText,$messageQueueUser)){
        $log->setAction("ERROR_EMAIL_QUEUE");
        $log->setDetail("Calling Script","user/<user uuid>/message/send");
        $log->setDetail("Message Sender",$messageSender);
        $log->setDetail("Message Recipient",$messageRecipient);
        $log->setDetail("Message Subject",$messageSubject);
        $log->setDetail("HTML Body",$emailBodyHTML);
        $log->setDetail("Text Body",$emailBodyText);
        $log->setDetail("Queue User",$messageQueueUser);
        $log->saveEntry();
    }
    else{
        $_SESSION['messages'][] = "Message queued for delivery.";

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
                        <li><a href="/admin/users/<?php echo $userUUID; ?>"><i class="fa fa-caret-square-o-left fa-fw"></i>Return to user manager</a></li>
                    </ul>
                </div>
                <div class="clearfix"><br></div>
            </section>
        </div>
        <div class="6u">
            <section>
                <header>
                    <h2>Message Form</h2>
                </header>
                <form action="/admin/users/<?php echo $userUUID; ?>/message/send" method="POST">
                    <ul>
                        <li>
                            <label for="messageSubject">Subject</label>
                            <br>
                            <input type="text" id="messageSubject" name="messageSubject">
                        </li>
                        <li>
                            <label for="messageBody">Body</label>
                            <br>
                            <textarea id="messageBody" name="messageBody"></textarea>
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