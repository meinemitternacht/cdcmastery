<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:49 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;
use mysqli;

class QuestionAnswersCollection
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
     * @var QuestionAnswers[]
     */
    private $questionAnswers = [];

    /**
     * QuestionAnswersCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param Afsc $afsc
     * @param Question[]|null $questions
     * @return array
     */
    public function fetch(Afsc $afsc, ?array $questions = null): array
    {
        $afscUuid = $afsc->getUuid();

        if (!$afscUuid) {
            return [];
        }

        if (!$questions) {
            $questionCollection = new QuestionCollection($this->db,
                                                         $this->log);

            $questions = $questionCollection->fetchAfsc($afsc);
        }

        $questionUuidList = QuestionHelpers::listUuid($questions);

        $answerCollection = new AnswerCollection($this->db,
                                                 $this->log);

        $answerCollection->preloadQuestionAnswers($afsc, $questionUuidList);

        foreach ($questions as $question) {
            if (!$question instanceof Question) {
                continue;
            }

            $correct = $answerCollection->getCorrectAnswer($question->getUuid());

            if (!$correct) {
                continue;
            }

            $questionAnswer = new QuestionAnswers();
            $questionAnswer->setQuestion($question);
            $questionAnswer->setAnswers(
                $answerCollection->getQuestionAnswers($question->getUuid())
            );
            $questionAnswer->setCorrect($correct);

            if (!isset($this->questionAnswers[ $afsc->getUuid() ])) {
                $this->questionAnswers[ $afscUuid ] = [];
            }

            $this->questionAnswers[ $afscUuid ][] = $questionAnswer;
        }

        return $this->questionAnswers[ $afscUuid ] ?? [];
    }
}