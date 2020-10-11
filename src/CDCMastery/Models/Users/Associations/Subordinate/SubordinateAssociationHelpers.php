<?php
declare(strict_types=1);


namespace CDCMastery\Models\Users\Associations\Subordinate;


use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserCollection;
use Psr\Log\LoggerInterface;

class SubordinateAssociationHelpers
{
    public static function handle_role_change(
        LoggerInterface $log,
        UserCollection $users,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs,
        Role $prev_role,
        Role $new_role,
        User $user
    ): void {
        if ($prev_role->getType() === $new_role->getType()) {
            return;
        }

        switch ($prev_role->getType()) {
            case Role::TYPE_USER:
                /* no associations to modify */
                return;
            case Role::TYPE_SUPERVISOR:
                switch ($new_role->getType()) {
                    case Role::TYPE_USER:
                        /* supervisor -> user */
                        $su_assocs->removeAllBySupervisor($user);
                        break;
                    case Role::TYPE_TRAINING_MANAGER:
                        /* supervisor -> training manager */
                        $assocs = $su_assocs->fetchAllBySupervisor($user);

                        if (!$assocs) {
                            return;
                        }

                        $assoc_users = $users->fetchArray($assocs);

                        if (!$assoc_users) {
                            return;
                        }

                        $tm_assocs->batchAddUsersForTrainingManager($assoc_users, $user);
                        $su_assocs->removeAllBySupervisor($user);
                        $log->info("migrate associations :: supervisor -> training manager :: {$user->getName()} [{$user->getUuid()}]");
                        break;
                }
                break;
            case Role::TYPE_TRAINING_MANAGER:
                switch ($new_role->getType()) {
                    case Role::TYPE_USER:
                        /* training manager -> user */
                        $tm_assocs->removeAllByTrainingManager($user);
                        break;
                    case Role::TYPE_SUPERVISOR:
                        /* training manager -> supervisor */
                        $assocs = $tm_assocs->fetchAllByTrainingManager($user);

                        if (!$assocs) {
                            return;
                        }

                        $assoc_users = $users->fetchArray($assocs);

                        if (!$assoc_users) {
                            return;
                        }

                        $su_assocs->batchAddUsersForSupervisor($assoc_users, $user);
                        $tm_assocs->removeAllByTrainingManager($user);
                        $log->info("migrate associations :: supervisor -> training manager :: {$user->getName()} [{$user->getUuid()}]");
                        break;
                }
                break;
        }
    }
}
