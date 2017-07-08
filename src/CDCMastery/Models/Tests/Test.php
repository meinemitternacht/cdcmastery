<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:51 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\Question;

class Test
{
    const MIN_QUESTIONS = 1;
    const MAX_QUESTIONS = 500;

    const SCORE_PRECISION = 2;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $userUuid;

    /**
     * @var \DateTime|null
     */
    private $timeStarted;

    /**
     * @var \DateTime|null
     */
    private $timeCompleted;

    /**
     * @var Afsc[]
     */
    private $afscs;

    /**
     * @var Question[]
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
     * @var float
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
        return $this->uuid ?? '';
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
        return $this->userUuid ?? '';
    }

    /**
     * @param string $userUuid
     */
    public function setUserUuid(string $userUuid)
    {
        $this->userUuid = $userUuid;
    }

    /**
     * @return \DateTime|null
     */
    public function getTimeStarted(): ?\DateTime
    {
        return $this->timeStarted;
    }

    /**
     * @param \DateTime|null $timeStarted
     */
    public function setTimeStarted(?\DateTime $timeStarted)
    {
        $this->timeStarted = $timeStarted;
    }

    /**
     * @return \DateTime|null
     */
    public function getTimeCompleted(): ?\DateTime
    {
        return $this->timeCompleted;
    }

    /**
     * @param \DateTime|null $timeCompleted
     */
    public function setTimeCompleted(?\DateTime $timeCompleted)
    {
        $this->timeCompleted = $timeCompleted;
    }

    /**
     * @return Afsc[]
     */
    public function getAfscs(): array
    {
        return $this->afscs ?? [];
    }

    /**
     * @param Afsc[] $afscs
     */
    public function setAfscs(array $afscs)
    {
        $this->afscs = $afscs;
    }

    /**
     * @return Question[]
     */
    public function getQuestions(): array
    {
        return $this->questions ?? [];
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
    public function getCurrentQuestion(): int
    {
        return $this->currentQuestion ?? 0;
    }

    /**
     * @param int $currentQuestion
     */
    public function setCurrentQuestion(int $currentQuestion)
    {
        $this->currentQuestion = $currentQuestion;
    }

    public function getNumAfscs(): int
    {
        return count($this->afscs ?? []);
    }

    /**
     * @return int
     */
    public function getNumAnswered(): int
    {
        return $this->numAnswered ?? 0;
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
        return $this->numMissed ?? 0;
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
    public function getNumQuestions(): int
    {
        if (empty($this->questions)) {
            return $this->getNumAnswered();
        }

        return count($this->questions ?? []);
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score ?? 0;
    }

    /**
     * @param float $score
     */
    public function setScore(float $score)
    {
        $this->score = $score;
    }

    /**
     * @return bool
     */
    public function isCombined(): bool
    {
        return $this->combined ?? false;
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
        return $this->archived ?? false;
    }

    /**
     * @param bool $archived
     */
    public function setArchived(bool $archived)
    {
        $this->archived = $archived;
    }
}