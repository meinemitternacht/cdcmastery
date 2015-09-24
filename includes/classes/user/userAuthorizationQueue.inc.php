<?php

/**
 * Created by PhpStorm.
 * User: claude
 * Date: 10/27/14
 * Time: 6:14 PM
 */
class userAuthorizationQueue extends user
{
    /**
     * @var mysqli
     */
    protected $db;
    /**
     * @var log
     */
    protected $log;

    public $uuid;
    public $dateRequested;

    /**
     * @param mysqli $db
     * @param log $log
     * @param emailQueue $emailQueue
     */
    public function __construct(mysqli $db, log $log, emailQueue $emailQueue)
    {
        $this->uuid = parent::genUUID();
        $this->db = $db;
        $this->log = $log;

        parent::__construct($db, $log, $emailQueue);
    }

    /**
     *
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * @param $userUUID
     * @param $roleUUID
     * @return bool
     */
    public function queueRoleAuthorization($userUUID, $roleUUID)
    {

        $stmt = $this->db->prepare("INSERT INTO queueRoleAuthorization
                                      (uuid,userUUID,roleUUID,dateRequested)
                                      VALUES (?,?,?,UTC_TIMESTAMP)
                                        ON DUPLICATE KEY UPDATE
                                          userUUID=values(userUUID),
                                          roleUUID=values(roleUUID)");

        $stmt->bind_param("sss", $this->uuid, $userUUID, $roleUUID);

        if ($stmt->execute()) {
            $this->log->setAction("QUEUE_ROLE_AUTHORIZATION");
            $this->log->setDetail("UUID", $this->uuid);
            $this->log->setDetail("User UUID", $userUUID);
            $this->log->setDetail("Role UUID", $roleUUID);
            $this->log->saveEntry();

            $stmt->close();
            return true;
        } else {
            $this->log->setAction("ERROR_QUEUE_ROLE_AUTHORIZATION");
            $this->log->setDetail("UUID", $this->uuid);
            $this->log->setDetail("User UUID", $userUUID);
            $this->log->setDetail("Role UUID", $roleUUID);
            $this->log->saveEntry();

            $stmt->close();
            return false;
        }
    }

    public function listUserAuthorizeQueue(){
        $stmt = $this->db->prepare("SELECT uuid, userUUID, roleUUID, dateRequested
                                    FROM queueRoleAuthorization
                                    ORDER BY dateRequested DESC");

        if($stmt->execute()){
            $stmt->bind_result($uuid,$userUUID,$roleUUID,$dateRequested);

            while($stmt->fetch()){
                $roleAuthorizationArray[$uuid]['userUUID'] = $userUUID;
                $roleAuthorizationArray[$uuid]['roleUUID'] = $roleUUID;
                $roleAuthorizationArray[$uuid]['dateRequested'] = $dateRequested;
            }

            $stmt->close();

            if(isset($roleAuthorizationArray) && !empty($roleAuthorizationArray)){
                return $roleAuthorizationArray;
            }
            else{
                return false;
            }
        }
        else{
            $this->error = $stmt->error;
            $this->log->setAction("MYSQL_ERROR");
            $this->log->setDetail("CALLING FUNCTION","userAuthorizationQueue->listUserAuthorizeQueue()");
            $this->log->setDetail("MYSQL ERROR",$this->error);
            $this->log->saveEntry();
            $stmt->close();

            return false;
        }
    }

    /**
     * @param $userUUID
     * @param $roleUUID
     * @return bool
     */
    public function approveRoleAuthorization($userUUID, $roleUUID)
    {
        if (parent::loadUser($userUUID)) {
            parent::setUserRole($roleUUID);

            if (parent::saveUser()) {
                $this->log->setAction("APPROVE_ROLE_AUTHORIZATION");
                $this->log->setDetail("User UUID", $userUUID);
                $this->log->setDetail("Role UUID", $roleUUID);
                $this->log->saveEntry();

                $stmt = $this->db->prepare("DELETE FROM queueRoleAuthorization
                                      WHERE userUUID = ?
                                      AND roleUUID = ?");

                $stmt->bind_param("ss", $userUUID, $roleUUID);

                if (!$stmt->execute()) {
                    $this->log->setAction("ERROR_APPROVE_ROLE_AUTHORIZATION");
                    $this->log->setDetail("User UUID", $userUUID);
                    $this->log->setDetail("Role UUID", $roleUUID);
                    $this->log->setDetail("Calling Function", "userAuthorizationQueue->approveRoleAuthorization()");
                    $this->log->setDetail("MySQL Error", $stmt->error);
                    $this->log->setDetail("Sub Function", "DELETE FROM queueRoleAuthorization TABLE");
                    $this->log->saveEntry();

                    return false;
                } else {
                    return true;
                }
            } else {
                $this->log->setAction("ERROR_APPROVE_ROLE_AUTHORIZATION");
                $this->log->setDetail("User UUID", $userUUID);
                $this->log->setDetail("Role UUID", $roleUUID);
                $this->log->setDetail("Calling Function", "userAuthorizationQueue->approveRoleAuthorization()");
                $this->log->setDetail("Sub Function", "Save User");
                $this->log->saveEntry();

                return false;
            }
        } else {
            return false;
        }
    }
}