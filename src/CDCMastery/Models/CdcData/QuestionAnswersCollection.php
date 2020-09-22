<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 6:49 PM
 */

namespace CDCMastery\Models\CdcData;


use RuntimeException;

class QuestionAnswersCollection
{
    private QuestionCollection $questions;
    private AnswerCollection $answers;

    public function __construct(
        QuestionCollection $questions,
        AnswerCollection $answers
    ) {
        $this->questions = $questions;
        $this->answers = $answers;
    }

    /**
     * @param Afsc $afsc
     * @param Question[]|null $questions
     * @return array
     */
    public function fetch(Afsc $afsc, ?array $questions = null): array
    {
        $afsc_uuid = $afsc->getUuid();

        if (!$afsc_uuid) {
            return [];
        }

        if (!$questions) {
            $questions = $this->questions->fetchAfsc($afsc);
        }

        $answers = $this->answers->fetchByQuestions($afsc, $questions);

        $answers_by_quuid = [];
        $answers_by_quuid_correct = [];
        foreach ($answers as $answer) {
            $quuid = $answer->getQuestionUuid();
            if (!isset($answers_by_quuid[ $quuid ])) {
                $answers_by_quuid[ $quuid ] = [];
            }

            $answers_by_quuid[ $quuid ][] = $answer;

            if ($answer->isCorrect()) {
                $answers_by_quuid_correct[ $quuid ] = $answer;
            }
        }

        $qas = [];
        foreach ($questions as $quuid => $question) {
            if (!$question instanceof Question) {
                continue;
            }

            if (!isset($answers_by_quuid[ $quuid ])) {
                throw new RuntimeException("question has no answers :: quuid {$quuid} :: afsc {$afsc_uuid}");
            }

            $correct = $answers_by_quuid_correct[ $quuid ] ?? null;

            if (!$correct) {
                throw new RuntimeException("question has no correct answer :: quuid {$quuid} :: afsc {$afsc_uuid}");
            }

            $qa = new QuestionAnswers();
            $qa->setQuestion($question);
            $qa->setAnswers($answers_by_quuid[ $quuid ]);
            $qa->setCorrect($correct);
            $qas[] = $qa;
        }

        return $qas;
    }
}