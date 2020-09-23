<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/5/2017
 * Time: 8:46 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class UserHelpers
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * AuthProcessor constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    public static function listRanks(bool $keyed = true, bool $show_special = true): array
    {
        $enlisted = [
            'AB' => 'Airman Basic',
            'Amn' => 'Airman',
            'A1C' => "Airman First Class",
            'SrA' => 'Senior Airman',
            'SSgt' => 'Staff Sergeant',
            'TSgt' => 'Technical Sergeant',
            'MSgt' => 'Master Sergeant',
            'SMSgt' => 'Senior Master Sergeant',
            'CMSgt' => 'Chief Master Sergeant',
        ];

        $officer = [
            '2LT' => 'Second Lieutenant',
            '1LT' => 'First Lieutenant',
            'Cpt' => 'Captain',
            'Maj' => 'Major',
            'Lt Col' => 'Lieutenant Colonel',
            'Col' => 'Colonel',
            'Brig Gen' => 'Brigadier General',
            'Maj Gen' => 'Major General',
            'Lt Gen' => 'Lieutenant General',
            'Gen' => 'General',
        ];

        $special = [
            'SSgt (Ret.)' => 'Staff Sergeant (Retired)',
        ];

        if (!$keyed) {
            return $show_special
                ? array_merge($enlisted, $officer, $special)
                : array_merge($enlisted, $officer);
        }

        if (!$show_special) {
            return [
                'Enlisted' => $enlisted,
                'Officer' => $officer,
            ];
        }

        return [
            'Enlisted' => $enlisted,
            'Officer' => $officer,
            'Special' => $special,
        ];
    }

    /**
     * @param string $username
     * @return null|string
     */
    public function findByUsername(string $username): ?string
    {
        $qry = <<<SQL
SELECT
  uuid
FROM userData
WHERE userHandle = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $username) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result($uuid);
        $stmt->fetch();
        $stmt->close();

        return $uuid;
    }

    /**
     * @param string $email
     * @return null|string
     */
    public function findByEmail(string $email): ?string
    {
        $qry = <<<SQL
SELECT
  uuid
FROM userData
WHERE userEmail = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $email) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result($uuid);
        $stmt->fetch();
        $stmt->close();

        return $uuid;
    }
}