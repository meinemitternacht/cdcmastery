<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 3:54 PM
 */

namespace CDCMastery\Models\CdcData;

class Question
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $afscUuid;

    /**
     * @var string
     */
    private $text;

    /**
     * @var bool
     */
    private $disabled;

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
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getAfscUuid(): string
    {
        return $this->afscUuid;
    }

    /**
     * @param string $afscUuid
     */
    public function setAfscUuid(string $afscUuid): void
    {
        $this->afscUuid = $afscUuid;
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
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled ?? false;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }
}