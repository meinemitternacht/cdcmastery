<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/29/2017
 * Time: 8:52 PM
 */

namespace CDCMastery\Models\Email;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use DateTime;
use Exception;
use Monolog\Logger;
use mysqli;

class EmailCollection
{
    protected mysqli $db;
    protected Logger $log;

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

    public function delete(Email $email): void
    {
        $uuid = $email->getUuid();

        if (!$uuid) {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM emailQueue
WHERE uuid = '{$uuid}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    /**
     * @param Email[] $emails
     */
    public function deleteArray(array $emails): void
    {
        foreach ($emails as $email) {
            $this->delete($email);
        }
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

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $emails = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $queue_time = DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                     $row[ 'queueTime' ] ?? '',
                                                     DateTimeHelpers::utc_tz());
            $queue_time->setTimezone(DateTimeHelpers::user_tz());

            $email = new Email();
            $email->setUuid($row[ 'uuid' ] ?? '');
            $email->setQueueTime($queue_time);
            $email->setSender($row[ 'emailSender' ] ?? '');
            $email->setRecipient($row[ 'emailRecipient' ] ?? '');
            $email->setSubject($row[ 'emailSubject' ] ?? '');
            $email->setBodyHtml($row[ 'emailBodyHTML' ] ?? '');
            $email->setBodyTxt($row[ 'emailBodyText' ] ?? '');
            $email->setUserUuid($row[ 'queueUser' ] ?? '');

            $emails[ $row[ 'uuid' ] ] = $email;
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
                           ->setTimezone(DateTimeHelpers::utc_tz())
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssssssss',
                               $uuid,
                               $queueTime,
                               $sender,
                               $recipient,
                               $subject,
                               $bodyHtml,
                               $bodyText,
                               $userUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
