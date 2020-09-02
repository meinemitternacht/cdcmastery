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
     * @var string[]|null
     */
    private $authorized;

    /**
     * @var string[]|null
     */
    private $pending;

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
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @param string $afscUuid
     */
    public function addAfsc(string $afscUuid): void
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
    public function setAfscs(array $afscs): void
    {
        $this->afscs = $afscs;
    }

    /**
     * @return string[]|null
     */
    public function getAuthorized(): ?array
    {
        return $this->authorized;
    }

    /**
     * @param string[]|null $authorized
     */
    public function setAuthorized(?array $authorized): void
    {
        $this->authorized = $authorized;
    }

    /**
     * @return string[]|null
     */
    public function getPending(): ?array
    {
        return $this->pending;
    }

    /**
     * @param string[]|null $pending
     */
    public function setPending(?array $pending): void
    {
        $this->pending = $pending;
    }
}