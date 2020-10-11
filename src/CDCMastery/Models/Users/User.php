<?php
declare(strict_types=1);
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
    private string $uuid;
    private string $firstName;
    private string $lastName;
    private string $handle;
    private ?string $password;
    private ?string $legacyPassword;
    private string $email;
    private string $rank;
    private ?DateTime $dateRegistered;
    private ?DateTime $lastLogin;
    private ?DateTime $lastActive;
    private string $timeZone;
    private string $role;
    private ?string $officeSymbol;
    private string $base;
    private bool $disabled;
    private bool $reminderSent;

    public function assert_valid(): bool
    {
        return ($this->uuid ?? '') !== '';
    }

    public function getUuid(): string
    {
        return $this->uuid ?? '';
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getName(): string
    {
        return implode(' ', [
            $this->getRank(),
            $this->getFirstName(),
            $this->getLastName()
        ]);
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): void
    {
        $this->handle = $handle;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getLegacyPassword(): ?string
    {
        return $this->legacyPassword;
    }

    public function setLegacyPassword(?string $legacyPassword): void
    {
        $this->legacyPassword = $legacyPassword;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function setRank(string $rank): void
    {
        $this->rank = $rank;
    }

    public function getDateRegistered(): ?DateTime
    {
        return $this->dateRegistered;
    }

    public function setDateRegistered(?DateTime $dateRegistered): void
    {
        $this->dateRegistered = $dateRegistered;
    }

    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function getLastActive(): ?DateTime
    {
        return $this->lastActive;
    }

    public function setLastActive(?DateTime $lastActive): void
    {
        $this->lastActive = $lastActive;
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getOfficeSymbol(): ?string
    {
        return $this->officeSymbol;
    }

    public function setOfficeSymbol(?string $officeSymbol): void
    {
        $this->officeSymbol = $officeSymbol;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function setBase(string $base): void
    {
        $this->base = $base;
    }

    public function isDisabled(): bool
    {
        return $this->disabled ?? false;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isReminderSent(): bool
    {
        return $this->reminderSent ?? false;
    }

    public function setReminderSent(bool $reminderSent): void
    {
        $this->reminderSent = $reminderSent;
    }
}
