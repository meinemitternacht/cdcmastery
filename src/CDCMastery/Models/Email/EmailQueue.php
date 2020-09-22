<?php


namespace CDCMastery\Models\Email;


use Monolog\Logger;
use RuntimeException;
use Swift_Mailer;
use Swift_Message;

class EmailQueue
{
    private Logger $log;
    private EmailCollection $emails;
    private Swift_Mailer $mailer;
    /** @var resource $mutex */
    private $mutex;

    public function __construct(Logger $log, EmailCollection $emails, Swift_Mailer $mailer)
    {
        $this->log = $log;
        $this->emails = $emails;
        $this->mailer = $mailer;
        $this->mutex = sem_get(ftok(__FILE__, 'e'));
    }

    private static function to_swift_message(Email $email): Swift_Message
    {
        return (new Swift_Message($email->getSubject()))
            ->setFrom([$email->getSender() => 'CDCMastery.com'])
            ->setTo($email->getRecipient())
            ->setBody($email->getBodyTxt())
            ->setReplyTo(['support@cdcmastery.com' => 'CDCMastery.com']);
    }

    private function lock(): void
    {
        if (!sem_acquire($this->mutex, true)) {
            $msg = 'unable to acquire e-mail queue lock';
            $this->log->debug($msg);
            throw new RuntimeException($msg);
        }
    }

    private function unlock(): void
    {
        sem_release($this->mutex);
    }

    public function process(): void
    {
        try {
            $this->lock();
            $emails = $this->emails->fetchAll();

            if (!$emails) {
                return;
            }

            $success = [];
            foreach ($emails as $email) {
                if ($this->send($email)) {
                    $success[] = $email;
                }
            }

            if ($success) {
                $this->emails->deleteArray($success);
            }
        } finally {
            $this->unlock();
        }
    }

    private function send(Email $message): bool
    {
        if (!$this->mailer->send(self::to_swift_message($message))) {
            $this->log->error(
                "email send failed :: uuid {$message->getUuid()} :: to '{$message->getRecipient()}' :: subject '{$message->getSubject()}'"
            );

            return false;
        }

        $this->log->info(
            "email sent :: uuid {$message->getUuid()} :: to '{$message->getRecipient()}' :: subject '{$message->getSubject()}'"
        );

        return true;
    }
}