<?php
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
use stdClass;

class Config
{
    /**
     * @var stdClass
     */
    protected $configData;

    /**
     * Config constructor.
     * @throws ConfigFileEmptyException
     * @throws ConfigFileInvalidException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
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
     */
    private function loadConfigurationData(): bool
    {
        if (!defined('APP_DIR')) {
            die('You are not supposed to be here.');
        }

        if (!file_exists(APP_DIR . '/config.json')) {
            throw new FileNotFoundException("Configuration file does not exist: " . APP_DIR . '/config.json');
        }

        $configFile = file_get_contents(APP_DIR . '/config.json');

        if ($configFile === false) {
            throw new FileNotReadableException("Configuration file was not readable: " . APP_DIR . '/config.json');
        }

        if (empty($configFile)) {
            throw new ConfigFileEmptyException("Configuration file was empty: " . APP_DIR . '/config.json');
        }

        $this->configData = json_decode($configFile);

        if ($this->configData === false) {
            throw new ConfigFileInvalidException("Configuration file could not be decoded: " . APP_DIR . '/config.json');
        }

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