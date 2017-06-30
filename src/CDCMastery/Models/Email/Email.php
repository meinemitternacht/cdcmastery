<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:57 PM
 */

namespace CDCMastery\Models\Email;


class Email
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var \DateTime
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
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return \DateTime
     */
    public function getQueueTime(): \DateTime
    {
        return $this->queueTime ?? new \DateTime();
    }

    /**
     * @param \DateTime $queueTime
     */
    public function setQueueTime(\DateTime $queueTime)
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
    public function setSender(string $sender)
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
    public function setRecipient(string $recipient)
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
    public function setSubject(string $subject)
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
    public function setBodyHtml(string $bodyHtml)
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
    public function setBodyTxt(string $bodyTxt)
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
    public function setUserUuid(string $userUuid)
    {
        $this->userUuid = $userUuid;
    }
}