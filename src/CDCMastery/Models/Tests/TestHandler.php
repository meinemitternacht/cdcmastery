<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 10:31 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\AnswerHelpers;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionHelpers;
use DateTime;
use Exception;
use Monolog\Logger;
use mysqli;
use function count;

class TestHandler
{
    const ACTION_NO_ACTION = -1;
    const ACTION_SUBMIT_ANSWER = 0;
    const ACTION_NAV_FIRST = 1;
    const ACTION_NAV_PREV = 2;
    const ACTION_NAV_NEXT = 3;
    const ACTION_NAV_LAST = 4;
    const ACTION_NAV_NUM = 5;
    const ACTION_SCORE_TEST = 6;

    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var TestDataHelpers
     */
    private $test_data_helpers;

    /**
     * @var Test
     */
    private $test;

    /**
     * TestHandler constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param TestDataHelpers $test_data_helpers
     */
    public function __construct(mysqli $mysqli, Logger $logger, TestDataHelpers $test_data_helpers)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->test_data_helpers = $test_data_helpers;
    }

    /**
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param TestDataHelpers $test_data_helpers
     * @param TestOptions $options
     * @return TestHandler
     * @throws Exception
     */
    public static function factory(
        mysqli $mysqli,
        Logger $logger,
        TestDataHelpers $test_data_helpers,
        TestOptions $options
    ): self {
        if (count($options->getAfscs()) === 0) {
            return new self($mysqli, $logger, $test_data_helpers);
        }

        /* Load AFSCs and generate an array of questions */
        $cdcDataCollection = new CdcDataCollection(
            $mysqli,
            $logger
        );

        $questions = [];
        foreach ($options->getAfscs() as $afsc) {
            $questionData = $cdcDataCollection->fetch($afsc->getUuid())->getQuestionAnswerData();

            if (count($questionData) === 0) {
                continue;
            }

            foreach ($questionData as $questionAnswer) {
                $questions[] = $questionAnswer->getQuestion();
            }
        }

        if (count($questions) === 0) {
            return new self($mysqli, $logger, $test_data_helpers);
        }

        /* Randomize questions and extract a slice of them */
        shuffle($questions);

        if ($options->getNumQuestions() <= 0) {
            return new self($mysqli, $logger, $test_data_helpers);
        }

        if ($options->getNumQuestions() > Test::MAX_QUESTIONS) {
            $options->setNumQuestions(Test::MAX_QUESTIONS);
        }

        $n_questions = count($questions);

        if ($options->getNumQuestions() > $n_questions) {
            $options->setNumQuestions($n_questions);
        }

        $questionList = array_slice($questions,
                                    0,
                                    $options->getNumQuestions());

        $test = new Test();
        $test->setUuid(UUID::generate());
        $test->setAfscs($options->getAfscs());
        $test->setQuestions($questionList);
        $test->setCombined(count($options->getAfscs()) > 1);
        $test->setTimeStarted(new DateTime());
        $test->setUserUuid($options->getUser()->getUuid());

        $handler = new self($mysqli, $logger, $test_data_helpers);
        $handler->setTest($test);

        /* Navigate to the first question, which automatically saves the new test */
        $handler->first();

        return $handler;
    }

    /**
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param TestDataHelpers $test_data_helpers
     * @param Test $test
     * @return TestHandler
     */
    public static function resume(mysqli $mysqli, Logger $logger, TestDataHelpers $test_data_helpers, Test $test): self
    {
        $handler = new self($mysqli, $logger, $test_data_helpers);
        $handler->setTest($test);

        return $handler;
    }

    public function first(): void
    {
        if ($this->test === null) {
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
        if ($this->test === null) {
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
        if ($this->test === null) {
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
        if ($this->test === null) {
            return;
        }

        $this->test->setCurrentQuestion(
            $this->test->getNumQuestions() - 1
        );

        $this->save();
    }

    /**
     * @param int $idx
     */
    public function navigate(int $idx): void
    {
        if ($this->test === null) {
            return;
        }

        if (!isset($this->test->getQuestions()[$idx])) {
            return;
        }

        $this->test->setCurrentQuestion($idx);
        $this->save();
    }

    /**
     * @return array
     */
    public function getDisplayData(): array
    {
        if ($this->test === null) {
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
            [$this->getQuestion()->getUuid()]
        );

        $answerList = $answerCollection->getQuestionAnswers(
            $this->getQuestion()->getUuid()
        );

        shuffle($answerList);

        $answerData = [];
        foreach ($answerList as $answer) {
            $answerData[] = [
                'uuid' => $answer->getUuid(),
                'text' => $answer->getText(),
            ];
        }

        $storedAnswer = $this->test_data_helpers->fetch(
            $this->getTest(),
            $this->getQuestion()
        );

        $numAnswered = $this->test_data_helpers->count($this->test);

        return [
            'uuid' => $this->getTest()->getUuid(),
            'afscs' => [
                'total' => $this->getTest()->getNumAfscs(),
                'list' => AfscHelpers::listUuid($this->getTest()->getAfscs()),
            ],
            'questions' => [
                'idx' => $this->getTest()->getCurrentQuestion(),
                'total' => $this->getTest()->getNumQuestions(),
                'numAnswered' => $numAnswered,
                'unanswered' => $this->test_data_helpers->getUnanswered(
                    $this->getTest()
                ),
            ],
            'display' => [
                'question' => [
                    'uuid' => $this->getQuestion()->getUuid(),
                    'text' => $this->getQuestion()->getText(),
                ],
                'answers' => $answerData,
                'selection' => $storedAnswer->getUuid(),
            ],
        ];
    }

    public function getNumAnswered(): int
    {
        return $this->test_data_helpers->count($this->test);
    }

    /**
     * @param int $idx
     * @return Question
     */
    public function getQuestion(int $idx = -1): Question
    {
        if ($this->test === null) {
            return new Question();
        }

        if ($idx === -1) {
            $idx = $this->test->getCurrentQuestion();
        }

        return $this->test->getQuestions()[$idx] ?? new Question();
    }

    private function save(): void
    {
        if ($this->test === null) {
            return;
        }

        $testCollection = new TestCollection($this->db,
                                             $this->log);

        $testCollection->save($this->test);
    }

    /**
     * @param QuestionResponse $questionResponse
     */
    public function saveResponse(QuestionResponse $questionResponse): void
    {
        if ($this->test === null) {
            return;
        }

        if (($questionResponse->getQuestionUuid() ?? '') === '' || ($questionResponse->getAnswerUuid() ?? '') === '') {
            return;
        }

        if (($questionResponse->getTestUuid() ?? '') === '') {
            $questionResponse->setTestUuid($this->test->getUuid());
        }

        $this->test_data_helpers->save($questionResponse);
        $this->test->setNumAnswered($this->test->getNumAnswered() + 1);
        $this->next();
    }

    /**
     * @throws Exception
     */
    public function score(): void
    {
        $answerHelpers = new AnswerHelpers($this->db,
                                           $this->log);

        $testCollection = new TestCollection($this->db,
                                             $this->log);

        $selectedAnswers = $this->test_data_helpers->list($this->getTest());

        $answerUuids = [];
        foreach ($selectedAnswers as $answer) {
            $answerUuids[] = $answer->getAnswer()->getUuid();
        }

        $answersCorrect = $answerHelpers->fetchCorrectArray(
            $answerUuids
        );

        $nCorrect = 0;
        $nMissed = 0;
        foreach ($answersCorrect as $answerUuid => $answerCorrect) {
            if ($answerCorrect) {
                $nCorrect++;
                continue;
            }

            $nMissed++;
        }

        $test = $this->getTest();
        $test->setCurrentQuestion($test->getNumQuestions() - 1);
        $test->setTimeCompleted(new DateTime());
        $test->setScore($this->calculateScore($test->getNumQuestions(),
                                              $nCorrect));
        $test->setNumAnswered(count($selectedAnswers));
        $test->setNumMissed($nMissed);
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

        return round(($correct / $questions) * 100,
                     Test::SCORE_PRECISION);
    }

    /**
     * @return Test
     */
    public function getTest(): Test
    {
        if ($this->test === null) {
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