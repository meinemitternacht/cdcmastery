<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 7:00 PM
 */

namespace CDCMastery\Models\Tests\Offline;


use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\Question;

class OfflineTest
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $userUuid;

    /**
     * @var Afsc
     */
    private $afsc;

    /**
     * @var Question[]
     */
    private $questions;

    /**
     * @var \DateTime
     */
    private $dateCreated;

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
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    /**
     * @param string $userUuid
     */
    public function setUserUuid(string $userUuid)
    {
        $this->userUuid = $userUuid;
    }

    /**
     * @return Afsc
     */
    public function getAfsc(): Afsc
    {
        return $this->afsc;
    }

    /**
     * @param Afsc $afsc
     */
    public function setAfsc(Afsc $afsc)
    {
        $this->afsc = $afsc;
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @param Question[] $questions
     */
    public function setQuestions(array $questions)
    {
        $this->questions = $questions;
    }

    /**
     * @return int
     */
    public function getNumQuestions(): int
    {
        return count($this->questions ?? []);
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     */
    public function setDateCreated(\DateTime $dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }
}