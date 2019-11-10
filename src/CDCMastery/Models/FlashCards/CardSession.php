<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 12:14 PM
 */

namespace CDCMastery\Models\FlashCards;


use DateTime;

class CardSession
{
    /**
     * @var string
     */
    private $userUuid;

    /**
     * @var string
     */
    private $category;

    /**
     * @var string[]
     */
    private $cardUuidList;

    /**
     * @var int
     */
    private $currentCard;

    /**
     * @var int
     */
    private $state;

    /**
     * @var DateTime
     */
    private $timeStarted;

    /**
     * @return int
     */
    public function countCards(): int
    {
        return count($this->cardUuidList ?? []);
    }

    /**
     * @return string
     */
    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    /**
     * @param string $userUuid
     */
    public function setUserUuid(string $userUuid): void
    {
        $this->userUuid = $userUuid;
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

    /**
     * @return \string[]
     */
    public function getCardUuidList(): array
    {
        return $this->cardUuidList;
    }

    /**
     * @param \string[] $cardUuidList
     */
    public function setCardUuidList(array $cardUuidList): void
    {
        $this->cardUuidList = $cardUuidList;
    }

    /**
     * @return int
     */
    public function getCurrentCard(): int
    {
        return $this->currentCard;
    }

    /**
     * @param int $currentCard
     */
    public function setCurrentCard(int $currentCard): void
    {
        $this->currentCard = $currentCard;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return DateTime
     */
    public function getTimeStarted(): DateTime
    {
        return $this->timeStarted;
    }

    /**
     * @param DateTime $timeStarted
     */
    public function setTimeStarted(DateTime $timeStarted): void
    {
        $this->timeStarted = $timeStarted;
    }
}