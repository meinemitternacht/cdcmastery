<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/29/2017
 * Time: 8:52 PM
 */

namespace CDCMastery\Models\Email;


use CDCMastery\Helpers\DateTimeHelpers;
use Monolog\Logger;

class EmailCollection
{
    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Email[]
     */
    private $emails = [];

    /**
     * EmailCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $uuid
     */
    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }
        
        $uuid = $this->db->real_escape_string($uuid);
        
        $qry = <<<SQL
DELETE FROM emailQueue
WHERE uuid = '{$uuid}'
SQL;

        $this->db->query($qry);

        if (isset($this->emails[$uuid])) {
            array_splice(
                $this->emails,
                array_search(
                    $uuid,
                    $this->emails
                ),
                1
            );
        }
    }

    /**
     * @param array $uuidList
     */
    public function deleteAll(array $uuidList): void
    {
        if (empty($uuidList)) {
            return;
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
DELETE FROM emailQueue
WHERE uuid IN ('{$uuidListString}')
SQL;

        $this->db->query($qry);
        $this->emails = [];
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
        
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid']) || empty($row['uuid'])) {
                continue;
            }
            
            $email = new Email();
            $email->setUuid($row['uuid'] ?? '');
            $email->setQueueTime(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['queueTime'] ?? ''
                )
            );
            $email->setSender($row['emailSender'] ?? '');
            $email->setRecipient($row['emailRecipient'] ?? '');
            $email->setSubject($row['emailSubject'] ?? '');
            $email->setBodyHtml($row['emailBodyHTML'] ?? '');
            $email->setBodyTxt($row['emailBodyText'] ?? '');
            $email->setUserUuid($row['queueUser'] ?? '');
            
            $this->emails[$row['uuid']] = $email;
        }
        
        return $this->emails;
    }

    /**
     * @param Email $email
     */
    public function save(Email $email): void
    {
        if (empty($email->getUuid())) {
            return;
        }
        
        $uuid = $email->getUuid();
        $queueTime = $email->getQueueTime()->format(
            DateTimeHelpers::FMT_DATABASE
        );
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
        $this->emails[$uuid] = $email;
    }

    /**
     * @param array $emails
     */
    public function saveArray(array $emails): void
    {
        if (empty($emails)) {
            return;
        }

        $c = count($emails);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($emails[$i])) {
                continue;
            }

            if (!$emails[$i] instanceof Email) {
                continue;
            }

            $this->save($emails[$i]);
        }
    }
}