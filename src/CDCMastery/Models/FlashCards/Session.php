<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 12:14 PM
 */

namespace CDCMastery\Models\FlashCards;


class Session
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
     * @var \DateTime
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
    public function setUserUuid(string $userUuid)
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
    public function setCategory(string $category)
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
    public function setCardUuidList(array $cardUuidList)
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
    public function setCurrentCard(int $currentCard)
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
    public function setState(int $state)
    {
        $this->state = $state;
    }

    /**
     * @return \DateTime
     */
    public function getTimeStarted(): \DateTime
    {
        return $this->timeStarted;
    }

    /**
     * @param \DateTime $timeStarted
     */
    public function setTimeStarted(\DateTime $timeStarted)
    {
        $this->timeStarted = $timeStarted;
    }
}