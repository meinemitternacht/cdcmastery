<?php


namespace CDCMastery\Models\Auth\PasswordReset;


use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Users\User;
use DateTime;

class PasswordReset
{
    public const LIFETIME = '+3 days';

    private string $uuid;
    private DateTime $date_initiated;
    private DateTime $date_expires;
    private string $user_uuid;

    public function __construct(string $uuid, DateTime $date_initiated, DateTime $date_expires, string $user_uuid)
    {
        $this->uuid = $uuid;
        $this->date_initiated = $date_initiated;
        $this->date_expires = $date_expires;
        $this->user_uuid = $user_uuid;
    }

    public static function factory(User $user): PasswordReset
    {
        return new self(UUID::generate(),
                        new DateTime(),
                        (new DateTime())->modify(self::LIFETIME),
                        $user->getUuid());
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return DateTime
     */
    public function getDateInitiated(): DateTime
    {
        return $this->date_initiated;
    }

    /**
     * @return DateTime
     */
    public function getDateExpires(): DateTime
    {
        return $this->date_expires;
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->user_uuid;
    }
}