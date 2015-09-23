<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/22/2015
 * Time: 9:35 PM
 */

class search extends CDCMastery
{
    protected $db;
    protected $log;
    protected $afscManager;
    protected $answerManager;
    protected $questionManager;
    protected $associationManager;
    protected $userManager;
    protected $userStatisticsManager;
    protected $testManager;
    protected $roleManager;

    public $error;
    public $uuid;

    /*
     * Valid Search Types:
     *
     * log
     * afsc
     * answer
     * question
     * association
     * user
     * userStatistic
     * test
     * role
     */
    public $searchType;
    public $searchParametersSingleValue;
    public $searchParametersMultipleValues;

    public function __construct(mysqli $db,
                                log $log,
                                afsc $afsc,
                                answerManager $answerManager,
                                questionManager $questionManager,
                                associations $associations,
                                user $user,
                                userStatistics $userStatistics,
                                testManager $testManager,
                                roles $roles){
        $this->db = $db;
        $this->log = $log;
        $this->afscManager = $afsc;
        $this->answerManager = $answerManager;
        $this->questionManager = $questionManager;
        $this->associationManager = $associations;
        $this->userManager = $user;
        $this->userStatisticsManager = $userStatistics;
        $this->testManager = $testManager;
        $this->roleManager = $roles;
    }

    public function executeSearch(){
        if(!$this->searchType){
            $this->error = "Search type was not defined.";
            return false;
        }
        elseif(empty($this->searchParameters) && empty($this->searchParametersMultipleValues)){
            $this->error = "There are no search parameters.";
            return false;
        }

        switch($this->searchType){
            case "user":
                if(!empty($this->searchParametersSingleValue)) {
                    foreach ($this->searchParametersSingleValue as $searchParameterKey => $searchParameter) {
                        $queryAppend[] = $searchParameterKey . " LIKE '%" . $this->db->real_escape_string($searchParameter) . "%''";
                    }
                }

                if(!empty($this->searchParametersMultipleValues)) {
                    foreach ($this->searchParametersMultipleValues as $searchParameterKey => $searchParameterList){
                        array_map(array($this->db, 'real_escape_string'),$searchParameterList);
                        $queryAppend[] = $searchParameterKey . " IN ('".implode("','",$searchParameterList)."')";
                    }
                }

                var_dump($queryAppend);

                break;
        }

        return true;
    }

    public function getSearchType(){
        return $this->searchType;
    }

    public function getSearchParametersSingleValue(){
        return $this->searchParametersSingleValue;
    }

    public function setSearchType($searchType){
        $this->searchType = $searchType;
        return true;
    }

    public function addSearchParameterSingleValue(array $searchParameter){
        $this->searchParametersSingleValue[$searchParameter[0]] = $searchParameter[1];
        return true;
    }

    public function addSearchParameterMultipleValues($dataName, array $valueList){
        $this->searchParametersMultipleValues[$dataName] = $valueList;

        return true;
    }

    public function __destruct(){
        parent::__destruct();
    }
}