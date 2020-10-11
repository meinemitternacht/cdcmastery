<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 9:33 PM
 */

namespace CDCMastery\Models\Users\Roles;


class Role
{
    public const TYPE_SUPER_ADMIN = 'super_admin';
    public const TYPE_ADMIN = 'admin';
    public const TYPE_QUESTION_EDITOR = 'editor';
    public const TYPE_SUPERVISOR = 'supervisor';
    public const TYPE_TRAINING_MANAGER = 'trainingManager';
    public const TYPE_USER = 'user';

    private const VALID_TYPES = [
        self::TYPE_SUPER_ADMIN,
        self::TYPE_ADMIN,
        self::TYPE_QUESTION_EDITOR,
        self::TYPE_SUPERVISOR,
        self::TYPE_TRAINING_MANAGER,
        self::TYPE_USER,
    ];

    private string $uuid;
    private string $type;
    private string $name;
    private string $description;

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES)) {
            return;
        }

        $this->type = $type;
    }

    public function getName(): ?string
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

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
