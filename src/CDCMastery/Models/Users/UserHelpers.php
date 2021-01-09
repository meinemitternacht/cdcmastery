<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/5/2017
 * Time: 8:46 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\FlashCards\CategoryCollection;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\Associations\Subordinate\UserSupervisorAssociations;
use CDCMastery\Models\Users\Associations\Subordinate\UserTrainingManagerAssociations;
use CDCMastery\Models\Users\Roles\Role;
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

        $civilian = [
            'CTR' => 'Contractor',
            'CIV' => 'Civilian (other)',
        ];

        $special = [
            'SSgt (Ret.)' => 'Staff Sergeant (Retired)',
        ];

        if (!$keyed) {
            return $show_special
                ? array_merge($enlisted, $officer, $civilian, $special)
                : array_merge($enlisted, $officer, $civilian);
        }

        if (!$show_special) {
            return [
                'Enlisted' => $enlisted,
                'Officer' => $officer,
                'Civilian' => $civilian,
            ];
        }

        return [
            'Enlisted' => $enlisted,
            'Officer' => $officer,
            'Civilian' => $civilian,
            'Special' => $special,
        ];
    }

    public static function profile_common(
        User $user,
        ?Base $base,
        Role $role,
        UserCollection $users,
        AfscCollection $afscs,
        OfficeSymbolCollection $symbols,
        CategoryCollection $categories,
        UserAfscAssociations $afsc_assocs,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs,
        TestStats $test_stats
    ): array {
        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $symbols->fetch($u_symbol);
        }

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $u_afscs = $afscs->fetchAll(AfscCollection::SHOW_ALL);
        $u_afsc_assocs = $afsc_assocs->fetchAllByUser($user);
        $u_tm_assocs = $users->fetchArray($tm_assocs->fetchAllByUser($user), $user_sort);
        $u_su_assocs = $users->fetchArray($su_assocs->fetchAllByUser($user), $user_sort);
        $fc_cats = $categories->fetchAllByUser($user);

        $subs = null;
        switch ($role->getType()) {
            case Role::TYPE_SUPERVISOR:
                $subs = $users->fetchArray($su_assocs->fetchAllBySupervisor($user), $user_sort);
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $users->fetchArray($tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                break;
        }

        return [
            'user' => $user,
            'base' => $base,
            'symbol' => $symbol ?? null,
            'role' => $role,
            'afscs' => [
                'authorized' => array_intersect_key($u_afscs, array_flip($u_afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($u_afscs, array_flip($u_afsc_assocs->getPending())),
            ],
            'assocs' => [
                'tm' => $u_tm_assocs,
                'su' => $u_su_assocs,
                'subordinates' => $subs,
                'flash_cards' => [
                    'categories' => $fc_cats,
                ],
            ],
            'stats' => [
                'tests' => [
                    'complete' => [
                        'count' => $test_stats->userCountOverall($user),
                        'avg' => $test_stats->userAverageOverall($user),
                    ],
                    'incomplete' => [
                        'count' => $test_stats->userCountIncompleteOverall($user),
                    ],
                    'practice' => [
                        'count' => $test_stats->userCountPracticeOverall($user),
                    ],
                ],
            ],
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
