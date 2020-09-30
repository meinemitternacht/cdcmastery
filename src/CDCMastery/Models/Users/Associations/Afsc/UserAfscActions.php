<?php


namespace CDCMastery\Models\Users\Associations\Afsc;


use CDCMastery\Controllers\RootController;
use CDCMastery\Helpers\RequestHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class UserAfscActions
{
    private LoggerInterface $log;
    private AfscCollection $afscs;
    private UserAfscAssociations $afsc_assocs;

    /**
     * UserAfscActions constructor.
     * @param LoggerInterface $log
     * @param AfscCollection $afscs
     * @param UserAfscAssociations $afsc_assocs
     */
    public function __construct(LoggerInterface $log, AfscCollection $afscs, UserAfscAssociations $afsc_assocs)
    {
        $this->log = $log;
        $this->afscs = $afscs;
        $this->afsc_assocs = $afsc_assocs;
    }

    public function do_afsc_association_add(
        FlashBagInterface $flash,
        Request $request,
        User $user,
        User $initiator,
        bool $override,
        string $url_success,
        string $url_failure
    ): Response {
        $new_afsc = RequestHelpers::get($request, 'new_afsc');

        if (!is_array($new_afsc)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_afscs = $this->afscs->fetchArray($new_afsc);

        if (!$tgt_afscs) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_afscs_fouo = array_filter($tgt_afscs, static function (Afsc $v): bool {
            return $v->isFouo();
        });
        $tgt_afscs_non_fouo = array_filter($tgt_afscs, static function (Afsc $v): bool {
            return !$v->isFouo();
        });

        if ($override) {
            $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
                return "{$v->getName()} [{$v->getUuid()}]";
            }, $tgt_afscs));
            $this->log->info("add afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$initiator->getUuid()}");

            $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs, true);

            $flash->add(
                MessageTypes::SUCCESS,
                'The selected AFSC associations were successfully added'
            );
            goto out_return;
        }

        if ($tgt_afscs_fouo) {
            $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
                return "{$v->getName()} [{$v->getUuid()}]";
            }, $tgt_afscs_fouo));

            $msg = "add pending afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$initiator->getUuid()}";

            $this->log->alert($msg);
            $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs_fouo, false);
        }

        if ($tgt_afscs_non_fouo) {
            $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
                return "{$v->getName()} [{$v->getUuid()}]";
            }, $tgt_afscs_non_fouo));
            $this->log->info("add afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$initiator->getUuid()}");

            $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs_non_fouo, true);
        }

        $flash->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully added. ' .
            'FOUO AFSC associations may take up to 24 hours to be approved.'
        );

        out_return:
        return RootController::static_redirect($url_success);
    }

    public function do_afsc_association_approve(
        FlashBagInterface $flash,
        Request $request,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $approve_afsc = RequestHelpers::get($request, 'approve_afsc');

        if (!is_array($approve_afsc)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_afscs = $this->afscs->fetchArray($approve_afsc);

        if (!$tgt_afscs) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_afscs));
        $this->log->info("approve afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$initiator->getUuid()}");

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->authorize($user, $tgt_afsc);
        }

        $flash->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully approved'
        );

        return RootController::static_redirect($url_success);
    }

    public function do_afsc_association_remove(
        FlashBagInterface $flash,
        Request $request,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $del_afsc = RequestHelpers::get($request, 'del_afsc');

        if (!is_array($del_afsc)) {
            $flash->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return RootController::static_redirect($url_failure);
        }

        $tgt_afscs = $this->afscs->fetchArray($del_afsc);

        if (!$tgt_afscs) {
            $flash->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return RootController::static_redirect($url_failure);
        }

        $afscs_str = implode(', ', array_map(static function (Afsc $v): string {
            return "{$v->getName()} [{$v->getUuid()}]";
        }, $tgt_afscs));
        $this->log->info("delete afsc assocs :: {$user->getName()} [{$user->getUuid()}] :: {$afscs_str} :: user {$initiator->getUuid()}");

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->remove($user, $tgt_afsc);
        }

        $flash->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully removed'
        );

        return RootController::static_redirect($url_success);
    }
}