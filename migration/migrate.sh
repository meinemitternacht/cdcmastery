#!/bin/bash

php migrateBases.php
php migrateOfficeSymbols.php
php migrateRoles.php
php migrateUsers.php
php migrateTestHistory.php
php migrateTestData.php