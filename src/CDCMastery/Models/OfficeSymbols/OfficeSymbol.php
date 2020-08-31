<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:09 PM
 */

namespace CDCMastery\Models\OfficeSymbols;


class OfficeSymbol
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $symbol;

    /**
     * @var int
     */
    private $users = 0;

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
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * @param string $symbol
     */
    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @return int
     */
    public function getUsers(): int
    {
        return $this->users;
    }

    /**
     * @param int $users
     */
    public function setUsers(int $users): void
    {
        $this->users = $users;
    }
}