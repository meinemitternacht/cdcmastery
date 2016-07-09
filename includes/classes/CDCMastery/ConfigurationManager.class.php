<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 7/1/16
 * Time: 4:18 AM
 */
namespace CDCMastery;

class ConfigurationManager
{
    protected $encryptionKey;
    protected $databaseConfiguration = [];
    protected $mailServerConfiguration = [];
    protected $memcachedConfiguration = [];
    protected $nginxConfiguration = [];
    protected $xmlArchiveConfiguration = [];

    public function __construct()
    {

    }

    public function getEncryptionKey()
    {
        return $this->encryptionKey;
    }

    public function setEncryptionKey($encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;

        return true;
    }

    public function getDatabaseConfiguration($arrayKey)
    {
        if (isset($this->databaseConfiguration[$arrayKey])) {
            return $this->databaseConfiguration[$arrayKey];
        }
        else {
            return false;
        }
    }

    public function setDatabaseConfiguration($arrayKey, $value)
    {
        $this->databaseConfiguration[$arrayKey] = $value;

        return true;
    }

    public function getMailServerConfiguration($arrayKey)
    {
        if (isset($this->mailServerConfiguration[$arrayKey])) {
            return $this->mailServerConfiguration[$arrayKey];
        }
        else {
            return false;
        }
    }

    public function setMailServerConfiguration($arrayKey, $value)
    {
        $this->mailServerConfiguration[$arrayKey] = $value;

        return true;
    }

    public function getMemcachedConfiguration($arrayKey)
    {
        if (isset($this->memcachedConfiguration[$arrayKey])) {
            return $this->memcachedConfiguration[$arrayKey];
        }
        else {
            return false;
        }
    }

    public function setMemcachedConfiguration($arrayKey, $value)
    {
        $this->memcachedConfiguration[$arrayKey] = $value;

        return true;
    }

    public function getNGINXConfiguration($arrayKey)
    {
        if (isset($this->nginxConfiguration[$arrayKey])) {
            return $this->nginxConfiguration[$arrayKey];
        }
        else {
            return false;
        }
    }

    public function setNGINXConfiguration($arrayKey, $value)
    {
        $this->nginxConfiguration[$arrayKey] = $value;

        return true;
    }

    public function getXMLArchiveConfiguration($arrayKey)
    {
        if (isset($this->xmlArchiveConfiguration[$arrayKey])) {
            return $this->xmlArchiveConfiguration[$arrayKey];
        }
        else {
            return false;
        }
    }

    public function setXMLArchiveConfiguration($arrayKey, $value)
    {
        $this->xmlArchiveConfiguration[$arrayKey] = $value;

        return true;
    }
}