<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 12:12 PM
 */

namespace CDCMastery\Models\FlashCards;


class Category
{
    public const TYPE_AFSC = 'afsc';
    public const TYPE_GLOBAL = 'global';
    public const TYPE_PRIVATE = 'private';

    private string $uuid;
    private string $name;
    private bool $encrypted;
    private string $type;
    private ?string $binding = null;
    private string $createdBy;
    private ?string $comments = null;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function setEncrypted(bool $encrypted): void
    {
        $this->encrypted = $encrypted;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getBinding(): ?string
    {
        return $this->binding;
    }

    public function setBinding(?string $binding): void
    {
        $this->binding = $binding;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param string|null $comments
     */
    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
    }
}