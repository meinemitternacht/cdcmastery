<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 10:26 PM
 */

namespace CDCMastery\Models\Tests\Offline;


use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestOptions;
use DateTime;
use Exception;
use Monolog\Logger;
use mysqli;

class OfflineTestHandler
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
     * @var OfflineTest
     */
    private $test;

    /**
     * OfflineTestHandler constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param TestOptions $options
     * @return static
     * @throws Exception
     */
    public static function factory(mysqli $mysqli, Logger $logger, TestOptions $options): self
    {
        $numAfscs = count($options->getAfscs());
        if ($numAfscs !== 1) {
            return new self($mysqli, $logger);
        }

        /* Load AFSCs and generate an array of questions */
        $cdcDataCollection = new CdcDataCollection(
            $mysqli,
            $logger
        );

        /** @var Question[] $questions */
        $questions = [];
        foreach ($options->getAfscs() as $afsc) {
            $questionData = $cdcDataCollection->fetch($afsc)->getQuestionAnswerData();

            if (empty($questionData)) {
                continue;
            }

            foreach ($questionData as $questionAnswer) {
                $questions[] = $questionAnswer->getQuestion();
            }
        }

        if (empty($questions)) {
            return new self($mysqli, $logger);
        }

        /* Randomize questions and extract a slice of them */
        shuffle($questions);

        if ($options->getNumQuestions() <= 0) {
            return new self($mysqli, $logger);
        }

        if ($options->getNumQuestions() > Test::MAX_QUESTIONS) {
            $options->setNumQuestions(Test::MAX_QUESTIONS);
        }

        $n_questions = count($questions);

        if ($options->getNumQuestions() > $n_questions) {
            $options->setNumQuestions($n_questions);
        }

        /** @var Question[] $questionList */
        $questionList = array_slice($questions,
                                    0,
                                    $options->getNumQuestions());

        $offlineTest = new OfflineTest();
        $offlineTest->setUuid(UUID::generate());
        $offlineTest->setAfsc($options->getAfscs()[0]);
        $offlineTest->setQuestions($questionList);
        $offlineTest->setUserUuid($options->getUser()->getUuid());
        $offlineTest->setDateCreated(new DateTime());

        $offlineTestHandler = new self($mysqli, $logger);
        $offlineTestHandler->setTest($offlineTest);

        return $offlineTestHandler;
    }

    /**
     * @return OfflineTest
     */
    public function getTest(): OfflineTest
    {
        return $this->test;
    }

    /**
     * @param OfflineTest $test
     */
    public function setTest(OfflineTest $test)
    {
        $this->test = $test;
    }
}