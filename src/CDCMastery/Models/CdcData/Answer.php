<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:03 PM
 */

namespace CDCMastery\Models\CdcData;


class Answer
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $text;

    /**
     * @var bool
     */
    private $correct;

    /**
     * @var string
     */
    private $questionUuid;

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text)
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isCorrect(): bool
    {
        return $this->correct;
    }

    /**
     * @param bool $correct
     */
    public function setCorrect(bool $correct)
    {
        $this->correct = $correct;
    }

    /**
     * @return string
     */
    public function getQuestionUuid(): string
    {
        return $this->questionUuid;
    }

    /**
     * @param string $questionUuid
     */
    public function setQuestionUuid(string $questionUuid)
    {
        $this->questionUuid = $questionUuid;
    }
}