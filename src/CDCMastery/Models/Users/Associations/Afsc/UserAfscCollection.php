<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/9/2017
 * Time: 9:01 PM
 */

namespace CDCMastery\Models\Users\Associations\Afsc;


class UserAfscCollection
{
    private string $user;
    /** @var string[] */
    private array $afscs;
    /** @var string[]|null */
    private ?array $authorized;
    /** @var string[]|null */
    private ?array $pending;

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    public function addAfsc(string $afscUuid): void
    {
        $this->afscs[] = $afscUuid;
    }

    /**
     * @return string[]
     */
    public function getAfscs(): array
    {
        return $this->afscs;
    }

    /**
     * @param string[] $afscs
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