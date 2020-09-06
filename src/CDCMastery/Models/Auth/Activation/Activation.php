<?php


namespace CDCMastery\Models\Auth\Activation;


use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Users\User;
use DateTime;

class Activation
{
    private const LIFETIME = '+7 days';

    private string $code;
    private string $user_uuid;
    private DateTime $date_expires;

    public function __construct(string $code, string $user_uuid, DateTime $date_expires)
    {
        $this->code = $code;
        $this->user_uuid = $user_uuid;
        $this->date_expires = $date_expires;
    }

    public static function factory(User $user): Activation
    {
        return new self(UUID::generate(),
                        $user->getUuid(),
                        (new DateTime())->modify(self::LIFETIME));
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->user_uuid;
    }

    /**
     * @return DateTime
     */
    public function getDateExpires(): DateTime
    {
        return $this->date_expires;
    }
}