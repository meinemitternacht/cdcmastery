<?php


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\AuthHelpers;
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

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        ActivationCollection $activations,
        UserCollection $users
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->activations = $activations;
        $this->users = $users;
    }

    public function do_manual_activation(): Response
    {
        $params = [
            'codes',
        ];

        $this->checkParameters($params);

        $codes = $this->get('codes');

        if (!is_array($codes)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted activation codes were improperly formatted'
            );

            return $this->redirect('/admin/activations');
        }

        $tgt_activations = $this->activations->fetchArray($codes);

        if (!$tgt_activations) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system could not locate any of the submitted activation codes'
            );

            return $this->redirect('/admin/activations');
        }

        $this->activations->removeArray($tgt_activations);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected users were activated successfully'
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
            'activations' => $pending,
            'users' => $this->users->fetchArray($user_uuids),
        ];

        return $this->render(
            'admin/activations/list.html.twig',
            $data
        );
    }
}