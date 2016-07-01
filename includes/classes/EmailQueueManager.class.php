<?php
class EmailQueueManager extends CDCMastery {
	protected $db;
	protected $log;

    public $error;
	
	private $smtpHost;
	private $smtpPort;
	private $smtpUsername;
	private $smtpPassword;

	public $uuid;
    public $queueTime;
    public $emailSender;
    public $emailRecipient;
    public $emailSubject;
    public $emailBody;
    public $emailBodyHTML;
    public $emailBodyText;
    public $queueUser;

	public function __construct(mysqli $db, SystemLog $log, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword){
		$this->db = $db;
		$this->log = $log;
		$this->smtpHost = $smtpHost;
		$this->smtpPort = $smtpPort;
		$this->smtpUsername = $smtpUsername;
		$this->smtpPassword = $smtpPassword;
		$this->uuid = parent::genUUID();
	}

	public function processQueue(){
		$res = $this->db->query("SELECT uuid,
										queueTime,
										emailSender,
										emailRecipient,
										emailSubject,
										emailBodyHTML,
										emailBodyText,
										queueUser
									FROM emailQueue
									ORDER BY queueTime ASC");

		if($res->num_rows > 0){
			$error = false;
			while($row = $res->fetch_assoc()){
				$this->uuid = $row['uuid'];
				$this->queueTime = $row['queueTime'];
				$this->emailSender = $row['emailSender'];
				$this->emailRecipient = $row['emailRecipient'];
				$this->emailSubject = $row['emailSubject'];
				$this->emailBodyHTML = $row['emailBodyHTML'];
				$this->emailBodyText = $row['emailBodyText'];
				$this->queueUser = $row['queueUser'];

				if($this->sendEmail()){
					if(!$this->db->query("DELETE FROM emailQueue WHERE uuid = '".$this->uuid."'")){
						$this->log->setAction("ERROR_EMAIL_QUEUE_REMOVE");
						$this->log->setDetail("UUID", $this->uuid);
						$this->log->setDetail("MySQL Error",$this->db->error);
						$this->log->saveEntry();

						$error = true;
					}
				}
				else{
					$error = true;
				}
			}

			if($error == false){
				return true;
			}
			else{
				$this->log->setAction("ERROR_EMAIL_QUEUE_PROCESS");
				$this->log->setDetail("UUID", $this->uuid);
				$this->log->setDetail("MySQL Error",$this->db->error);
				$this->log->saveEntry();
				return false;
			}
		}
		else{
			return true;
		}
	}

	public function queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser){
		$this->uuid = parent::genUUID();
		$this->emailSender = $emailSender;
		$this->emailRecipient = $emailRecipient;
		$this->emailSubject = $emailSubject;
		$this->emailBodyHTML = $emailBodyHTML;
		$this->emailBodyText = $emailBodyText;
		$this->queueUser = $queueUser;

		$stmt = $this->db->prepare("INSERT INTO emailQueue (uuid,
															emailSender,
															emailRecipient,
															emailSubject,
															emailBodyHTML,
															emailBodyText,
															queueUser)
												VALUES (?,?,?,?,?,?,?)");

		$stmt->bind_param("sssssss",$this->uuid, $this->emailSender, $this->emailRecipient, $this->emailSubject, $this->emailBodyHTML, $this->emailBodyText, $this->queueUser);

		if(!$stmt->execute()){
            $this->error = $stmt->error;
			if(!isset($_SESSION['userUUID']) || empty($_SESSION['userUUID']))
				$this->log->setUserUUID($queueUser);
			$this->log->setAction("ERROR_EMAIL_QUEUE_ADD");
			$this->log->setDetail("MySQL Error",$stmt->error);
			$this->log->setDetail("UUID",$this->uuid);
			$this->log->setDetail("Sender",$this->emailSender);
			$this->log->setDetail("Recipient",$this->emailRecipient);
			$this->log->setDetail("Subject",$this->emailSubject);
			$this->log->setDetail("Body (HTML)",$this->emailBodyHTML);
			$this->log->setDetail("Body (Text)",$this->emailBodyText);
			$this->log->setDetail("Queued By",$this->queueUser);
			$this->log->saveEntry();
			return false;
		}
		else{
			if(!isset($_SESSION['userUUID']) || empty($_SESSION['userUUID']))
				$this->log->setUserUUID($queueUser);
            $this->log->setAction("EMAIL_QUEUE_ADD");
            $this->log->setDetail("UUID",$this->uuid);
            $this->log->setDetail("Sender",$this->emailSender);
            $this->log->setDetail("Recipient",$this->emailRecipient);
            $this->log->setDetail("Subject",$this->emailSubject);
            $this->log->setDetail("Body (HTML)",$this->emailBodyHTML);
            $this->log->setDetail("Body (Text)",$this->emailBodyText);
            $this->log->setDetail("Queued By",$this->queueUser);
            $this->log->saveEntry();
			return true;
		}
	}

	public function sendEmail(){
		$crlf = "\n";
		$mime = new Mail_mime($crlf);
		$mime->setTXTBody($this->emailBodyText);
		$mime->setHTMLBody($this->emailBodyHTML);
		$this->emailBody = $mime->get();

		$emailHeader = array ('From' => $this->emailSender, 'To' => $this->emailRecipient, 'Subject' => $this->emailSubject);
		$emailHeader = $mime->headers($emailHeader);
		$smtp = Mail::factory('smtp',
				array ('host' => $this->smtpHost,
						'auth' => true,
						'username' => $this->smtpUsername,
						'password' => $this->smtpPassword,
						'port' => $this->smtpPort
				));

		$mail = $smtp->send($this->emailRecipient, $emailHeader, $this->emailBody);

		if (PEAR::isError($mail)){
			$this->log->setAction("ERROR_EMAIL_SEND");
			$this->log->setDetail("MAIL_ERROR",$mail->getMessage());
			$this->log->setDetail("UUID",$this->uuid);
			$this->log->saveEntry();
			return false;
		}
		else{
			$this->log->setAction("EMAIL_SEND");
			$this->log->setDetail("UUID",$this->uuid);
			$this->log->saveEntry();
			return true;
		}
	}

	public function __destruct(){
		parent::__destruct();
	}
}