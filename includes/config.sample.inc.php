<?php
$configurationManager = new CDCMastery\ConfigurationManager();

$configurationManager->setMemcachedConfiguration('host','127.0.0.1');
$configurationManager->setMemcachedConfiguration('port','11211');

$configurationManager->setEncryptionKey('***CHANGE ME***');  /* Enter a long string of hexadecimal characters */

$configurationManager->setDatabaseConfiguration('host','127.0.0.1');
$configurationManager->setDatabaseConfiguration('port','3306');
$configurationManager->setDatabaseConfiguration('socket','unix:/var/run/mysqld/mysqld.sock');
$configurationManager->setDatabaseConfiguration('name','<database name>');
$configurationManager->setDatabaseConfiguration('username','<username>');
$configurationManager->setDatabaseConfiguration('password','<password>');

$configurationManager->setMailServerConfiguration('host','<host>');
$configurationManager->setMailServerConfiguration('port','<port>');
$configurationManager->setMailServerConfiguration('username','<username>');
$configurationManager->setMailServerConfiguration('password','<password>');

$configurationManager->setXMLArchiveConfiguration('directory','<directory>'); /* Directory to save archived tests to, with a trailing slash */

$configurationManager->setNGINXConfiguration('error_log','<path>'); /* Path to the web server error log */
