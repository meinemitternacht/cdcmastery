<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:57 PM
 */

namespace CDCMastery\Models\Email;


use DateTime;
use Exception;

class Email
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var DateTime
     */
    private $queueTime;

    /**
     * @var string
     */
    private $sender;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $bodyHtml;

    /**
     * @var string
     */
    private $bodyTxt;

    /**
     * @var string
     */
    private $userUuid;

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getQueueTime(): DateTime
    {
        return $this->queueTime ?? new DateTime();
    }

    /**
     * @param DateTime $queueTime
     */
    public function setQueueTime(DateTime $queueTime): void
    {
        $this->queueTime = $queueTime;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     */
    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * @param string $recipient
     */
    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    /**
     * @param string $bodyHtml
     */
    public function setBodyHtml(string $bodyHtml): void
    {
        $this->bodyHtml = $bodyHtml;
    }

    /**
     * @return string
     */
    public function getBodyTxt(): string
    {
        return $this->bodyTxt;
    }

    /**
     * @param string $bodyTxt
     */
    public function setBodyTxt(string $bodyTxt): void
    {
        $this->bodyTxt = $bodyTxt;
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    /**
     * @param string $userUuid
     */
    public function setUserUuid(string $userUuid): void
    {
        $this->userUuid = $userUuid;
    }
}