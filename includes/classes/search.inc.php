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
    /*
     * Valid search parameter join methods:
     * AND
     * OR
     */
    public $searchParameterJoinMethod;

    /*
     * Valid search tables are tables in the database! (duh)
     */
    public $searchTable;

    /*
     * Valid return column is usually UUID, but it will be whatever column(s) we want to get from the database
     */
    public $returnColumn;
    public $searchQuery;
    public $orderBy;
    public $oderDirection;

    public function __construct(mysqli $db, log $log){
        $this->db = $db;
        $this->log = $log;
    }

    public function executeSearch(){
        if(!$this->searchType){
            $this->error = "Search type was not defined.";
            return false;
        }
        elseif(empty($this->searchParametersSingleValue) && empty($this->searchParametersMultipleValues)){
            $this->error = "There are no search parameters.";
            return false;
        }

        switch($this->searchType){
            case "user":
                $this->returnColumn = "uuid";
                $this->searchTable = "userData";
                $this->orderBy[] = "userLastName";
                $this->orderBy[] = "userFirstName";
                $this->orderBy[] = "userRank";
                $this->orderDirection = "ASC";
                break;
            case "AFSCassociations":
                $this->returnColumn = "userUUID";
                $this->searchTable = "userAFSCAssociations";
                $this->orderBy[] = "uuid";
                $this->orderDirection = "ASC";
                break;
            case "testHistory":
                $this->returnColumn = "uuid";
                $this->searchTable = "testHistory";
                $this->orderBy[] = "testTimeCompleted";
                $this->orderDirection = "DESC";
                break;
        }

        if(!empty($this->searchParametersSingleValue)) {
            foreach ($this->searchParametersSingleValue as $searchParameterKey => $searchParameter) {
                $queryAppend[] = $this->db->real_escape_string($searchParameterKey) . " LIKE '%" . $this->db->real_escape_string($searchParameter) . "%'";
            }
        }

        if(!empty($this->searchParametersMultipleValues)) {
            foreach ($this->searchParametersMultipleValues as $searchParameterKey => $searchParameterList){
                array_map(array($this->db, 'real_escape_string'),$searchParameterList);
                $queryAppend[] = $this->db->real_escape_string($searchParameterKey) . " IN ('".implode("','",$searchParameterList)."')";
            }
        }

        if(isset($queryAppend) && !empty($queryAppend)) {
            $this->searchQuery = "SELECT " . $this->returnColumn . " AS searchResult FROM " . $this->searchTable . " WHERE ";

            if(count($queryAppend) > 1){
                $this->searchQuery .= implode(" " . $this->searchParameterJoinMethod . " ",$queryAppend);
            }
            else{
                $this->searchQuery .=  $queryAppend[0];
            }

            $this->searchQuery .= " ORDER BY " . implode(" " . $this->orderDirection . ", ",$this->orderBy) . " " . $this->orderDirection;

            $res = $this->db->query($this->searchQuery);

            if ($res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $resultArray[] = $row['searchResult'];
                }

                if (!empty($resultArray)) {
                    return $resultArray;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        else{
            return false;
        }
    }

    public function getSearchType(){
        return $this->searchType;
    }

    public function getSearchParametersSingleValue(){
        return $this->searchParametersSingleValue;
    }

    public function getSearchParametersMultipleValues(){
        return $this->searchParametersMultipleValues;
    }

    public function getSearchParameterJoinMethod(){
        return $this->searchParameterJoinMethod;
    }

    public function getSearchTable(){
        return $this->searchTable;
    }

    public function getReturnColumn(){
        return $this->returnColumn;
    }

    public function setSearchType($searchType){
        $this->searchType = $searchType;
        return true;
    }

    public function setSearchParameterJoinMethod($searchParameterJoinMethod){
        $joinMethod = strtoupper($searchParameterJoinMethod);

        if($joinMethod == "AND" || $joinMethod == "OR"){
            $this->searchParameterJoinMethod = $joinMethod;

            return true;
        }
        else{
            $this->searchParameterJoinMethod = "OR";

            return true;
        }
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