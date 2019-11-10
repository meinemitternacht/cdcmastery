<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:05 PM
 */

namespace CDCMastery\Models\CdcData;


class Afsc
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
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $version;

    /**
     * @var bool
     */
    private $fouo;

    /**
     * @var bool
     */
    private $hidden;

    /**
     * @var bool
     */
    private $obsolete;

    /**
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid ?? '';
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return null|string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param null|string $version
     */
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return bool
     */
    public function isFouo(): bool
    {
        return $this->fouo;
    }

    /**
     * @param bool $fouo
     */
    public function setFouo(bool $fouo): void
    {
        $this->fouo = $fouo;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return bool
     */
    public function isObsolete(): bool
    {
        return $this->obsolete ?? false;
    }

    /**
     * @param bool $obsolete
     */
    public function setObsolete(bool $obsolete): void
    {
        $this->obsolete = $obsolete;
    }
}