<?php
declare(strict_types=1);


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\Roles\PendingRole;
use CDCMastery\Models\Users\Roles\PendingRoleCollection;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\Associations\Subordinate\SubordinateAssociationHelpers;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\Associations\Subordinate\UserSupervisorAssociations;
use CDCMastery\Models\Users\Associations\Subordinate\UserTrainingManagerAssociations;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class RoleApprovals extends Admin
{
    private UserCollection $users;
    private RoleCollection $roles;
    private PendingRoleCollection $pending_roles;
    private UserTrainingManagerAssociations $tm_assocs;
    private UserSupervisorAssociations $super_assocs;

    /**
     * RoleApprovals constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param UserCollection $users
     * @param RoleCollection $roles
     * @param PendingRoleCollection $pending_roles
     * @param UserTrainingManagerAssociations $tm_assocs
     * @param UserSupervisorAssociations $super_assocs
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        UserCollection $users,
        RoleCollection $roles,
        PendingRoleCollection $pending_roles,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $super_assocs
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->users = $users;
        $this->roles = $roles;
        $this->pending_roles = $pending_roles;
        $this->tm_assocs = $tm_assocs;
        $this->super_assocs = $super_assocs;
    }

    public function do_approve_roles(): Response
    {
        $params = [
            'user_uuids',
            'determination',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect('/admin/pending-roles');
        }

        $user_uuids = $this->get('user_uuids');
        $determination = $this->filter_string_default('determination');

        if (!is_array($user_uuids) || !$user_uuids) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided list of role approvals was improperly formatted'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/pending-roles');
        }

        $user_uuids_r = array_flip($user_uuids);

        $tgt_pending = array_filter($this->pending_roles->fetchAll(),
            static function (PendingRole $v) use ($user_uuids_r): bool {
                return isset($user_uuids_r[ $v->getUserUuid() ]);
            });

        if (!$tgt_pending) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'None of the provided role approvals could be found in the system'
            );

            return $this->redirect('/admin/pending-roles');
        }

        switch ($determination) {
            case 'approve':
                $requests_approved = true;
                break;
            case 'reject':
            default:
                $requests_approved = false;
                break;
        }

        $success = [];
        $tgt_users = $this->users->fetchArray($user_uuids);
        $all_roles = $this->roles->fetchAll();
        foreach ($tgt_pending as $role) {
            if (!isset($tgt_users[ $role->getUserUuid() ], $all_roles[ $role->getRoleUuid() ])) {
                continue;
            }

            $tgt_user = $tgt_users[ $role->getUserUuid() ];

            if ($tgt_user->getRole() === $role->getRoleUuid()) {
                continue;
            }

            $prev_role = $all_roles[ $tgt_user->getRole() ] ?? null;
            $new_role = $all_roles[ $role->getRoleUuid() ];

            if ($prev_role === null) {
                continue;
            }

            if (!$requests_approved) {
                goto out_continue;
            }

            $tgt_user->setRole($role->getRoleUuid());

            SubordinateAssociationHelpers::handle_role_change($this->log,
                                                              $this->users,
                                                              $this->tm_assocs,
                                                              $this->super_assocs,
                                                              $prev_role,
                                                              $new_role,
                                                              $tgt_user);

            out_continue:
            $this->log->notice("{$tgt_user->getName()} :: role change " .
                               ($requests_approved
                                   ? 'approved'
                                   : 'denied') .
                               " :: {$prev_role->getName()} -> {$new_role->getName()}");
            $success[] = $role;
        }

        $this->users->saveArray($tgt_users);
        $this->pending_roles->removeArray($success);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The requested roles were processed successfully'
        );

        if (!$this->pending_roles->count()) {
            return $this->redirect('/admin/users');
        }

        return $this->redirect('/admin/pending-roles');
    }

    public function show_home(): Response
    {
        $pending = $this->pending_roles->fetchAll();

        if (!$pending) {
            $this->flash()->add(
                MessageTypes::INFO,
                'There are no pending role requests to approve'
            );

            return $this->redirect('/admin/users');
        }

        $user_uuids = array_map(
            static function (PendingRole $v): string {
                return $v->getUserUuid();
            },
            $pending
        );

        $data = [
            'pending' => $pending,
            'roles' => $this->roles->fetchAll(),
            'users' => $this->users->fetchArray($user_uuids),
        ];

        return $this->render(
            'admin/role-approvals/list.html.twig',
            $data
        );
    }
}
