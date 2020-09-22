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
    /**
     * @var string
     */
    private $testUuid;

    /**
     * @var string
     */
    private $questionUuid;

    /**
     * @var string
     */
    private $answerUuid;

    /**
     * @return string
     */
    public function getTestUuid(): string
    {
        return $this->testUuid;
    }

    /**
     * @param string $testUuid
     */
    public function setTestUuid(string $testUuid): void
    {
        $this->testUuid = $testUuid;
    }

    /**
     * @return string
     */
    public function getQuestionUuid(): string
    {
        return $this->questionUuid;
    }

    /**
     * @param string $questionUuid
     */
    public function setQuestionUuid(string $questionUuid): void
    {
        $this->questionUuid = $questionUuid;
    }

    /**
     * @return string
     */
    public function getAnswerUuid(): string
    {
        return $this->answerUuid;
    }

    /**
     * @param string $answerUuid
     */
    public function setAnswerUuid(string $answerUuid): void
    {
        $this->answerUuid = $answerUuid;
    }
}