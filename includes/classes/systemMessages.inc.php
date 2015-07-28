<?php
class systemMessages extends cdcMastery
{
    public function __construct(){
        parent::__construct();
        if(!isset($_SESSION['messageStore'])){
            $_SESSION['messageStore'] = Array();
        }
    }

    public function addMessage($message){
        $_SESSION['messageStore'][] = $message;
        return true;
    }

    public function clearMessages(){
        $_SESSION['messageStore'] = Array();

        return true;
    }

    public function getMessageCount(){
        return count($_SESSION['messageStore']);
    }

    public function retrieveMessages(){
        $rtnVal = $_SESSION['messageStore'];
        $this->clearMessages();

        return $rtnVal;
    }

    public function __destruct(){
    }
}