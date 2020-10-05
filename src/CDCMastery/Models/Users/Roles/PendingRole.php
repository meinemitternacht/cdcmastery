<?php
declare(strict_types=1);


namespace CDCMastery\Models\Users\Roles;


use DateTime;

class PendingRole
{
    private string $user_uuid;
    private string $role_uuid;
    private DateTime $date_requested;

    public function __construct(string $user_uuid, string $role_uuid, DateTime $date_requested)
    {
        $this->user_uuid = $user_uuid;
        $this->role_uuid = $role_uuid;
        $this->date_requested = $date_requested;
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->user_uuid;
    }

    /**
     * @return string
     */
    public function getRoleUuid(): string
    {
        return $this->role_uuid;
    }

    /**
     * @return DateTime
     */
    public function getDateRequested(): DateTime
    {
        return $this->date_requested;
    }
}