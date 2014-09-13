<?php

/**
 *  @author     Stefan Gabos <ix@nivelzero.ro>
 *  @version    1.0.6 (last revision: October 01, 2007)
 *  @copyright  (c) 2006 - 2007 Stefan Gabos
 *  @package    dbSession
 *  @example    example.php
*/

class dbSession
{
	protected $db;

    function __construct(mysqli $db, $gc_maxlifetime = "86400", $gc_probability = "1", $gc_divisor = "5", $securityCode = "8dF8dAxSpdBiC", $tableName = "sessionData")
    {
    	$this->db = $db;

        if ($gc_maxlifetime != "" && is_integer($gc_maxlifetime)) {
            @ini_set('session.gc_maxlifetime', $gc_maxlifetime);
        }

        if ($gc_probability != "" && is_integer($gc_probability)) {
            @ini_set('session.gc_probability', $gc_probability);
        }

        if ($gc_divisor != "" && is_integer($gc_divisor)) {
            @ini_set('session.gc_divisor', $gc_divisor);
        }

        $this->sessionLifetime = ini_get("session.gc_maxlifetime");
        $this->securityCode = $securityCode;
        $this->tableName = $tableName;

        session_set_save_handler(
            array(&$this, 'open'),
            array(&$this, 'close'),
            array(&$this, 'read'),
            array(&$this, 'write'),
            array(&$this, 'destroy'),
            array(&$this, 'gc')
        );

        register_shutdown_function('session_write_close');

        if(php_sapi_name() != 'cli'){
        	session_start();
        }
    }

    function stop()
    {
        $this->regenerate_id();
        session_unset();
        session_destroy();
    }

    function regenerate_id()
    {
        $oldSessionID = session_id();
        session_regenerate_id();
        $this->destroy($oldSessionID);
    }

    function get_users_online()
    {
        $this->gc($this->sessionLifetime);

        $res = $this->db->query("SELECT COUNT(session_id) AS count FROM ".$this->tableName);
        $row = $res->fetch_assoc();

        return $row["count"];
    }

    function open($save_path, $session_name)
    {
        return true;
    }

    function close()
    {
        return true;
    }

    function read($session_id)
    {
    	$query = "SELECT session_data FROM ".$this->tableName." WHERE
    								session_id = '".$this->db->real_escape_string($session_id)."' AND
    								http_user_agent = '".$this->db->real_escape_string(md5($_SERVER['HTTP_USER_AGENT'] . $this->securityCode))."' AND
    								session_expire > '".time()."' LIMIT 1";

    	$res = $this->db->query($query);
    	$row = $res->fetch_assoc();

    	if(!empty($row['session_data'])){
    		return $row['session_data'];
    	}
    	else{
    		return "";
    	}
    }

    function write($session_id, $session_data)
    {
    	$res = $this->db->query("INSERT INTO ".$this->tableName." (session_id,http_user_agent,session_data,session_expire)
    								VALUES (
    								'".$this->db->real_escape_string($session_id)."',
    								'".$this->db->real_escape_string(md5($_SERVER['HTTP_USER_AGENT'] . $this->securityCode))."',
    								'".$this->db->real_escape_string($session_data)."',
    								'".$this->db->real_escape_string(time() + $this->sessionLifetime)."')
    								ON DUPLICATE KEY UPDATE
    									session_data = '".$this->db->real_escape_string($session_data)."',
    									session_expire = '".$this->db->real_escape_string(time() + $this->sessionLifetime)."'");

    	if($res){
	    	if($this->db->affected_rows > 1){
	    		return true;
	    	}
	    	else{
	    		return "";
	    	}
    	}
    	else{
    		return false;
    	}
    }

    function destroy($session_id)
    {
    	$res = $this->db->query("DELETE FROM ".$this->tableName." WHERE session_id = '".$this->db->real_escape_string($session_id)."'");

    	if($this->db->affected_rows > 0){
            return true;
        }

        return false;
    }

    function gc($maxlifetime)
    {
    	if(!$this->db->query("DELETE FROM ".$this->tableName." WHERE session_expire < '".$this->db->real_escape_string(time() - $maxlifetime)."'")){
    		return false;
    	}
    	else{
    		return true;
    	}
    }

    function __destruct(){
    	$this->db->close();
    }
}
?>
