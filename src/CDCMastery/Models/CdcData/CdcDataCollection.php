<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 8:07 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class CdcDataCollection
{
    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var CdcData[]
     */
    private $cdcData = [];

    /**
     * CdcDataCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
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

        if (isset($this->cdcData[$afscUuid])) {
            return $this->cdcData[$afscUuid];
        }

        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $afsc = $afscCollection->fetch($afscUuid);

        if (empty($afsc->getUuid())) {
            return new CdcData();
        }

        $questionAnswerCollection = new QuestionAnswerCollection(
            $this->db,
            $this->log
        );

        $cdcQuestionAnswers = $questionAnswerCollection->fetch($afsc);

        $cdcData = new CdcData();
        $cdcData->setAfsc($afsc);
        $cdcData->setQuestionAnswerData($cdcQuestionAnswers);

        $this->cdcData[$afsc->getUuid()] = $cdcData;

        return $cdcData;
    }

    /**
     * @return CdcDataCollection
     */
    public function refresh(): self
    {
        $this->cdcData = [];

        return $this;
    }

    /**
     * @param CdcData $cdcData
     */
    public function save(CdcData $cdcData): void
    {
        $afscCollection = new AfscCollection(
            $this->db,
            $this->log
        );

        $afscCollection->save($cdcData->getAfsc());

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        $questions = [];
        $answers = [];
        foreach ($cdcData->getQuestionAnswerData() as $questionAnswer) {
            $questions[] = $questionAnswer->getQuestion();
            $answers = array_merge(
                $answers,
                $questionAnswer->getAnswers()
            );
        }

        $questionCollection->saveArray(
            $cdcData->getAfsc(),
            $questions
        );

        $answerCollection->saveArray(
            $cdcData->getAfsc(),
            $answers
        );
    }
}