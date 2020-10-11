<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Bases\BaseHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\UserStats;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\Roles\RoleHelpers;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Users extends Stats
{
    private BaseCollection $bases;
    private RoleCollection $roles;
    private UserStats $users;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        BaseCollection $bases,
        RoleCollection $roles,
        UserStats $user_stats
    ) {
        parent::__construct($logger, $twig, $session);

        $this->bases = $bases;
        $this->roles = $roles;
        $this->users = $user_stats;
    }

    /**
     * @return Response
     */
    public function show_stats_users_home(): Response
    {
        return $this->redirect('/stats/users/bases');
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_users_by_base(): Response
    {
        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Base',
            'counts' => StatisticsHelpers::formatGraphDataUsersBases(
                $this->users->countsByBase(),
                BaseHelpers::listNames($this->bases->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no users in the system');

            return $this->redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function show_users_by_role(): Response
    {
        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Role',
            'counts' => StatisticsHelpers::formatGraphDataUsersBases(
                $this->users->countsByRole(),
                RoleHelpers::listNames($this->roles->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no users in the system');

            return $this->redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }
}
