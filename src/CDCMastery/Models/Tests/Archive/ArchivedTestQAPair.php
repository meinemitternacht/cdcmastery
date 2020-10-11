<?php
declare(strict_types=1);


namespace CDCMastery\Models\Tests\Archive;


class ArchivedTestQAPair
{
    private string $question_text;
    private string $answer_text;
    private bool $correct;

    /**
     * ArchivedTestQAPair constructor.
     * @param string $question_text
     * @param string $answer_text
     * @param bool $correct
     */
    public function __construct(string $question_text, string $answer_text, bool $correct)
    {
        $this->question_text = $question_text;
        $this->answer_text = $answer_text;
        $this->correct = $correct;
    }

    /**
     * @return string
     */
    public function getQuestionText(): string
    {
        return $this->question_text;
    }

    /**
     * @return string
     */
    public function getAnswerText(): string
    {
        return $this->answer_text;
    }

    /**
     * @return bool
     */
    public function isCorrect(): bool
    {
        return $this->correct;
    }
}
