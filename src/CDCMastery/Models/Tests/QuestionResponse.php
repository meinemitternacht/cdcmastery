<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:59 PM
 */

namespace CDCMastery\Models\Tests;


class QuestionResponse
{
    private string $testUuid;
    private string $questionUuid;
    private string $answerUuid;

    public function getTestUuid(): string
    {
        return $this->testUuid;
    }

    public function setTestUuid(string $testUuid): void
    {
        $this->testUuid = $testUuid;
    }

    public function getQuestionUuid(): string
    {
        return $this->questionUuid;
    }

    public function setQuestionUuid(string $questionUuid): void
    {
        $this->questionUuid = $questionUuid;
    }

    public function getAnswerUuid(): string
    {
        return $this->answerUuid;
    }

    public function setAnswerUuid(string $answerUuid): void
    {
        $this->answerUuid = $answerUuid;
    }
}