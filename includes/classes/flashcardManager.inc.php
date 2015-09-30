<?php

/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 9/30/2015
 * Time: 9:31 AM
 */
class flashcardManager extends CDCMastery
{
    protected $db;
    protected $log;

    public function __construct(mysqli $db, log $log){
        $this->db = $db;
        $this->log = $log;
    }

    public function __destruct(){
        parent::__destruct();
    }
}