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
     * @var QuestionAnswer[]
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
     * @return QuestionAnswer[]
     */
    public function getQuestionAnswerData(): array
    {
        return $this->questionAnswerData;
    }

    /**
     * @param QuestionAnswer[] $questionAnswerData
     */
    public function setQuestionAnswerData(array $questionAnswerData)
    {
        $this->questionAnswerData = $questionAnswerData;
    }
}