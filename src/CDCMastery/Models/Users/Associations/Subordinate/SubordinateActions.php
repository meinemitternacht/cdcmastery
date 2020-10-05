<?php
declare(strict_types=1);


namespace CDCMastery\Models\Users\Associations\Subordinate;


use CDCMastery\Controllers\RootController;
use CDCMastery\Helpers\RequestHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserCollection;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class SubordinateActions
{
    private LoggerInterface $log;
    private RoleCollection $roles;
    private UserCollection $users;
    private UserSupervisorAssociations $su_assocs;
    private UserTrainingManagerAssociations $tm_assocs;

    /**
     * SubordinateActions constructor.
     * @param LoggerInterface $log
     * @param RoleCollection $roles
     * @param UserCollection $users
     * @param UserSupervisorAssociations $su_assocs
     * @param UserTrainingManagerAssociations $tm_assocs
     */
    public function __construct(
        LoggerInterface $log,
        RoleCollection $roles,
        UserCollection $users,
        UserSupervisorAssociations $su_assocs,
        UserTrainingManagerAssociations $tm_assocs
    ) {
        $this->log = $log;
        $this->roles = $roles;
        $this->users = $users;
        $this->su_assocs = $su_assocs;
        $this->tm_assocs = $tm_assocs;
    }

    public function do_association_add(
        FlashBagInterface $flash,
        Request $request,
        string $type,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        switch ($type) {
            case Role::TYPE_SUPERVISOR:
                $type_str = 'supervisor';
                $tgt_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);
                $new_parents = RequestHelpers::get($request, 'new_super');
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $type_str = 'training manager';
                $tgt_role = $this->roles->fetchType(Role::TYPE_TRAINING_MANAGER);
                $new_parents = RequestHelpers::get($request, 'new_tm');
                break;
            default:
                throw new RuntimeException("invalid association type: {$type}");
        }

        if (!is_array($new_parents)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_parents = $this->users->fetchArray($new_parents);

        if (!$tgt_parents) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_parents = array_filter($tgt_parents, static function (User $v) use ($tgt_role): bool {
            return $v->getRole() === $tgt_role->getUuid();
        });

        if (!$tgt_parents) {
            $flash->add(
                MessageTypes::ERROR,
                "None of the selected users were in the {$type_str} group"
            );

            return RootController::static_redirect($url_failure);
        }

        $parents_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_parents));
        $this->log->info("add {$type_str} assocs :: {$user->getName()} [{$user->getUuid()}] :: {$parents_str} :: user {$initiator->getUuid()}");

        switch ($type) {
            case Role::TYPE_SUPERVISOR:
                $this->su_assocs->batchAddSupervisorsForUser($tgt_parents, $user);
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $this->tm_assocs->batchAddTrainingManagersForUser($tgt_parents, $user);
                break;
        }

        $flash->add(
            MessageTypes::SUCCESS,
            "The selected {$type_str} associations were successfully added"
        );

        return RootController::static_redirect($url_success);
    }

    public function do_association_remove(
        FlashBagInterface $flash,
        Request $request,
        string $type,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        switch ($type) {
            case Role::TYPE_SUPERVISOR:
                $type_str = 'supervisor';
                $tgt_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);
                $del_parents = RequestHelpers::get($request, 'del_super');
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $type_str = 'training manager';
                $tgt_role = $this->roles->fetchType(Role::TYPE_TRAINING_MANAGER);
                $del_parents = RequestHelpers::get($request, 'del_tm');
                break;
            default:
                throw new RuntimeException("invalid association type: {$type}");
        }

        if (!is_array($del_parents)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_parents = $this->users->fetchArray($del_parents);

        if (!$tgt_parents) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_parents = array_filter($tgt_parents, static function (User $v) use ($tgt_role): bool {
            return $v->getRole() === $tgt_role->getUuid();
        });

        if (!$tgt_parents) {
            $flash->add(
                MessageTypes::ERROR,
                "None of the selected users were in the {$type_str} group"
            );

            return RootController::static_redirect($url_failure);
        }

        $parents_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_parents));
        $this->log->info("delete {$type_str} assocs :: {$user->getName()} [{$user->getUuid()}] :: {$parents_str} :: user {$initiator->getUuid()}");

        switch ($type) {
            case Role::TYPE_SUPERVISOR:
                foreach ($tgt_parents as $tgt_parent) {
                    $this->su_assocs->remove($user, $tgt_parent);
                }
                break;
            case Role::TYPE_TRAINING_MANAGER:
                foreach ($tgt_parents as $tgt_parent) {
                    $this->tm_assocs->remove($user, $tgt_parent);
                }
                break;
        }

        $flash->add(
            MessageTypes::SUCCESS,
            "The selected {$type_str} associations were successfully removed"
        );

        return RootController::static_redirect($url_success);
    }

    public function do_subordinates_add(
        FlashBagInterface $flash,
        Request $request,
        Role $tgt_role,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $new_users = RequestHelpers::get($request, 'new_users');

        if (!is_array($new_users)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $new_users = array_filter($new_users, static function (string $v) use ($user): bool {
            return $v !== $user->getUuid();
        });

        $tgt_subs = $this->users->fetchArray($new_users);

        if (!$tgt_subs) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_sub_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_subs));
        switch ($tgt_role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $this->tm_assocs->batchAddUsersForTrainingManager($tgt_subs, $user);
                $this->log->info("add training manager subordinates :: {$user->getName()} [{$user->getUuid()}] :: {$tgt_sub_str} :: user {$initiator->getUuid()}");
                break;
            case Role::TYPE_SUPERVISOR:
                $this->su_assocs->batchAddUsersForSupervisor($tgt_subs, $user);
                $this->log->info("add supervisor subordinates :: {$user->getName()} [{$user->getUuid()}] :: {$tgt_sub_str} :: user {$initiator->getUuid()}");
                break;
        }

        $flash->add(
            MessageTypes::SUCCESS,
            'The selected subordinates were successfully added'
        );

        return RootController::static_redirect($url_success);
    }

    public function do_subordinates_remove(
        FlashBagInterface $flash,
        Request $request,
        Role $role,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $del_users = RequestHelpers::get($request, 'del_users');

        if (!is_array($del_users)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_subs = $this->users->fetchArray($del_users);

        if (!$tgt_subs) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_sub_str = implode(', ', array_map(static function (User $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_subs));
        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                foreach ($tgt_subs as $del_user) {
                    $this->tm_assocs->remove($del_user, $user);
                }
                $this->log->info("remove training manager subordinates :: {$user->getName()} [{$user->getUuid()}] :: {$tgt_sub_str} :: user {$initiator->getUuid()}");
                break;
            case Role::TYPE_SUPERVISOR:
                foreach ($tgt_subs as $del_user) {
                    $this->su_assocs->remove($del_user, $user);
                }
                $this->log->info("remove supervisor subordinates :: {$user->getName()} [{$user->getUuid()}] :: {$tgt_sub_str} :: user {$initiator->getUuid()}");
                break;
        }

        $flash->add(
            MessageTypes::SUCCESS,
            'The selected subordinates were successfully removed'
        );

        return RootController::static_redirect($url_success);
    }
}