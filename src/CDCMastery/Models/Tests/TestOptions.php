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
    /** @var Afsc[] */
    private array $afscs = [];
    private int $numQuestions;
    private User $user;

    /**
     * @param Afsc $afsc
     */
    public function addAfsc(Afsc $afsc): void
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
        if (!is_array($this->afscs) || !$this->afscs) {
            return false;
        }

        foreach ($this->afscs as $tgt_afsc) {
            if (!$tgt_afsc instanceof Afsc) {
                continue;
            }

            if ($tgt_afsc->getUuid() === $afsc->getUuid()) {
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
    public function setAfscs(array $afscs): void
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
    public function setNumQuestions(int $numQuestions): void
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
    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}