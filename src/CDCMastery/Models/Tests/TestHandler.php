<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 10:31 PM
 */

namespace CDCMastery\Models\Tests;


use CDCMastery\Helpers\ArrayHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionAnswers;
use DateTime;
use Exception;
use Monolog\Logger;
use mysqli;
use RuntimeException;
use function count;

class TestHandler
{
    public const ACTION_NO_ACTION = -1;
    public const ACTION_SUBMIT_ANSWER = 0;
    public const ACTION_NAV_FIRST = 1;
    public const ACTION_NAV_PREV = 2;
    public const ACTION_NAV_NEXT = 3;
    public const ACTION_NAV_LAST = 4;
    public const ACTION_NAV_NUM = 5;
    public const ACTION_SCORE_TEST = 6;

    protected mysqli $db;
    protected Logger $log;
    private AfscCollection $afscs;
    private TestCollection $tests;
    private TestDataHelpers $test_data_helpers;
    private AnswerCollection $answers;
    private ?Test $test;

    public function __construct(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        TestCollection $tests,
        TestDataHelpers $test_data_helpers,
        AnswerCollection $answers
    ) {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->afscs = $afscs;
        $this->tests = $tests;
        $this->test_data_helpers = $test_data_helpers;
        $this->answers = $answers;
    }

    /**
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param AfscCollection $afscs
     * @param TestCollection $tests
     * @param CdcDataCollection $cdc_data
     * @param AnswerCollection $answers
     * @param TestDataHelpers $test_data_helpers
     * @param TestOptions $options
     * @return TestHandler
     * @throws Exception
     */
    public static function factory(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        TestCollection $tests,
        CdcDataCollection $cdc_data,
        AnswerCollection $answers,
        TestDataHelpers $test_data_helpers,
        TestOptions $options
    ): self {
        if ($options->getNumQuestions() <= 0) {
            throw new RuntimeException('The test parameters asked for 0 questions to be presented');
        }

        if ($options->getNumQuestions() > Test::MAX_QUESTIONS) {
            $options->setNumQuestions(Test::MAX_QUESTIONS);
        }

        if (!$options->getAfscs()) {
            throw new RuntimeException('One or more AFSC selections must be provided');
        }

        /* Load AFSCs and generate an array of questions */
        $tgt_questions = [];
        foreach ($options->getAfscs() as $afsc) {
            $qdata = $cdc_data->fetch($afsc)->getQuestionAnswerData();

            if (!$qdata) {
                continue;
            }

            $qdata = array_filter($qdata, static function (QuestionAnswers $v): bool {
                return !$v->getQuestion()->isDisabled();
            });

            foreach ($qdata as $questionAnswer) {
                $tgt_questions[] = $questionAnswer->getQuestion();
            }
        }

        if (!$tgt_questions) {
            throw new RuntimeException('There are no questions in the database for the AFSC(s) selected');
        }

        /* Randomize questions and extract a slice of them */
        ArrayHelpers::shuffle($tgt_questions);

        $n_questions = count($tgt_questions);

        if ($options->getNumQuestions() > $n_questions) {
            $options->setNumQuestions($n_questions);
        }

        $questionList = array_slice($tgt_questions,
                                    0,
                                    $options->getNumQuestions());

        $test = new Test();
        $test->setUuid(UUID::generate());
        $test->setAfscs($options->getAfscs());
        $test->setQuestions($questionList);
        $test->setCombined(count($options->getAfscs()) > 1);
        $test->setTimeStarted(new DateTime());
        $test->setTimeCompleted(null);
        $test->setUserUuid($options->getUser()->getUuid());

        $handler = new self($mysqli,
                            $logger,
                            $afscs,
                            $tests,
                            $test_data_helpers,
                            $answers);
        $handler->setTest($test);

        /* Navigate to the first question, which automatically saves the new test */
        $handler->first();

        return $handler;
    }

    /**
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param AfscCollection $afscs
     * @param TestCollection $tests
     * @param AnswerCollection $answers
     * @param TestDataHelpers $test_data_helpers
     * @param Test $test
     * @return TestHandler
     */
    public static function resume(
        mysqli $mysqli,
        Logger $logger,
        AfscCollection $afscs,
        TestCollection $tests,
        AnswerCollection $answers,
        TestDataHelpers $test_data_helpers,
        Test $test
    ): self {
        $handler = new self($mysqli,
                            $logger,
                            $afscs,
                            $tests,
                            $test_data_helpers,
                            $answers);
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

        if (!isset($this->test->getQuestions()[ $idx ])) {
            return;
        }

        $this->test->setCurrentQuestion($idx);
        $this->save();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDisplayData(): array
    {
        if ($this->test === null) {
            return [];
        }

        $question = $this->getQuestion();
        $afsc = $this->afscs->fetch($question->getAfscUuid());

        if (!$afsc) {
            throw new RuntimeException('The AFSC for that question no longer exists');
        }

        $answerList = $this->answers->fetchByQuestion($afsc, $question);

        ArrayHelpers::shuffle($answerList);

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

        $test = $this->getTest();
        $question = $this->getQuestion();

        return [
            'uuid' => $this->getTest()->getUuid(),
            'afscs' => [
                'total' => $this->getTest()->getNumAfscs(),
                'list' => AfscHelpers::listUuid($this->getTest()->getAfscs()),
            ],
            'questions' => [
                'idx' => $test->getCurrentQuestion(),
                'total' => $test->getNumQuestions(),
                'numAnswered' => $numAnswered,
                'unanswered' => $this->test_data_helpers->getUnanswered(
                    $this->getTest()
                ),
            ],
            'display' => [
                'question' => [
                    'uuid' => $question->getUuid(),
                    'text' => $question->getText(),
                ],
                'answers' => $answerData,
                'selection' => $storedAnswer
                    ? $storedAnswer->getUuid()
                    : null,
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

        return $this->test->getQuestions()[ $idx ] ?? new Question();
    }

    private function save(): void
    {
        if ($this->test === null) {
            return;
        }

        $this->tests->save($this->test);
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
        $test_qa_pairs = $this->test_data_helpers->list($this->getTest());

        $nCorrect = 0;
        $nMissed = 0;
        foreach ($test_qa_pairs as $test_qa_pair) {
            if ($test_qa_pair->getAnswer()->isCorrect()) {
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
        $test->setNumAnswered(count($test_qa_pairs));
        $test->setNumMissed($nMissed);
        $this->tests->save($test);
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
    public function setTest(Test $test): void
    {
        $this->test = $test;
    }
}