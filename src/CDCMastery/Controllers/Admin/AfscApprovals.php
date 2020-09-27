<?php


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserAfscCollection;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class AfscApprovals extends Admin
{
    private UserAfscAssociations $assocs;
    private UserCollection $users;
    private AfscCollection $afscs;

    /**
     * AfscApprovals constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param UserAfscAssociations $assocs
     * @param UserCollection $users
     * @param AfscCollection $afscs
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        UserAfscAssociations $assocs,
        UserCollection $users,
        AfscCollection $afscs
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->assocs = $assocs;
        $this->users = $users;
        $this->afscs = $afscs;
    }

    public function do_approve_assocs(): Response
    {
        $params = [
            'user_afscs',
            'determination',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/pending-afscs");
        }

        $user_afscs = $this->get('user_afscs');
        $determination = $this->filter_string_default('determination');

        if (!is_array($user_afscs)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/pending-afscs");
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

        $tgt_user_afscs = [];
        $tgt_afscs = [];
        foreach ($user_afscs as $user_afsc) {
            if (!str_contains($user_afsc, '_')) {
                continue;
            }

            [$user_uuid, $afsc_uuid] = explode('_', $user_afsc);

            if (!isset($tgt_user_afscs[ $user_uuid ])) {
                $tgt_user_afscs[ $user_uuid ] = [];
            }

            $tgt_user_afscs[ $user_uuid ][] = $afsc_uuid;
            $tgt_afscs[] = $afsc_uuid;
        }

        if (!$tgt_user_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/pending-afscs");
        }

        $tgt_users = $this->users->fetchArray(array_keys($tgt_user_afscs));
        $tgt_afscs = $this->afscs->fetchArray($tgt_afscs);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/pending-afscs");
        }

        foreach ($tgt_user_afscs as $user_uuid => $user_afscs) {
            foreach ($user_afscs as $user_afsc) {
                if ($requests_approved) {
                    $this->log->notice("admin approve afsc :: {$tgt_users[$user_uuid]->getName()} :: {$tgt_afscs[$user_afsc]->getName()}");
                    $this->assocs->authorize($tgt_users[ $user_uuid ], $tgt_afscs[ $user_afsc ]);
                    continue;
                }

                $this->log->notice("admin deny afsc :: {$tgt_users[$user_uuid]->getName()} :: {$tgt_afscs[$user_afsc]->getName()}");
                $this->assocs->remove($tgt_users[ $user_uuid ], $tgt_afscs[ $user_afsc ]);
            }
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully processed'
        );

        if (!$this->assocs->countPending()) {
            return $this->redirect("/admin/users");
        }

        return $this->redirect("/admin/pending-afscs");
    }

    public function show_home(): Response
    {
        $pending = $this->assocs->fetchAllPending();

        if (!$pending) {
            $this->flash()->add(
                MessageTypes::INFO,
                'There are no pending AFSC requests to process'
            );

            return $this->redirect('/admin/users');
        }

        $user_uuids = array_map(static function (UserAfscCollection $v): string {
            return $v->getUser();
        }, $pending);

        $afsc_uuids = array_map(static function (UserAfscCollection $v): array {
            return $v->getAfscs();
        }, $pending);

        if ($afsc_uuids) {
            $afsc_uuids = array_merge(...array_values($afsc_uuids));
        }

        $tgt_users = $this->users->fetchArray($user_uuids);
        $tgt_afscs = $this->afscs->fetchArray($afsc_uuids);

        $data = [
            'pending' => $pending,
            'users' => $tgt_users,
            'afscs' => $tgt_afscs,
        ];

        return $this->render(
            'admin/afsc-approvals/list.html.twig',
            $data
        );
    }
}