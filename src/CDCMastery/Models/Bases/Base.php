<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:07 PM
 */

namespace CDCMastery\Models\Bases;


class Base
{
    private string $uuid;
    private string $name;
    private int $users = 0;
    private int $tests_complete = 0;
    private int $tests_incomplete = 0;

    public function getUuid(): string
    {
        return $this->uuid ?? '';
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return trim($this->name);
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUsers(): int
    {
        return $this->users;
    }

    public function setUsers(int $users): void
    {
        $this->users = $users;
    }

    /**
     * @return int
     */
    public function getTestsComplete(): int
    {
        return $this->tests_complete;
    }

    /**
     * @param int $tests_complete
     */
    public function setTestsComplete(int $tests_complete): void
    {
        $this->tests_complete = $tests_complete;
    }

    /**
     * @return int
     */
    public function getTestsIncomplete(): int
    {
        return $this->tests_incomplete;
    }

    /**
     * @param int $tests_incomplete
     */
    public function setTestsIncomplete(int $tests_incomplete): void
    {
        $this->tests_incomplete = $tests_incomplete;
    }
}