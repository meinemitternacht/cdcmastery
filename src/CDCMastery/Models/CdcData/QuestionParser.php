<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 10:55 PM
 */

namespace CDCMastery\Models\CdcData;


use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextAnswerEmptyException;
use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextEmptyException;
use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextInsufficientAnswersException;
use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextInvalidCorrectAnswerException;
use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextInvalidException;
use CDCMastery\Exceptions\CdcData\QuestionParser\QuestionTextParserFailedException;
use CDCMastery\Helpers\UuidHelpers;

class QuestionParser
{
    /**
     * @param Afsc $afsc
     * @param string $text
     * @param int $correctAnswer
     * @return QuestionAnswers
     * @throws QuestionTextAnswerEmptyException
     * @throws QuestionTextEmptyException
     * @throws QuestionTextInsufficientAnswersException
     * @throws QuestionTextInvalidCorrectAnswerException
     * @throws QuestionTextInvalidException
     * @throws QuestionTextParserFailedException
     */
    public static function parse(Afsc $afsc, string $text, int $correctAnswer): QuestionAnswers
    {
        if (empty($text)) {
            throw new QuestionTextInvalidException();
        }

        if ($correctAnswer < 0 || $correctAnswer > 4) {
            throw new QuestionTextInvalidCorrectAnswerException();
        }

        $lines = self::splitByLineAndAnswerIdentifiers($text);

        /* Handle instances where the line breaks are not copied correctly */
        if (\count($lines) === 1) {
            $lines = self::addLineBreaks($lines[0]);
        }

        $c = \count($lines);

        if ($c > 5) {
            throw new QuestionTextParserFailedException();
        }

        $questionText = null;
        $answerTexts = [];
        for ($i = 0; $i < $c; $i++) {
            if (!isset($lines[$i])) {
                continue;
            }

            switch ($i) {
                case 0:
                    $questionText = self::replaceQuestionNumberAndSection($lines[$i]);
                    break;
                case 1:
                case 2:
                case 3:
                case 4:
                    $answerTexts[] = self::replaceLastDot($lines[$i]);
                    break;
                default:
                    throw new QuestionTextParserFailedException();
                    break;
            }
        }

        if ($questionText === null || empty($questionText)) {
            throw new QuestionTextEmptyException();
        }

        if (count($answerTexts) < 4) {
            throw new QuestionTextInsufficientAnswersException();
        }

        $question = new Question();
        $question->setUuid(UuidHelpers::generate());
        $question->setText($questionText);
        $question->setAfscUuid($afsc->getUuid());

        $answers = [];
        $i = 0;
        foreach ($answerTexts as $answerText) {
            if (empty($answerText)) {
                throw new QuestionTextAnswerEmptyException();
            }

            $answer = new Answer();
            $answer->setUuid(UuidHelpers::generate());
            $answer->setText($answerText);
            $answer->setQuestionUuid($question->getUuid());
            $answer->setCorrect($i === $correctAnswer);

            $answers[] = $answer;
        }

        $questionAnswers = new QuestionAnswers();
        $questionAnswers->setQuestion($question);
        $questionAnswers->setAnswers($answers);

        return $questionAnswers;
    }

    /**
     * @param string $line
     * @return array
     * @throws QuestionTextParserFailedException
     */
    private static function addLineBreaks(string $line): array
    {
        return self::splitByLineAndAnswerIdentifiers(
            preg_replace(
                "/\s([AaBbCcDd]\.)\s/",
                "\r\n$1 ",
                $line
            )
        );
    }

    /**
     * @param string $text
     * @return string
     */
    private static function replaceLastDot(string $text): string
    {
        return preg_replace(
            "/\.$/",
            '',
            $text
        );
    }

    /**
     * @param string $text
     * @return string
     */
    private static function replaceQuestionNumberAndSection(string $text): string
    {
        return preg_replace(
            "/^[0-9]+\. \([0-9]+\)/",
            '',
            $text
        );
    }

    /**
     * @param string $text
     * @return array
     * @throws QuestionTextParserFailedException
     */
    private static function splitByLineAndAnswerIdentifiers(string $text): array
    {
        if (strpos($text, "\r\n") !== false) {
            return preg_split(
                "/\r\n[a-dA-D]\.\s/",
                $text
            );
        } elseif (strpos($text, "\n") !== false) {
            return preg_split(
                "/\n[a-dA-D]\.\s/",
                $text
            );
        } else {
            throw new QuestionTextParserFailedException();
        }
    }
}