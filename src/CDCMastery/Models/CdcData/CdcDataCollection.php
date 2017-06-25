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

    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

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

    public function refresh(): self
    {
        $this->cdcData = [];

        return $this;
    }
}