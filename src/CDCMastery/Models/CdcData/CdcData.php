<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 7:06 PM
 */

namespace CDCMastery\Models\CdcData;


class CdcData
{
    private Afsc $afsc;
    /** @var QuestionAnswers[] */
    private array $questionAnswerData;

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
    public function setAfsc(Afsc $afsc): void
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
    public function setQuestionAnswerData(array $questionAnswerData): void
    {
        $this->questionAnswerData = $questionAnswerData;
    }
}
