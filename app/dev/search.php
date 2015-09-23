<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/22/2015
 * Time: 10:05 PM
 */
$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);
$testManager = new testManager($db,$log,$afsc);

$searchObj = new search($db,$log,$afsc,$answerManager,$questionManager,$assoc,$user,$userStatistics,$testManager,$roles);

$searchObj->setSearchType("user");

$searchParam = Array("userLastName","Bing");
$searchValueList = Array("something","else","is","needed","here");

$searchObj->addSearchParameterSingleValue($searchParam);
$searchObj->addSearchParameterMultipleValues("ExampleParam",$searchValueList);

$searchObj->executeSearch();