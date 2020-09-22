<?php

namespace CDCMastery\Models\CdcData;


class Afsc
{
    private string $uuid;
    private string $name;
    private ?string $description;
    private ?string $version;
    private ?string $edit_code;
    private bool $fouo;
    private bool $hidden;
    private bool $obsolete;

    public function getUuid(): string
    {
        return $this->uuid ?? '';
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    public function getEditCode(): ?string
    {
        return $this->edit_code;
    }

    public function setEditCode(?string $edit_code): void
    {
        $this->edit_code = $edit_code;
    }

    public function isFouo(): bool
    {
        return $this->fouo;
    }

    public function setFouo(bool $fouo): void
    {
        $this->fouo = $fouo;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function isObsolete(): bool
    {
        return $this->obsolete ?? false;
    }

    public function setObsolete(bool $obsolete): void
    {
        $this->obsolete = $obsolete;
    }
}