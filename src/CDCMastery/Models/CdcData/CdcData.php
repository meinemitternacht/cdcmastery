<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 7:06 PM
 */

namespace CDCMastery\Models\CdcData;


class CdcData
{
    /**
     * @var Afsc
     */
    private $afsc;

    /**
     * @var QuestionAnswers[]
     */
    private $questionAnswerData;

    /**
     * @return Afsc
     */
    public function getAfsc(): Afsc
    {
        return $this->afsc;
    }

    /**
     * @param Afsc $afsc
     */
    public function setAfsc(Afsc $afsc)
    {
        $this->afsc = $afsc;
    }

    /**
     * @return QuestionAnswers[]
     */
    public function getQuestionAnswerData(): array
    {
        return $this->questionAnswerData;
    }

    /**
     * @param QuestionAnswers[] $questionAnswerData
     */
    public function setQuestionAnswerData(array $questionAnswerData)
    {
        $this->questionAnswerData = $questionAnswerData;
    }
}