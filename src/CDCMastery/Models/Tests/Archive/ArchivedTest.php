<?php
declare(strict_types=1);


namespace CDCMastery\Models\Tests\Archive;


use DateTime;

class ArchivedTest
{
    /* V1 */
    private ?DateTime $time_started;
    private ?DateTime $time_completed;
    /** @var string[] */
    private array $afscs;
    private int $num_questions;
    private int $num_missed;
    private float $score;

    /* V2 */
    /** @var ArchivedTestQAPair[] */
    private array $questions;

    /* V3 */
    private int $type;

    /**
     * ArchivedTest constructor.
     * @param DateTime|null $time_started
     * @param DateTime|null $time_completed
     * @param string[] $afscs
     * @param int $num_questions
     * @param int $num_missed
     * @param float $score
     * @param ArchivedTestQAPair[] $questions
     * @param int $type
     */
    public function __construct(
        ?DateTime $time_started,
        ?DateTime $time_completed,
        array $afscs,
        int $num_questions,
        int $num_missed,
        float $score,
        array $questions,
        int $type
    ) {
        $this->time_started = $time_started;
        $this->time_completed = $time_completed;
        $this->afscs = $afscs;
        $this->num_questions = $num_questions;
        $this->num_missed = $num_missed;
        $this->score = $score;
        $this->questions = $questions;
        $this->type = $type;
    }

    /**
     * @return DateTime|null
     */
    public function getTimeStarted(): ?DateTime
    {
        return $this->time_started;
    }

    /**
     * @return DateTime|null
     */
    public function getTimeCompleted(): ?DateTime
    {
        return $this->time_completed;
    }

    /**
     * @return string[]
     */
    public function getAfscs(): array
    {
        return $this->afscs;
    }

    /**
     * @return int
     */
    public function getNumQuestions(): int
    {
        return $this->num_questions;
    }

    /**
     * @return int
     */
    public function getNumMissed(): int
    {
        return $this->num_missed;
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @return ArchivedTestQAPair[]
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}
