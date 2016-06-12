<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/10/16
 * Time: 9:00 PM
 */

/**
 * Memcache configuration
 */
$cfg['memcache']['host'] = "127.0.0.1";
$cfg['memcache']['port'] = "11211";

/**
 * Encryption Key for FOUO Content
 */
$cfg['encryption']['key'] = "AbCdEfCHANGEMEFeDcBa";

/**
 * Database configuration
 */
$cfg['db']['name'] = "cdcmastery_main";
$cfg['db']['host'] = "127.0.0.1";
$cfg['db']['port'] = "3306";
$cfg['db']['socket'] = "unix:/var/run/mysqld/mysqld.sock";
$cfg['db']['user'] = "<username>";
$cfg['db']['pass'] = "<password>";

/**
 * SMTP E-mail configuration
 */
$cfg['smtp']['host'] = "<host>";
$cfg['smtp']['port'] = "<port>";
$cfg['smtp']['user'] = "<username>";
$cfg['smtp']['pass'] = "<password>";

/**
 * Path to save archived tests to (XML format)
 */
$cfg['xml']['directory'] = "/home/cdcmastery/xml-archives";