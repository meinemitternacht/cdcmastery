<?php


namespace CDCMastery\Models\Auth\Activation;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class ActivationCollection
{
    private mysqli $db;
    private Logger $log;

    public function __construct(mysqli $db, Logger $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    /**
     * @param array $rows
     * @return Activation[]
     */
    private function create_objects(array $rows): array
    {
        $activations = [];
        foreach ($rows as $row) {
            $expires = DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB, $row[ 'timeExpires' ]);

            $activations[ $row[ 'activationCode' ] ] = new Activation($row[ 'activationCode' ],
                                                                      $row[ 'userUUID' ],
                                                                      $expires);
        }

        return $activations;
    }

    public function count(): int
    {
        $qry = <<<SQL
SELECT COUNT(*) AS count FROM queueUnactivatedUsers
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
    }

    public function fetch(string $code): ?Activation
    {
        if (!$code) {
            return null;
        }

        $qry = <<<SQL
SELECT activationCode, userUUID, timeExpires
FROM queueUnactivatedUsers
WHERE activationCode = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return null;
        }

        if (!$stmt->bind_param('s', $code) ||
            !$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result($_code, $user_uuid, $time_expires);
        $stmt->fetch();
        $stmt->close();

        if ($_code === null) {
            return null;
        }

        $row = [
            'activationCode' => $_code,
            'userUUID' => $user_uuid,
            'timeExpires' => $time_expires,
        ];

        return $this->create_objects([$row])[ $code ] ?? null;
    }

    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT activationCode, userUUID, timeExpires
FROM queueUnactivatedUsers
ORDER BY timeExpires DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        if (!$rows) {
            return [];
        }

        $res->free();
        return $this->create_objects($rows);
    }

    public function fetchArray(array $codes): array
    {
        if (!$codes) {
            return [];
        }

        $codes_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $codes));

        $qry = <<<SQL
SELECT activationCode, userUUID, timeExpires
FROM queueUnactivatedUsers
WHERE activationCode IN ('{$codes_str}')
ORDER BY timeExpires DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        if (!$rows) {
            return [];
        }

        $res->free();
        return $this->create_objects($rows);
    }

    public function fetchByUser(User $user): ?Activation
    {
        if (!$user->getUuid()) {
            return null;
        }

        $qry = <<<SQL
SELECT activationCode, userUUID, timeExpires
FROM queueUnactivatedUsers
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return null;
        }

        $user_uuid = $user->getUuid();
        if (!$stmt->bind_param('s', $user_uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result($code, $_user_uuid, $time_expires);
        $stmt->fetch();
        $stmt->close();

        if ($code === null) {
            return null;
        }

        $row = [
            'activationCode' => $code,
            'userUUID' => $_user_uuid,
            'timeExpires' => $time_expires,
        ];

        return $this->create_objects([$row])[ $code ] ?? null;
    }

    public function remove(Activation $activation): void
    {
        $code = $activation->getCode();

        $qry = <<<SQL
DELETE FROM queueUnactivatedUsers
WHERE activationCode = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return;
        }

        if (!$stmt->bind_param('s', $code)) {
            $stmt->close();
            return;
        }

        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Activation[] $activations
     */
    public function removeArray(array $activations): void
    {
        foreach ($activations as $activation) {
            $this->remove($activation);
        }
    }

    public function save(Activation $activation): void
    {
        if (!$activation->getCode()) {
            return;
        }

        $code = $activation->getCode();
        $user_uuid = $activation->getUserUuid();
        $time_expires = $activation->getDateExpires()->format(DateTimeHelpers::DT_FMT_DB);

        $qry = <<<SQL
INSERT INTO queueUnactivatedUsers
    (activationCode, userUUID, timeExpires)
VALUES (?,?,?)
ON DUPLICATE KEY UPDATE 
    activationCode=VALUES(activationCode),
    userUUID=VALUES(userUUID),
    timeExpires=VALUES(timeExpires)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return;
        }

        if (!$stmt->bind_param('sss', $code, $user_uuid, $time_expires)) {
            $stmt->close();
            return;
        }

        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param Activation[] $activations
     */
    public function saveArray(array $activations): void
    {
        foreach ($activations as $activation) {
            $this->save($activation);
        }
    }
}