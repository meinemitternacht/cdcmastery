<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:03 PM
 */

namespace CDCMastery\Models\CdcData;


class Answer
{
    private string $uuid;
    private string $text;
    private bool $correct;
    private string $questionUuid;

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function isCorrect(): bool
    {
        return $this->correct;
    }

    public function setCorrect(bool $correct): void
    {
        $this->correct = $correct;
    }

    public function getQuestionUuid(): string
    {
        return $this->questionUuid;
    }

    public function setQuestionUuid(string $questionUuid): void
    {
        $this->questionUuid = $questionUuid;
    }
}
