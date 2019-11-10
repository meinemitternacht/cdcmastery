<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:47 PM
 */

namespace CDCMastery\Models\CdcData;


class QuestionAnswer
{
    /**
     * @var Question
     */
    private $question;

    /**
     * @var Answer
     */
    private $answer;

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
     * @return Answer
     */
    public function getAnswer(): Answer
    {
        return $this->answer;
    }

    /**
     * @param Answer $answer
     */
    public function setAnswer(Answer $answer): void
    {
        $this->answer = $answer;
    }
}