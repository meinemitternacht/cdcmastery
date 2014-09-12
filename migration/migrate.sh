#!/bin/bash

php migrateBases.php
php migrateOfficeSymbols.php
php migrateRoles.php
php migrateUsers.php
php migrateTestHistory.php
php migrateTestData.php
php migrateUserSupervisorAssociations.php
php migrateUserTrainingManagerAssociations.php
php migrateAFSCs.php
php migrateAFSCAssociations.php