<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/29/2017
 * Time: 8:52 PM
 */

namespace CDCMastery\Models\Email;


use CDCMastery\Helpers\DateTimeHelpers;
use DateTime;
use Exception;
use Monolog\Logger;
use mysqli;

class EmailCollection
{
    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * EmailCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $uuid
     */
    public function delete(string $uuid): void
    {
        if ($uuid === '') {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM emailQueue
WHERE uuid = '{$uuid}'
SQL;

        $this->db->query($qry);
    }

    /**
     * @param array $uuids
     */
    public function deleteAll(array $uuids): void
    {
        if (count($uuids) === 0) {
            return;
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
DELETE FROM emailQueue
WHERE uuid IN ('{$uuids_str}')
SQL;

        $this->db->query($qry);
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  queueTime,
  emailSender,
  emailRecipient,
  emailSubject,
  emailBodyHTML,
  emailBodyText,
  queueUser
FROM emailQueue
SQL;

        $res = $this->db->query($qry);

        $emails = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $email = new Email();
            $email->setUuid($row['uuid'] ?? '');
            $email->setQueueTime(DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                            $row['queueTime'] ?? ''));
            $email->setSender($row['emailSender'] ?? '');
            $email->setRecipient($row['emailRecipient'] ?? '');
            $email->setSubject($row['emailSubject'] ?? '');
            $email->setBodyHtml($row['emailBodyHTML'] ?? '');
            $email->setBodyTxt($row['emailBodyText'] ?? '');
            $email->setUserUuid($row['queueUser'] ?? '');

            $emails[$row['uuid']] = $email;
        }

        $res->free();

        return $emails;
    }

    /**
     * @param Email $email
     * @throws Exception
     */
    public function queue(Email $email): void
    {
        if (($email->getUuid() ?? '') === '') {
            return;
        }

        $uuid = $email->getUuid();
        $queueTime = $email->getQueueTime()
                           ->format(DateTimeHelpers::DT_FMT_DB);
        $sender = $email->getSender();
        $recipient = $email->getRecipient();
        $subject = $email->getSubject();
        $bodyHtml = $email->getBodyHtml();
        $bodyText = $email->getBodyTxt();
        $userUuid = $email->getUserUuid();

        $qry = <<<SQL
INSERT INTO emailQueue
  (
    uuid, 
    queueTime, 
    emailSender, 
    emailRecipient, 
    emailSubject, 
    emailBodyHTML, 
    emailBodyText, 
    queueUser
  )
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid), 
  queueTime=VALUES(queueTime), 
  emailSender=VALUES(emailSender), 
  emailRecipient=VALUES(emailRecipient), 
  emailSubject=VALUES(emailSubject), 
  emailBodyHTML=VALUES(emailBodyHTML), 
  emailBodyText=VALUES(emailBodyText), 
  queueUser=VALUES(queueUser) 
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssssssss',
            $uuid,
            $queueTime,
            $sender,
            $recipient,
            $subject,
            $bodyHtml,
            $bodyText,
            $userUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param array $emails
     * @throws Exception
     */
    public function queueArray(array $emails): void
    {
        foreach ($emails as $email) {
            $this->queue($email);
        }
    }
}