<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 8:07 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class CdcDataCollection
{
    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * CdcDataCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $afscUuid
     * @return CdcData
     */
    public function fetch(string $afscUuid): CdcData
    {
        if (empty($afscUuid)) {
            return new CdcData();
        }

        $afscs = new AfscCollection($this->db,
                                    $this->log);

        $afsc = $afscs->fetch($afscUuid);

        if (!$afsc || !$afsc->getUuid()) {
            return new CdcData();
        }

        $qas = new QuestionAnswersCollection($this->db,
                                             $this->log);

        $cdc_qas = $qas->fetch($afsc);

        $cdcData = new CdcData();
        $cdcData->setAfsc($afsc);
        $cdcData->setQuestionAnswerData($cdc_qas);
        return $cdcData;
    }

    /**
     * @param CdcData $cdcData
     */
    public function save(CdcData $cdcData): void
    {
        $afscs = new AfscCollection($this->db,
                                    $this->log);

        $afscs->save($cdcData->getAfsc());

        $questionCollection = new QuestionCollection($this->db,
                                                     $this->log);

        $answerCollection = new AnswerCollection($this->db,
                                                 $this->log);

        $questions = [];
        $answers = [];
        foreach ($cdcData->getQuestionAnswerData() as $questionAnswer) {
            $questions[] = $questionAnswer->getQuestion();
            $answers = array_merge(
                $answers,
                $questionAnswer->getAnswers()
            );
        }

        $questionCollection->saveArray($cdcData->getAfsc(),
                                       $questions);

        $answerCollection->saveArray($cdcData->getAfsc(),
                                     $answers);
    }
}