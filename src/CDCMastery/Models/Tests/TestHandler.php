<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 10:31 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\UuidHelpers;
use CDCMastery\Models\CdcData\CdcData;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use Monolog\Logger;

class TestHandler
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
     * @var Test
     */
    private $test;

    /**
     * TestHandler constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param \mysqli $mysqli
     * @param Logger $logger
     * @param TestOptions $options
     * @return TestHandler
     */
    public function factory(\mysqli $mysqli, Logger $logger, TestOptions $options): self
    {
        if (empty($options->getAfscs())) {
            return new self($mysqli, $logger);
        }

        /* Load AFSCs and generate an array of questions */
        $cdcDataCollection = new CdcDataCollection(
            $mysqli,
            $logger
        );

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

        $n_questions = count($questions);

        if ($options->getNumQuestions() > $n_questions) {
            $options->setNumQuestions($n_questions);
        }

        $questionList = array_slice(
            $questions,
            0,
            $options->getNumQuestions()
        );

        $test = new Test();
        $test->setUuid(UuidHelpers::generate());
        $test->setAfscs($options->getAfscs());
        $test->setQuestions($questionList);
        $test->setCombined(count($options->getAfscs()) > 1);
        $test->setTimeStarted(new \DateTime());
        $test->setUserUuid($options->getUser()->getUuid());

        $testHandler = new self($mysqli, $logger);
        $testHandler->setTest($test);

        return $testHandler;
    }

    public function first(): void
    {
        if (is_null($this->test)) {
            return;
        }

        if ($this->test->getCurrentQuestion() <= 0) {
            $this->test->setCurrentQuestion(0);
            return;
        }

        $this->test->setCurrentQuestion(0);
    }

    public function previous(): void
    {
        if (is_null($this->test)) {
            return;
        }

        if (($this->test->getCurrentQuestion() - 1) <= 0) {
            $this->test->setCurrentQuestion(0);
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getCurrentQuestion() - 1
        );
    }

    public function next(): void
    {
        if (is_null($this->test)) {
            return;
        }

        if (($this->test->getCurrentQuestion() + 1) >= $this->test->getNumQuestions()) {
            $this->test->setCurrentQuestion(
                $this->test->getNumQuestions() - 1
            );
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getCurrentQuestion() + 1
        );
    }

    public function last(): void
    {
        if (is_null($this->test)) {
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getNumQuestions() - 1
        );
    }

    /**
     * @return Question
     */
    public function getQuestion(): Question
    {
        if (is_null($this->test)) {
            return new Question();
        }

        return $this->test->getQuestions()[$this->test->getCurrentQuestion()] ?? new Question();
    }

    /**
     * @param QuestionResponse $questionResponse
     */
    public function saveResponse(QuestionResponse $questionResponse): void
    {
        if (is_null($this->test)) {
            return;
        }

        if (empty($questionResponse->getQuestionUuid()) || empty($questionResponse->getAnswerUuid())) {
            return;
        }

        if (empty($questionResponse->getTestUuid())) {
            $questionResponse->setTestUuid($this->test->getUuid());
        }

        $testDataHelpers = new TestDataHelpers(
            $this->db,
            $this->log
        );

        $testDataHelpers->save($questionResponse);
        $this->next();
    }

    /**
     * @return Test
     */
    public function getTest(): Test
    {
        if (is_null($this->test)) {
            $this->setTest(new Test());
        }

        return $this->test;
    }

    /**
     * @param Test $test
     */
    public function setTest(Test $test)
    {
        $this->test = $test;
    }
}