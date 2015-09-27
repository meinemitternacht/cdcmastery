<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/26/2015
 * Time: 6:40 PM
 */

$statSection = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$statValue = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

/*
 * To make this secure, check values against a known good list
 */
$statSectionList = Array(   'base',
                            'user',
                            'afsc',
                            'tests');

$statValueList = Array( 'active',
                        'average',
                        'average-month',
                        'average-seven-days',
                        'average-week',
                        'average-year',
                        'category',
                        'count-month',
                        'count-week',
                        'count-year',
                        'groups',
                        'pass-rate',
                        'rank',
                        'tests');

if(!$statSection && !$statValue){
    $cdcMastery->redirect("/about/statistics/afsc/pass-rate");
}

if(!in_array($statSection,$statSectionList) || !in_array($statValue,$statValueList)){
    $sysMsg->addMessage("That statistic does not exist.");
    $cdcMastery->redirect("/errors/404");
}

if(!file_exists(BASE_PATH . "/includes/modules/statistics/".$statSection."/".$statValue.".php")){
    $sysMsg->addMessage("That statistic does not exist.");
    $cdcMastery->redirect("/errors/404");
}

include BASE_PATH . "/includes/modules/statistics/".$statSection."/".$statValue.".php";
?>

