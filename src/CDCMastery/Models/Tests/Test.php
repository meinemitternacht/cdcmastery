<?php
declare(strict_types=1);


namespace CDCMastery\Models\Tests;


use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\Question;
use DateTime;

class Test
{
    public const STATE_COMPLETE = 0;
    public const STATE_INCOMPLETE = 1;

    public const TYPE_NORMAL = 0;
    public const TYPE_PRACTICE = 1;

    public const MAX_QUESTIONS = 500;
    public const SCORE_PRECISION = 2;

    private string $uuid;
    private string $userUuid;
    private ?DateTime $timeStarted;
    private ?DateTime $timeCompleted;
    private ?DateTime $lastUpdated;
    /** @var Afsc[] */
    private array $afscs;
    /** @var Question[] */
    private array $questions;
    private int $currentQuestion;
    private int $numAnswered;
    private int $numMissed;
    private float $score;
    private bool $archived;
    private int $type;

    public function getUuid(): string
    {
        return $this->uuid ?? '';
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid ?? '';
    }

    public function setUserUuid(string $userUuid): void
    {
        $this->userUuid = $userUuid;
    }

    public function getTimeStarted(): ?DateTime
    {
        return $this->timeStarted;
    }

    public function setTimeStarted(?DateTime $timeStarted): void
    {
        $this->timeStarted = $timeStarted;
    }

    public function getTimeCompleted(): ?DateTime
    {
        return $this->timeCompleted;
    }

    public function setTimeCompleted(?DateTime $timeCompleted): void
    {
        $this->timeCompleted = $timeCompleted;
    }

    /**
     * @return DateTime|null
     */
    public function getLastUpdated(): ?DateTime
    {
        return $this->lastUpdated;
    }

    /**
     * @param DateTime|null $lastUpdated
     */
    public function setLastUpdated(?DateTime $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
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
    public function setAfscs(array $afscs): void
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
    public function setQuestions(array $questions): void
    {
        $this->questions = array_values($questions);
    }

    public function getCurrentQuestion(): int
    {
        return $this->currentQuestion ?? 0;
    }

    public function setCurrentQuestion(int $currentQuestion): void
    {
        $this->currentQuestion = $currentQuestion;
    }

    public function getNumAfscs(): int
    {
        return count($this->afscs ?? []);
    }

    public function getNumAnswered(): int
    {
        return $this->numAnswered ?? 0;
    }

    public function setNumAnswered(int $numAnswered): void
    {
        $this->numAnswered = $numAnswered;
    }

    public function getNumMissed(): int
    {
        return $this->numMissed ?? 0;
    }

    public function setNumMissed(int $numMissed): void
    {
        $this->numMissed = $numMissed;
    }

    public function getNumQuestions(): int
    {
        if (empty($this->questions)) {
            return $this->getNumAnswered();
        }

        return count($this->questions ?? []);
    }

    public function getScore(): float
    {
        return $this->score ?? 0.00;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function isArchived(): bool
    {
        return $this->archived ?? false;
    }

    public function setArchived(bool $archived): void
    {
        $this->archived = $archived;
    }

    public function isComplete(): bool
    {
        return $this->getTimeCompleted() !== null;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }
}