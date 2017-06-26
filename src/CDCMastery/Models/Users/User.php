<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 9:30 PM
 */

namespace CDCMastery\Models\Users;


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
     * @var \DateTime
     */
    private $dateRegistered;

    /**
     * @var \DateTime
     */
    private $lastLogin;

    /**
     * @var \DateTime
     */
    private $lastActive;

    /**
     * @var \DateTime
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

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid)
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
    public function setFirstName(string $firstName)
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
    public function setLastName(string $lastName)
    {
        $this->lastName = $lastName;
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
    public function setHandle(string $handle)
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
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getLegacyPassword(): string
    {
        return $this->legacyPassword;
    }

    /**
     * @param string $legacyPassword
     */
    public function setLegacyPassword(string $legacyPassword)
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
    public function setEmail(string $email)
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
    public function setRank(string $rank)
    {
        $this->rank = $rank;
    }

    /**
     * @return \DateTime
     */
    public function getDateRegistered(): \DateTime
    {
        return $this->dateRegistered;
    }

    /**
     * @param \DateTime $dateRegistered
     */
    public function setDateRegistered(\DateTime $dateRegistered)
    {
        $this->dateRegistered = $dateRegistered;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin(): \DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     */
    public function setLastLogin(\DateTime $lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return \DateTime
     */
    public function getLastActive(): \DateTime
    {
        return $this->lastActive;
    }

    /**
     * @param \DateTime $lastActive
     */
    public function setLastActive(\DateTime $lastActive)
    {
        $this->lastActive = $lastActive;
    }

    /**
     * @return \DateTime
     */
    public function getTimeZone(): \DateTime
    {
        return $this->timeZone;
    }

    /**
     * @param \DateTime $timeZone
     */
    public function setTimeZone(\DateTime $timeZone)
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
    public function setRole(string $role)
    {
        $this->role = $role;
    }

    /**
     * @return string
     */
    public function getOfficeSymbol(): string
    {
        return $this->officeSymbol;
    }

    /**
     * @param string $officeSymbol
     */
    public function setOfficeSymbol(string $officeSymbol)
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
    public function setBase(string $base)
    {
        $this->base = $base;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return bool
     */
    public function isReminderSent(): bool
    {
        return $this->reminderSent;
    }

    /**
     * @param bool $reminderSent
     */
    public function setReminderSent(bool $reminderSent)
    {
        $this->reminderSent = $reminderSent;
    }
}