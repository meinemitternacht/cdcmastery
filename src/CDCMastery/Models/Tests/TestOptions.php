<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 10:34 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\Users\User;

class TestOptions
{
    /**
     * @var Afsc[]
     */
    private $afscs;

    /**
     * @var int
     */
    private $numQuestions;

    /**
     * @var User
     */
    private $user;

    /**
     * @param Afsc $afsc
     */
    public function addAfsc(Afsc $afsc)
    {
        if (!is_array($this->afscs)) {
            $this->afscs = [];
        }

        if ($this->afscExists($afsc)) {
            return;
        }

        $this->afscs[] = $afsc;
    }

    /**
     * @param Afsc $afsc
     * @return bool
     */
    private function afscExists(Afsc $afsc): bool
    {
        if (!is_array($this->afscs) || empty($this->afscs)) {
            return false;
        }

        $c = count($this->afscs);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($this->afscs[$i])) {
                continue;
            }

            if (!$this->afscs[$i] instanceof Afsc) {
                continue;
            }

            if ($this->afscs[$i]->getUuid() === $afsc->getUuid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Afsc[]
     */
    public function getAfscs(): array
    {
        return $this->afscs;
    }

    /**
     * @param Afsc[] $afscs
     */
    public function setAfscs(array $afscs)
    {
        $this->afscs = $afscs;
    }

    /**
     * @return int
     */
    public function getNumQuestions(): int
    {
        return $this->numQuestions;
    }

    /**
     * @param int $numQuestions
     */
    public function setNumQuestions(int $numQuestions)
    {
        $this->numQuestions = $numQuestions;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}