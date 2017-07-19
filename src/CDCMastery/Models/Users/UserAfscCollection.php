<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/9/2017
 * Time: 9:01 PM
 */

namespace CDCMastery\Models\Users;


class UserAfscCollection
{
    /**
     * @var string
     */
    private $user;

    /**
     * @var string[]
     */
    private $afscs;

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $afscUuid
     */
    public function addAfsc(string $afscUuid)
    {
        $this->afscs[] = $afscUuid;
    }

    /**
     * @return \string[]
     */
    public function getAfscs(): array
    {
        return $this->afscs;
    }

    /**
     * @param \string[] $afscs
     */
    public function setAfscs(array $afscs)
    {
        $this->afscs = $afscs;
    }
}