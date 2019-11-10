<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:08 PM
 */

namespace CDCMastery\Models\FlashCards;


class Card
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $front;

    /**
     * @var string
     */
    private $back;

    /**
     * @var string
     */
    private $category;

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
    public function getFront(): string
    {
        return $this->front;
    }

    /**
     * @param string $front
     */
    public function setFront(string $front): void
    {
        $this->front = $front;
    }

    /**
     * @return string
     */
    public function getBack(): string
    {
        return $this->back;
    }

    /**
     * @param string $back
     */
    public function setBack(string $back): void
    {
        $this->back = $back;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
}