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
     * @return array
     */
    public function fetch(Afsc $afsc): array
    {
        $afscUuid = $afsc->getUuid();

        if (empty($afscUuid)) {
            return [];
        }

        $questionCollection = new QuestionCollection(
            $this->db,
            $this->log
        );

        /** @var Question[] $questions */
        $questions = $questionCollection->fetchAfsc($afsc);

        $questionUuidList = QuestionHelpers::listUuid($questions);

        $answerCollection = new AnswerCollection(
            $this->db,
            $this->log
        );

        $answerCollection->preloadQuestionAnswers($afsc, $questionUuidList);

        $questions = array_values($questions);
        $c = count($questions);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($questions[$i])) {
                continue;
            }

            if (!$questions[$i] instanceof Question) {
                continue;
            }

            $questionAnswer = new QuestionAnswers();
            $questionAnswer->setQuestion($questions[$i]);
            $questionAnswer->setAnswers(
                $answerCollection->getQuestionAnswers(
                    $questions[$i]->getUuid()
                )
            );

            if (!isset($this->questionAnswers[$afsc->getUuid()])) {
                $this->questionAnswers[$afscUuid] = [];
            }

            $this->questionAnswers[$afscUuid][] = $questionAnswer;
        }

        return $this->questionAnswers[$afscUuid] ?? [];
    }
}