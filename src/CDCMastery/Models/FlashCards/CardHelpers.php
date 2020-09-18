<?php


namespace CDCMastery\Models\FlashCards;


use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\CdcDataCollection;

class CardHelpers
{
    /**
     * @param CdcDataCollection $cdc_data
     * @param Afsc $afsc
     * @return Card[]
     */
    public static function create_afsc_cards(CdcDataCollection $cdc_data, Afsc $afsc): array
    {
        $qas = $cdc_data->fetch($afsc->getUuid())
                        ->getQuestionAnswerData();

        if (!$qas) {
            return [];
        }

        $cards = [];
        foreach ($qas as $qa) {
            $card = new Card();
            $card->setUuid(UUID::NIL);
            $card->setFront($qa->getQuestion()->getText());
            $card->setBack($qa->getCorrect()->getText());
            $cards[] = $card;
        }

        return $cards;
    }
}