<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 11:18 AM
 */

namespace CDCMastery\Models\Config;


use CDCMastery\Exceptions\Configuration\ConfigFileEmptyException;
use CDCMastery\Exceptions\Configuration\ConfigFileInvalidException;
use CDCMastery\Exceptions\Files\FileNotFoundException;
use CDCMastery\Exceptions\Files\FileNotReadableException;
use JsonException;
use stdClass;

class Config
{
    private const CONFIG_FILE = APP_DIR . '/config.json';

    private stdClass $configData;

    /**
     * Config constructor.
     * @throws ConfigFileEmptyException
     * @throws ConfigFileInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws JsonException
     */
    public function __construct()
    {
        $this->loadConfigurationData();
    }

    /**
     * @return bool
     * @throws ConfigFileEmptyException
     * @throws ConfigFileInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws JsonException
     */
    private function loadConfigurationData(): bool
    {
        if (!defined('APP_DIR')) {
            die('You are not supposed to be here.');
        }

        if (!is_file(self::CONFIG_FILE)) {
            throw new FileNotFoundException("Configuration file does not exist: " . self::CONFIG_FILE);
        }

        $configFile = file_get_contents(self::CONFIG_FILE);

        if ($configFile === false) {
            throw new FileNotReadableException("Configuration file was not readable: " . self::CONFIG_FILE);
        }

        if (empty($configFile)) {
            throw new ConfigFileEmptyException("Configuration file was empty: " . self::CONFIG_FILE);
        }

        $data = json_decode($configFile, false, 512, JSON_THROW_ON_ERROR);

        if ($data === null) {
            throw new ConfigFileInvalidException("Configuration file could not be decoded: " . self::CONFIG_FILE);
        }

        $this->configData = $data;
        return !empty($this->configData);
    }

    /**
     * @param string[] $properties
     * @return mixed
     */
    private function getPropertyValue(array $properties)
    {
        if (empty($properties)) {
            return null;
        }

        $obj = $this->configData;

        foreach ($properties as $property) {
            if (!isset($obj->{$property})) {
                return null;
            }

            $obj = $obj->{$property};
        }

        return $obj;
    }

    /**
     * @param array $propertyPath
     * @return mixed
     */
    public function get(array $propertyPath)
    {
        return $this->getPropertyValue($propertyPath);
    }
}