<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/7/16
 * Time: 7:01 PM
 */

$logActionList = $log->listLogActions();
$logActionArray = $log->getAllActionArrays();

foreach($logActionList as $logAction){
    if(!in_array($logAction,$logActionArray)){
        echo $logAction . "<br>".PHP_EOL;
    }
}