<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:47 PM
 */

namespace CDCMastery\Models\CdcData;


class QuestionAnswers
{
    /**
     * @var Question
     */
    private $question;

    /**
     * @var Answer[]
     */
    private $answers;

    /**
     * @return Question
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    /**
     * @param Question $question
     */
    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    /**
     * @return Answer[]
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param Answer[] $answers
     */
    public function setAnswers(array $answers): void
    {
        $this->answers = $answers;
    }
}