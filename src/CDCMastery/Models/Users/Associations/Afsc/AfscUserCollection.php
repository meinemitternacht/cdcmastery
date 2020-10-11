<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/9/2017
 * Time: 9:01 PM
 */

namespace CDCMastery\Models\Users\Associations\Afsc;


class AfscUserCollection
{
    /** @var string[] $users */
    private array $users;
    private string $afsc;

    public function addUser(string $userUuid): void
    {
        $this->users[] = $userUuid;
    }

    /**
     * @return string[]
     */
    public function getUsers(): array
    {
        return $this->users ?? [];
    }

    /**
     * @param string[] $users
     */
    public function setUsers(array $users): void
    {
        $this->users = $users;
    }

    public function getAfsc(): string
    {
        return $this->afsc ?? '';
    }

    public function setAfsc(string $afsc): void
    {
        $this->afsc = $afsc;
    }
}
