<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/25/2017
 * Time: 9:33 PM
 */

namespace CDCMastery\Models\Users;


class Role
{
    const TYPE_ADMIN = 'admin';
    const TYPE_QUESTION_EDITOR = 'editor';
    const TYPE_SUPERVISOR = 'supervisor';
    const TYPE_TRAINING_MANAGER = 'trainingManager';
    const TYPE_USER = 'user';

    const VALID_TYPES = [
        self::TYPE_ADMIN,
        self::TYPE_QUESTION_EDITOR,
        self::TYPE_SUPERVISOR,
        self::TYPE_TRAINING_MANAGER,
        self::TYPE_USER
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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        if (!in_array($type, self::VALID_TYPES)) {
            return;
        }

        $this->type = $type;
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
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }
}