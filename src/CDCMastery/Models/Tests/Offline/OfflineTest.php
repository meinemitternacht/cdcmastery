<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 7:00 PM
 */

namespace CDCMastery\Models\Tests\Offline;


use CDCMastery\Models\CdcData\CdcData;
use DateTime;

class OfflineTest
{
    private string $uuid;
    private string $user_uuid;
    private CdcData $cdc_data;
    private DateTime $date_created;
    private ?array $deleted = null;

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
    public function getUserUuid(): string
    {
        return $this->user_uuid;
    }

    /**
     * @param string $user_uuid
     */
    public function setUserUuid(string $user_uuid): void
    {
        $this->user_uuid = $user_uuid;
    }

    /**
     * @return CdcData
     */
    public function getCdcData(): CdcData
    {
        return $this->cdc_data;
    }

    /**
     * @param CdcData $cdc_data
     */
    public function setCdcData(CdcData $cdc_data): void
    {
        $this->cdc_data = $cdc_data;
    }

    /**
     * @return int
     */
    public function getNumQuestions(): int
    {
        if (!$this->cdc_data) {
            return 0;
        }

        return count($this->cdc_data->getQuestionAnswerData());
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->date_created;
    }

    /**
     * @param DateTime $date_created
     */
    public function setDateCreated(DateTime $date_created): void
    {
        $this->date_created = $date_created;
    }

    /**
     * @return array|null
     */
    public function getDeleted(): ?array
    {
        return $this->deleted;
    }

    /**
     * @param array|null $deleted
     */
    public function setDeleted(?array $deleted): void
    {
        $this->deleted = $deleted;
    }
}
