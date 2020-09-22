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
    private string $uuid;
    private DateTime $queueTime;
    private string $sender;
    private string $recipient;
    private string $subject;
    private string $bodyHtml;
    private string $bodyTxt;
    private string $userUuid;

    public function getUuid(): string
    {
        return $this->uuid;
    }

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

    public function setQueueTime(DateTime $queueTime): void
    {
        $this->queueTime = $queueTime;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function setBodyHtml(string $bodyHtml): void
    {
        $this->bodyHtml = $bodyHtml;
    }

    public function getBodyTxt(): string
    {
        return $this->bodyTxt;
    }

    public function setBodyTxt(string $bodyTxt): void
    {
        $this->bodyTxt = $bodyTxt;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function setUserUuid(string $userUuid): void
    {
        $this->userUuid = $userUuid;
    }
}