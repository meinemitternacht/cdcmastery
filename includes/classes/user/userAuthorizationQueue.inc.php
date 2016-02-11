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

    public $queueUUID;
    public $dateRequested;

    /**
     * @param mysqli $db
     * @param log $log
     * @param emailQueue $emailQueue
     */
    public function __construct(mysqli $db, log $log, emailQueue $emailQueue)
    {
        $this->queueUUID = parent::genUUID();
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

        $stmt->bind_param("sss", $this->queueUUID, $userUUID, $roleUUID);

        if ($stmt->execute()) {
            $this->log->setAction("QUEUE_ROLE_AUTHORIZATION");
            $this->log->setDetail("User UUID", $userUUID);
            $this->log->setDetail("Role UUID", $roleUUID);
            $this->log->saveEntry();

            $stmt->close();
            return true;
        } else {
            $this->log->setAction("ERROR_QUEUE_ROLE_AUTHORIZATION");
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
            $stmt->bind_result($queueUUID,$userUUID,$roleUUID,$dateRequested);

            while($stmt->fetch()){
                $roleAuthorizationArray[$queueUUID]['userUUID'] = $userUUID;
                $roleAuthorizationArray[$queueUUID]['roleUUID'] = $roleUUID;
                $roleAuthorizationArray[$queueUUID]['dateRequested'] = $dateRequested;
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
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
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
                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                    $this->log->setDetail("MySQL Error", $stmt->error);
                    $this->log->setDetail("Sub Function", "DELETE FROM queueRoleAuthorization TABLE");
                    $this->log->saveEntry();

                    return false;
                } else {
                    if($this->notifyRoleApproval($userUUID,$roleUUID)){
                        return true;
                    }
                    else{
                        return false;
                    }
                }
            } else {
                $this->log->setAction("ERROR_APPROVE_ROLE_AUTHORIZATION");
                $this->log->setDetail("User UUID", $userUUID);
                $this->log->setDetail("Role UUID", $roleUUID);
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("Sub Function", "Save User");
                $this->log->saveEntry();

                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $userUUID
     * @param $roleUUID
     * @return bool
     */
    public function rejectRoleAuthorization($userUUID, $roleUUID)
    {
        if (parent::loadUser($userUUID)) {
                $this->log->setAction("REJECT_ROLE_AUTHORIZATION");
                $this->log->setDetail("User UUID", $userUUID);
                $this->log->setDetail("Role UUID", $roleUUID);
                $this->log->saveEntry();

                $stmt = $this->db->prepare("DELETE FROM queueRoleAuthorization
                                      WHERE userUUID = ?
                                      AND roleUUID = ?");

                $stmt->bind_param("ss", $userUUID, $roleUUID);

                if (!$stmt->execute()) {
                    $this->log->setAction("ERROR_REJECT_ROLE_AUTHORIZATION");
                    $this->log->setDetail("User UUID", $userUUID);
                    $this->log->setDetail("Role UUID", $roleUUID);
                    $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                    $this->log->setDetail("MySQL Error", $stmt->error);
                    $this->log->setDetail("Sub Function", "DELETE FROM queueRoleAuthorization TABLE");
                    $this->log->saveEntry();

                    return false;
                } else {
                    if($this->notifyRoleApproval($userUUID,$roleUUID)){
                        return true;
                    }
                    else{
                        return false;
                    }
                }
        } else {
            return false;
        }
    }

    /**
     * Notifies the user when their pending role association was approved
     * @param $userUUID
     * @param $roleUUID
     * @return bool
     */
    public function notifyRoleApproval($userUUID,$roleUUID){
        $_roles = new roles($this->db,$this->log,$this->emailQueue);

        if(!$_roles->verifyRole($roleUUID)){
            $this->error = "For some reason, that role does not exist.  This is not good!";

            $this->log->setAction("ERROR_NOTIFY_ROLE_APPROVAL");
            $this->log->setUserUUID($userUUID);
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("Child function","_roles->verifyRole()");
            $this->log->setDetail("Error",$this->error);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("Role UUID",$roleUUID);
            $this->log->saveEntry();

            return false;
        }

        if($this->verifyUser($userUUID)){
            $this->loadUser($userUUID);

            $emailSender = "support@cdcmastery.com";
            $emailRecipient = $this->getUserEmail();
            $emailSubject = $_roles->getRoleName($roleUUID) . " Account Approved";

            $emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
            $emailBodyHTML .= $this->getFullName().",";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "An administrator at CDCMastery has approved your pending account authorization. Your account now contains permissions for the ".$_roles->getRoleName($roleUUID)." role.";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "If you have any questions about this process, please contact the CDCMastery Help Desk: http://helpdesk.cdcmastery.com/ ";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "Regards,";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "CDCMastery.com";
            $emailBodyHTML .= "</body></html>";

            $emailBodyText = $this->getFullName().",";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "An administrator at CDCMastery has approved your pending account authorization. Your account now contains permissions for the ".$_roles->getRoleName($roleUUID)." role.";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "If you have any questions about this process, please contact the CDCMastery Help Desk: http://helpdesk.cdcmastery.com/ ";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "Regards,";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "CDCMastery.com";

            $queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

            if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
                $this->log->setAction("NOTIFY_ROLE_APPROVAL");
                $this->log->setUserUUID($userUUID);
                $this->log->setDetail("User UUID",$userUUID);
                $this->log->setDetail("Role UUID",$roleUUID);
                $this->log->saveEntry();
                return true;
            }
            else{
                $this->log->setAction("ERROR_NOTIFY_ROLE_APPROVAL");
                $this->log->setUserUUID($userUUID);
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("Child function","emailQueue->queueEmail()");
                $this->log->setDetail("User UUID",$userUUID);
                $this->log->setDetail("Role UUID",$roleUUID);
                $this->log->saveEntry();
                return false;
            }
        }
        else{
            $this->error = "That user does not exist.";
            $this->log->setAction("ERROR_NOTIFY_ROLE_APPROVAL");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("System Error",$this->error);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("Role UUID",$roleUUID);
            $this->log->saveEntry();
            return false;
        }
    }

    /**
     * Notifies the user when their pending role association was rejected
     * @param $userUUID
     * @param $roleUUID
     * @return bool
     */
    public function notifyRoleRejection($userUUID,$roleUUID){
        $_roles = new roles($this->db,$this->log,$this->emailQueue);

        if(!$_roles->verifyRole($roleUUID)){
            $this->error = "For some reason, that role does not exist.  This is not good!";

            $this->log->setAction("ERROR_NOTIFY_ROLE_REJECTION");
            $this->log->setUserUUID($userUUID);
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("Child function","_roles->verifyRole()");
            $this->log->setDetail("Error",$this->error);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("Role UUID",$roleUUID);
            $this->log->saveEntry();

            return false;
        }

        if($this->verifyUser($userUUID)){
            $this->loadUser($userUUID);

            $emailSender = "support@cdcmastery.com";
            $emailRecipient = $this->getUserEmail();
            $emailSubject = $_roles->getRoleName($roleUUID) . " Account Rejected";

            $emailBodyHTML	= "<html><head><title>".$emailSubject."</title></head><body>";
            $emailBodyHTML .= $this->getFullName().",";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "An administrator at CDCMastery has rejected your pending account authorization. Reasons for this include inability to verify your position, or duplicate accounts.
            If you wish to discuss this decision with the administrator, please open a support ticket at http://helpdesk.cdcmastery.com.  In the meantime, your account will retain user permissions.";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "Regards,";
            $emailBodyHTML .= "<br /><br />";
            $emailBodyHTML .= "CDCMastery.com";
            $emailBodyHTML .= "</body></html>";

            $emailBodyText = $this->getFullName().",";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "An administrator at CDCMastery has rejected your pending account authorization. Reasons for this include inability to verify your position, or duplicate accounts.
            If you wish to discuss this decision with the administrator, please open a support ticket at http://helpdesk.cdcmastery.com.  In the meantime, your account will retain user permissions.";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "Regards,";
            $emailBodyText .= "\r\n\r\n";
            $emailBodyText .= "CDCMastery.com";

            $queueUser = isset($_SESSION['userUUID']) ? $_SESSION['userUUID'] : "SYSTEM";

            if($this->emailQueue->queueEmail($emailSender, $emailRecipient, $emailSubject, $emailBodyHTML, $emailBodyText, $queueUser)){
                $this->log->setAction("NOTIFY_ROLE_REJECTION");
                $this->log->setUserUUID($userUUID);
                $this->log->setDetail("User UUID",$userUUID);
                $this->log->setDetail("Role UUID",$roleUUID);
                $this->log->saveEntry();
                return true;
            }
            else{
                $this->log->setAction("ERROR_NOTIFY_ROLE_REJECTION");
                $this->log->setUserUUID($userUUID);
                $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
                $this->log->setDetail("Child function","emailQueue->queueEmail()");
                $this->log->setDetail("User UUID",$userUUID);
                $this->log->setDetail("Role UUID",$roleUUID);
                $this->log->saveEntry();
                return false;
            }
        }
        else{
            $this->error = "That user does not exist.";
            $this->log->setAction("ERROR_NOTIFY_ROLE_REJECTION");
            $this->log->setDetail("Calling Function",__CLASS__ . "->" . __FUNCTION__);
            $this->log->setDetail("System Error",$this->error);
            $this->log->setDetail("User UUID",$userUUID);
            $this->log->setDetail("Role UUID",$roleUUID);
            $this->log->saveEntry();
            return false;
        }
    }
}