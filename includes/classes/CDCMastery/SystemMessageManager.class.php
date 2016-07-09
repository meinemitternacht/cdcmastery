<?php

namespace CDCMastery;

class SystemMessageManager extends CDCMastery
{
    public $validMessageTypes = ['success','info','warning','danger'];

    public function __construct(){
        parent::__construct();
        if(!isset($_SESSION['messageStore'])){
            $_SESSION['messageStore'] = Array();
        }
    }

    public function addMessage($message,$messageType="info"){
        if(!in_array($messageType,$this->validMessageTypes)){
            return false;
        }
        else{
            $_SESSION['messageStore'][$messageType][] = $message;
        }
        return true;
    }

    public function clearMessages(){
        $_SESSION['messageStore'] = Array();

        return true;
    }

    public function getMessageCount(){
        $count = 0;
        foreach($this->validMessageTypes as $messageType){
            if(!isset($_SESSION['messageStore'][$messageType])){
                continue;
            }
            
            $count += count($_SESSION['messageStore'][$messageType]);
        }

        return $count;
    }
    
    public function getValidMessageTypes(){
        return $this->validMessageTypes;
    }

    public function retrieveMessages(){
        $messageArray = $_SESSION['messageStore'];
        $this->clearMessages();

        return $messageArray;
    }

    public function __destruct(){
    }
}