<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:08 PM
 */

namespace CDCMastery\Models\FlashCards;


class Card
{
    private string $uuid;
    private string $front;
    private string $back;
    private string $category;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getFront(): string
    {
        return $this->front;
    }

    public function setFront(string $front): void
    {
        $this->front = $front;
    }

    public function getBack(): string
    {
        return $this->back;
    }

    public function setBack(string $back): void
    {
        $this->back = $back;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
}
