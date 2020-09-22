<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 9:30 PM
 */

namespace CDCMastery\Models\Users;


use DateTime;

class User
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $handle;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $legacyPassword;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $rank;

    /**
     * @var DateTime
     */
    private $dateRegistered;

    /**
     * @var DateTime
     */
    private $lastLogin;

    /**
     * @var DateTime
     */
    private $lastActive;

    /**
     * @var string
     */
    private $timeZone;

    /**
     * @var string
     */
    private $role;

    /**
     * @var string
     */
    private $officeSymbol;

    /**
     * @var string
     */
    private $base;

    /**
     * @var bool
     */
    private $disabled;

    /**
     * @var bool
     */
    private $reminderSent;

    public function assert_valid(): bool
    {
        return ($this->uuid ?? '') !== '';
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid ?? '';
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return implode(' ', [
            $this->getRank(),
            $this->getFirstName(),
            $this->getLastName()
        ]);
    }

    /**
     * @return string
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * @param string $handle
     */
    public function setHandle(string $handle): void
    {
        $this->handle = $handle;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return null|string
     */
    public function getLegacyPassword(): ?string
    {
        return $this->legacyPassword;
    }

    /**
     * @param null|string $legacyPassword
     */
    public function setLegacyPassword(?string $legacyPassword): void
    {
        $this->legacyPassword = $legacyPassword;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getRank(): string
    {
        return $this->rank;
    }

    /**
     * @param string $rank
     */
    public function setRank(string $rank): void
    {
        $this->rank = $rank;
    }

    /**
     * @return DateTime|null
     */
    public function getDateRegistered(): ?DateTime
    {
        return $this->dateRegistered;
    }

    /**
     * @param DateTime|null $dateRegistered
     */
    public function setDateRegistered(?DateTime $dateRegistered): void
    {
        $this->dateRegistered = $dateRegistered;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     */
    public function setLastLogin(?DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return DateTime|null
     */
    public function getLastActive(): ?DateTime
    {
        return $this->lastActive;
    }

    /**
     * @param DateTime|null $lastActive
     */
    public function setLastActive(?DateTime $lastActive): void
    {
        $this->lastActive = $lastActive;
    }

    /**
     * @return string
     */
    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    /**
     * @param string $timeZone
     */
    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @return null|string
     */
    public function getOfficeSymbol(): ?string
    {
        return $this->officeSymbol;
    }

    /**
     * @param null|string $officeSymbol
     */
    public function setOfficeSymbol(?string $officeSymbol): void
    {
        $this->officeSymbol = $officeSymbol;
    }

    /**
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
    }

    /**
     * @param string $base
     */
    public function setBase(string $base): void
    {
        $this->base = $base;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled ?? false;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /**
     * @return bool
     */
    public function isReminderSent(): bool
    {
        return $this->reminderSent ?? false;
    }

    /**
     * @param bool|null $reminderSent
     */
    public function setReminderSent(?bool $reminderSent): void
    {
        $this->reminderSent = $reminderSent;
    }
}