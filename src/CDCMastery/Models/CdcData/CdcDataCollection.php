<?php
declare(strict_types=1);

namespace CDCMastery\Models\CdcData;


use RuntimeException;

class CdcDataCollection
{
    private QuestionAnswersCollection $qas;

    public function __construct(QuestionAnswersCollection $qas)
    {
        $this->qas = $qas;
    }

    public function fetch(Afsc $afsc): CdcData
    {
        if (!$afsc || !$afsc->getUuid()) {
            throw new RuntimeException('invalid AFSC specified');
        }

        $cdc_qas = $this->qas->fetch($afsc);

        $cdcData = new CdcData();
        $cdcData->setAfsc($afsc);
        $cdcData->setQuestionAnswerData($cdc_qas);
        return $cdcData;
    }
}
