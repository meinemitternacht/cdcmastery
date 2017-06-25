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
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $encrypted;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $binding;

    /**
     * @var bool
     */
    private $private;

    /**
     * @var string
     */
    private $createdBy;

    /**
     * @var string
     */
    private $comments;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @param bool $encrypted
     */
    public function setEncrypted(bool $encrypted)
    {
        $this->encrypted = $encrypted;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getBinding(): string
    {
        return $this->binding;
    }

    /**
     * @param string $binding
     */
    public function setBinding(string $binding)
    {
        $this->binding = $binding;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * @param bool $private
     */
    public function setPrivate(bool $private)
    {
        $this->private = $private;
    }

    /**
     * @return string
     */
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * @param string $createdBy
     */
    public function setCreatedBy(string $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return string
     */
    public function getComments(): string
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments(string $comments)
    {
        $this->comments = $comments;
    }
}