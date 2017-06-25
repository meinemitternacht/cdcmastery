<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:51 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Models\CdcData\Afsc;

class Test
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
     * @var \DateTime
     */
    private $timeStarted;

    /**
     * @var \DateTime
     */
    private $timeCompleted;

    /**
     * @var Afsc[]
     */
    private $afscs;

    /**
     * @var string[]
     */
    private $questions;

    /**
     * @var int
     */
    private $currentQuestion;

    /**
     * @var int
     */
    private $numAnswered;

    /**
     * @var int
     */
    private $numMissed;

    /**
     * @var int
     */
    private $score;

    /**
     * @var bool
     */
    private $combined;

    /**
     * @var bool
     */
    private $archived;

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
     * @return \DateTime
     */
    public function getTimeStarted(): \DateTime
    {
        return $this->timeStarted;
    }

    /**
     * @param \DateTime $timeStarted
     */
    public function setTimeStarted(\DateTime $timeStarted)
    {
        $this->timeStarted = $timeStarted;
    }

    /**
     * @return \DateTime
     */
    public function getTimeCompleted(): \DateTime
    {
        return $this->timeCompleted;
    }

    /**
     * @param \DateTime $timeCompleted
     */
    public function setTimeCompleted(\DateTime $timeCompleted)
    {
        $this->timeCompleted = $timeCompleted;
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
     * @return \string[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @param \string[] $questions
     */
    public function setQuestions(array $questions)
    {
        $this->questions = $questions;
    }

    /**
     * @return int
     */
    public function getCurrentQuestion(): int
    {
        return $this->currentQuestion;
    }

    /**
     * @param int $currentQuestion
     */
    public function setCurrentQuestion(int $currentQuestion)
    {
        $this->currentQuestion = $currentQuestion;
    }

    /**
     * @return int
     */
    public function getNumAnswered(): int
    {
        return $this->numAnswered;
    }

    /**
     * @param int $numAnswered
     */
    public function setNumAnswered(int $numAnswered)
    {
        $this->numAnswered = $numAnswered;
    }

    /**
     * @return int
     */
    public function getNumMissed(): int
    {
        return $this->numMissed;
    }

    /**
     * @param int $numMissed
     */
    public function setNumMissed(int $numMissed)
    {
        $this->numMissed = $numMissed;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore(int $score)
    {
        $this->score = $score;
    }

    /**
     * @return bool
     */
    public function isCombined(): bool
    {
        return $this->combined;
    }

    /**
     * @param bool $combined
     */
    public function setCombined(bool $combined)
    {
        $this->combined = $combined;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     */
    public function setArchived(bool $archived)
    {
        $this->archived = $archived;
    }
}