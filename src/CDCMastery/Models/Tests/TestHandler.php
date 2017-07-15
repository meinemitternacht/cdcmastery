<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 10:31 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\UuidHelpers;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\AnswerHelpers;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionHelpers;
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
    public static function factory(\mysqli $mysqli, Logger $logger, TestOptions $options): self
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
            $questionData = $cdcDataCollection->fetch($afsc->getUuid())->getQuestionAnswerData();

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

        $testHandler->save();

        return $testHandler;
    }

    public function first(): void
    {
        if (is_null($this->test)) {
            return;
        }

        if ($this->test->getCurrentQuestion() <= 0) {
            $this->test->setCurrentQuestion(0);

            $this->save();
            return;
        }

        $this->test->setCurrentQuestion(0);

        $this->save();
    }

    public function previous(): void
    {
        if (is_null($this->test)) {
            return;
        }

        if (($this->test->getCurrentQuestion() - 1) <= 0) {
            $this->test->setCurrentQuestion(0);

            $this->save();
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getCurrentQuestion() - 1
        );

        $this->save();
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

            $this->save();
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getCurrentQuestion() + 1
        );

        $this->save();
    }

    public function last(): void
    {
        if (is_null($this->test)) {
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getNumQuestions() - 1
        );

        $this->save();
    }

    /**
     * @return array
     */
    public function getDisplayData(): array
    {
        if (is_null($this->test)) {
            return [];
        }

        $questionHelpers = new QuestionHelpers(
            $this->db,
            $this->log
        );

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        $answerCollection->preloadQuestionAnswers(
            $questionHelpers->getQuestionAfsc(
                $this->getQuestion()
            ),
            [$this->getQuestion()]
        );

        $answerList = $answerCollection->getQuestionAnswers(
            $this->getQuestion()->getUuid()
        );

        $answerData = [];
        foreach ($answerList as $answer) {
            $answerData[] = [
                'uuid' => $answer->getUuid(),
                'text' => $answer->getText()
            ];
        }

        $testDataHelpers = new TestDataHelpers(
            $this->db,
            $this->log
        );

        $storedAnswer = $testDataHelpers->fetch(
            $this->getTest(),
            $this->getQuestion()
        );

        return [
            'uuid' => $this->getTest()->getUuid(),
            'afscs' => [
                'total' => $this->getTest()->getNumAfscs(),
                'list' => AfscHelpers::listUuid($this->getTest()->getAfscs())
            ],
            'questions' => [
                'idx' => $this->getTest()->getCurrentQuestion(),
                'total' => $this->getTest()->getNumQuestions(),
                'unanswered' => $testDataHelpers->getUnanswered(
                    $this->getTest()
                )
            ],
            'display' => [
                'question' => [
                    'uuid' => $this->getQuestion()->getUuid(),
                    'text' => $this->getQuestion()->getText()
                ],
                'answers' => $answerData,
                'selection' => $storedAnswer->getUuid()
            ]
        ];
    }

    /**
     * @param int $idx
     * @return Question
     */
    public function getQuestion(int $idx = -1): Question
    {
        if (is_null($this->test)) {
            return new Question();
        }

        if ($idx === -1) {
            $idx = $this->test->getCurrentQuestion();
        }

        return $this->test->getQuestions()[$idx] ?? new Question();
    }

    private function save(): void
    {
        if (is_null($this->test)) {
            return;
        }

        $testCollection = new TestCollection(
            $this->db,
            $this->log
        );

        $testCollection->save($this->test);
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

    public function score(): void
    {
        $answerHelpers = new AnswerHelpers(
            $this->db,
            $this->log
        );

        $testDataHelpers = new TestDataHelpers(
            $this->db,
            $this->log
        );

        $testCollection = new TestCollection(
            $this->db,
            $this->log
        );

        $answersCorrect = $answerHelpers->fetchCorrectArray(
            $testDataHelpers->list($this->getTest())
        );

        $nCorrect = 0;
        $c = count($answersCorrect);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($answersCorrect[$i])) {
                continue;
            }

            if ($answersCorrect[$i] === true) {
                $nCorrect++;
            }
        }

        $test = $this->getTest();
        $test->setCurrentQuestion(
            $test->getNumQuestions() - 1
        );
        $test->setTimeCompleted(
            new \DateTime()
        );
        $test->setScore(
            $this->calculateScore(
                $test->getNumQuestions(),
                $nCorrect
            )
        );

        $testCollection->save($test);
    }

    /**
     * @param int $questions
     * @param int $correct
     * @return float
     */
    private function calculateScore(int $questions, int $correct): float
    {
        if ($questions === 0) {
            return 0.00;
        }

        return round(
            ($correct / $questions) * 100,
            Test::SCORE_PRECISION
        );
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