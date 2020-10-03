<?php


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Activations extends Admin
{
    private ActivationCollection $activations;
    private UserCollection $users;

    /**
     * Activations constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param ActivationCollection $activations
     * @param UserCollection $users
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        ActivationCollection $activations,
        UserCollection $users
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->activations = $activations;
        $this->users = $users;
    }

    public function do_manual_activation(): Response
    {
        $params = [
            'codes',
            'determination',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect('/admin/activations');
        }

        $codes = $this->get('codes');

        if (!is_array($codes)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted activation codes were improperly formatted'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/activations');
        }

        $tgt_activations = $this->activations->fetchArray($codes);
        $determination = $this->filter_string_default('determination');

        if (!$tgt_activations) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system could not locate any of the submitted activation codes'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/activations');
        }

        switch ($determination) {
            case 'approve':
                foreach ($tgt_activations as $tgt_activation) {
                    $this->log->notice("manually activate user :: {$tgt_activation->getUserUuid()}");
                }
                break;
            case 'reject':
            default:
                $users = $this->users->fetchArray(array_map(static function (Activation $v): string {
                    return $v->getUserUuid();
                }, $tgt_activations));

                foreach ($users as $user) {
                    $user->setDisabled(true);
                    $this->log->alert("admin disable user :: {$user->getName()} :: {$user->getUuid()}");
                }

                $this->users->saveArray($users);
                break;
        }

        $this->activations->removeArray($tgt_activations);
        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected users were processed successfully'
        );

        return $this->redirect('/admin/activations');
    }

    public function show_home(): Response
    {
        $pending = $this->activations->fetchAll();

        if (!$pending) {
            $this->flash()->add(
                MessageTypes::INFO,
                'There are no unactivated users to view'
            );

            return $this->redirect('/admin/users');
        }

        uasort($pending, static function (Activation $a, Activation $b): int {
            return $a->getDateExpires() <=> $b->getDateExpires();
        });

        $user_uuids = array_map(static function (Activation $v): string {
            return $v->getUserUuid();
        }, $pending);

        $data = [
            'pending' => $pending,
            'users' => $this->users->fetchArray($user_uuids),
        ];

        return $this->render(
            'admin/activations/list.html.twig',
            $data
        );
    }
}