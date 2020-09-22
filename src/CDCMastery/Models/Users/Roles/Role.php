<?php
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

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @return string|null
     */
    public function getUuid(): ?string
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
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES)) {
            return;
        }

        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
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
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}