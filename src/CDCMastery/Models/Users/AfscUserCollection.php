<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/9/2017
 * Time: 9:01 PM
 */

namespace CDCMastery\Models\Users;


class AfscUserCollection
{
    /**
     * @var string[]
     */
    private $users;

    /**
     * @var string
     */
    private $afsc;

    /**
     * @param string $userUuid
     */
    public function addUser(string $userUuid) {
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
    public function setUsers(array $users)
    {
        $this->users = $users;
    }

    /**
     * @return string
     */
    public function getAfsc(): string
    {
        return $this->afsc ?? '';
    }

    /**
     * @param string $afsc
     */
    public function setAfsc(string $afsc)
    {
        $this->afsc = $afsc;
    }
}