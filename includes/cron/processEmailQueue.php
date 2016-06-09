<?php
define('BASE_PATH', realpath(__DIR__) . "/../..");
require BASE_PATH . '/includes/bootstrap.inc.php';

if (!$emailQueue->processQueue()) {
    echo "The e-mail queue could not be processed.";
    exit(1);
} else {
    echo "Queue processed successfully.";
}