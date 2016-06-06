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
                            'tests',
                            'system');

$statValueList = Array( 'active',
                        'average',
                        'average-day',
                        'average-month',
                        'average-seven-days',
                        'average-week',
                        'average-year',
                        'category',
                        'count-hour',
                        'count-day-of-month',
                        'count-day',
                        'count-month',
                        'count-week',
                        'count-year',
                        'emails-day',
                        'errors-day',
                        'groups',
                        'logins-day',
                        'logins-month',
                        'logins-year',
                        'pass-rate',
                        'rank',
                        'registrations-day',
                        'tests',
                        'users',
                        'user-composition-30-days');

if(!$statSection && !$statValue){
    $cdcMastery->redirect("/about/statistics/tests/average-seven-days");
}

if(!in_array($statSection,$statSectionList) || !in_array($statValue,$statValueList)){
    $sysMsg->addMessage("That statistic does not exist.","warning");
    $cdcMastery->redirect("/errors/404");
}

if(!file_exists(BASE_PATH . "/includes/modules/statistics/".$statSection."/".$statValue.".php")){
    $sysMsg->addMessage("That statistic does not exist.","warning");
    $cdcMastery->redirect("/errors/404");
}

include BASE_PATH . "/includes/modules/statistics/".$statSection."/".$statValue.".php";
