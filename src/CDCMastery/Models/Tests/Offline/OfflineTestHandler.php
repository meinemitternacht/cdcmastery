<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 10:26 PM
 */

namespace CDCMastery\Models\Tests\Offline;


use CDCMastery\Helpers\ArrayHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\CdcData;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionAnswers;
use CDCMastery\Models\CdcData\QuestionAnswersCollection;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestOptions;
use DateTime;
use Exception;
use RuntimeException;

class OfflineTestHandler
{
    /**
     * @param QuestionAnswersCollection $qas
     * @param TestOptions $options
     * @return OfflineTest|null
     * @throws Exception
     */
    public static function factory(QuestionAnswersCollection $qas, TestOptions $options): ?OfflineTest
    {
        $numAfscs = count($options->getAfscs());
        if ($numAfscs !== 1) {
            return null;
        }

        /* Load AFSCs and generate an array of questions */
        $afscs = $options->getAfscs();
        $afsc = array_shift($afscs);

        $questionData = $qas->fetch($afsc);

        if ($questionData) {
            $questionData = array_filter($questionData, static function (QuestionAnswers $v): bool {
                return !$v->getQuestion()->isDisabled();
            });
        }

        if (!$questionData) {
            throw new RuntimeException('No questions available to populate test');
        }

        /* Randomize questions and extract a slice of them */
        ArrayHelpers::shuffle($questionData);

        if ($options->getNumQuestions() <= 0) {
            return null;
        }

        if ($options->getNumQuestions() > Test::MAX_QUESTIONS) {
            $options->setNumQuestions(Test::MAX_QUESTIONS);
        }

        $n_questions = count($questionData);

        if ($options->getNumQuestions() > $n_questions) {
            $options->setNumQuestions($n_questions);
        }

        /** @var Question[] $questionList */
        $questionsAnswers = array_slice($questionData,
                                        0,
                                        $options->getNumQuestions());

        $cdcData = new CdcData();
        $cdcData->setAfsc($afsc);
        $cdcData->setQuestionAnswerData($questionsAnswers);

        $test = new OfflineTest();
        $test->setUuid(UUID::generate());
        $test->setCdcData($cdcData);
        $test->setUserUuid($options->getUser()->getUuid());
        $test->setDateCreated(new DateTime());

        return $test;
    }
}
