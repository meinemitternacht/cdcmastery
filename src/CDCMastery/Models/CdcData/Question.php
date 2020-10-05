<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 3:54 PM
 */

namespace CDCMastery\Models\CdcData;

class Question
{
    private string $uuid;
    private string $afscUuid;
    private string $text;
    private bool $disabled;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getAfscUuid(): string
    {
        return $this->afscUuid;
    }

    public function setAfscUuid(string $afscUuid): void
    {
        $this->afscUuid = $afscUuid;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function isDisabled(): bool
    {
        return $this->disabled ?? false;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }
}