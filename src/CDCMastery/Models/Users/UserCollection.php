<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:07 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Helpers\DateTimeHelpers;
use Monolog\Logger;

class UserCollection
{
    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var User[]
     */
    private $users = [];

    /**
     * UserCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $uuid
     * @return User
     */
    public function fetch(string $uuid): User
    {
        if (empty($uuid)) {
            return new User();
        }
        
        $qry = <<<SQL
SELECT
  uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return new User();
        }
        
        $stmt->bind_result(
            $_uuid,
            $firstName,
            $lastName,
            $handle,
            $password,
            $legacyPassword,
            $email,
            $rank,
            $dateRegistered,
            $lastLogin,
            $lastActive,
            $timeZone,
            $role,
            $officeSymbol,
            $base,
            $disabled,
            $reminderSent
        );
        
        $stmt->fetch();
        $stmt->close();
        
        $user = new User();
        $user->setUuid($_uuid);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setHandle($handle);
        $user->setPassword($password);
        $user->setLegacyPassword($legacyPassword);
        $user->setEmail($email);
        $user->setRank($rank);
        $user->setDateRegistered(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $dateRegistered
            )
        );
        $user->setLastLogin(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $lastLogin
            )
        );
        $user->setLastActive(
            \DateTime::createFromFormat(
                DateTimeHelpers::FMT_DATABASE,
                $lastActive
            )
        );
        $user->setTimeZone($timeZone);
        $user->setRole($role);
        $user->setOfficeSymbol($officeSymbol);
        $user->setBase($base);
        $user->setDisabled($disabled);
        $user->setReminderSent($reminderSent);
        
        $this->users[$uuid] = $user;
        
        return $user;
    }

    /**
     * @return User[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }
            
            $user = new User();
            $user->setUuid($row['uuid'] ?? '');
            $user->setFirstName($row['userFirstName'] ?? '');
            $user->setLastName($row['userLastName'] ?? '');
            $user->setHandle($row['userHandle'] ?? '');
            $user->setPassword($row['userPassword'] ?? '');
            $user->setLegacyPassword($row['userLegacyPassword'] ?? '');
            $user->setEmail($row['userEmail'] ?? '');
            $user->setRank($row['userRank'] ?? '');
            $user->setDateRegistered(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userDateRegistered'] ?? ''
                )
            );
            $user->setLastLogin(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userLastLogin'] ?? ''
                )
            );
            $user->setLastActive(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userLastActive'] ?? ''
                )
            );
            $user->setTimeZone($row['userTimeZone'] ?? '');
            $user->setRole($row['userRole'] ?? '');
            $user->setOfficeSymbol($row['userOfficeSymbol'] ?? '');
            $user->setBase($row['userBase'] ?? '');
            $user->setDisabled((bool)($row['userDisabled'] ?? false));
            $user->setReminderSent((bool)($row['reminderSent'] ?? false));
            
            $this->users[$row['uuid']] = $user;
        }

        $res->free();
        
        return $this->users;
    }

    /**
     * @param string[] $uuidList
     * @return User[]
     */
    public function fetchArray(array $uuidList): array
    {
        if (empty($uuidList)) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);
        
        $qry = <<<SQL
SELECT
  uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);
        
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $user = new User();
            $user->setUuid($row['uuid'] ?? '');
            $user->setFirstName($row['userFirstName'] ?? '');
            $user->setLastName($row['userLastName'] ?? '');
            $user->setHandle($row['userHandle'] ?? '');
            $user->setPassword($row['userPassword'] ?? '');
            $user->setLegacyPassword($row['userLegacyPassword'] ?? '');
            $user->setEmail($row['userEmail'] ?? '');
            $user->setRank($row['userRank'] ?? '');
            $user->setDateRegistered(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userDateRegistered'] ?? ''
                )
            );
            $user->setLastLogin(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userLastLogin'] ?? ''
                )
            );
            $user->setLastActive(
                \DateTime::createFromFormat(
                    DateTimeHelpers::FMT_DATABASE,
                    $row['userLastActive'] ?? ''
                )
            );
            $user->setTimeZone($row['userTimeZone'] ?? '');
            $user->setRole($row['userRole'] ?? '');
            $user->setOfficeSymbol($row['userOfficeSymbol'] ?? '');
            $user->setBase($row['userBase'] ?? '');
            $user->setDisabled((bool)($row['userDisabled'] ?? false));
            $user->setReminderSent((bool)($row['reminderSent'] ?? false));

            $this->users[$row['uuid']] = $user;
        }

        $res->free();

        return array_intersect_key(
            $this->users,
            array_flip($uuidList)
        );
    }

    /**
     * @return UserCollection
     */
    public function reset(): self
    {
        $this->users = [];
        
        return $this;
    }

    /**
     * @param User $user
     */
    public function save(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }
        
        $uuid = $user->getUuid();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $handle = $user->getHandle();
        $password = $user->getPassword();
        $legacyPassword = $user->getLegacyPassword();
        $email = $user->getEmail();
        $rank = $user->getRank();
        $dateRegistered = $user->getDateRegistered()->format(
            DateTimeHelpers::FMT_DATABASE
        );
        $lastLogin = $user->getLastLogin()->format(
            DateTimeHelpers::FMT_DATABASE
        );
        $lastActive = $user->getLastActive()->format(
            DateTimeHelpers::FMT_DATABASE
        );
        $timeZone = $user->getTimeZone();
        $role = $user->getRole();
        $officeSymbol = $user->getOfficeSymbol();
        $base = $user->getBase();
        $disabled = (int)$user->isDisabled();
        $reminderSent = (int)$user->isReminderSent();
        
        $qry = <<<SQL
INSERT INTO userData
  (
    uuid, 
    userFirstName, 
    userLastName, 
    userHandle, 
    userPassword, 
    userLegacyPassword, 
    userEmail, 
    userRank, 
    userDateRegistered, 
    userLastLogin, 
    userLastActive, 
    userTimeZone, 
    userRole, 
    userOfficeSymbol, 
    userBase, 
    userDisabled,
    reminderSent
  )
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  userFirstName=VALUES(userFirstName),
  userLastName=VALUES(userLastName),
  userHandle=VALUES(userHandle),
  userPassword=VALUES(userPassword),
  userLegacyPassword=VALUES(userLegacyPassword),
  userEmail=VALUES(userEmail),
  userRank=VALUES(userRank),
  userDateRegistered=VALUES(userDateRegistered),
  userLastLogin=VALUES(userLastLogin),
  userLastActive=VALUES(userLastActive),
  userTimeZone=VALUES(userTimeZone),
  userRole=VALUES(userRole),
  userOfficeSymbol=VALUES(userOfficeSymbol),
  userBase=VALUES(userBase),
  userDisabled=VALUES(userDisabled),
  reminderSent=VALUES(reminderSent)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssssssssssssssii',
            $uuid,
            $firstName,
            $lastName,
            $handle,
            $password,
            $legacyPassword,
            $email,
            $rank,
            $dateRegistered,
            $lastLogin,
            $lastActive,
            $timeZone,
            $role,
            $officeSymbol,
            $base,
            $disabled,
            $reminderSent
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->users[$uuid] = $user;
    }

    /**
     * @param User[] $users
     */
    public function saveArray(array $users): void
    {
        if (empty($users)) {
            return;
        }

        $c = count($users);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($users[$i])) {
                continue;
            }

            if (!$users[$i] instanceof User) {
                continue;
            }

            $this->save($users[$i]);
        }
    }
}