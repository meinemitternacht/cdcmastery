<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:47 PM
 */

namespace CDCMastery\Models\CdcData;


class QuestionAnswer
{
    private Question $question;
    private ?Answer $answer = null;
    private ?Answer $correct = null; /* only used when viewing completed tests */

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    public function getAnswer(): ?Answer
    {
        return $this->answer;
    }

    public function setAnswer(?Answer $answer): void
    {
        $this->answer = $answer;
    }

    /**
     * @return Answer|null
     */
    public function getCorrect(): ?Answer
    {
        return $this->correct;
    }

    /**
     * @param Answer|null $correct
     */
    public function setCorrect(?Answer $correct): void
    {
        $this->correct = $correct;
    }
}