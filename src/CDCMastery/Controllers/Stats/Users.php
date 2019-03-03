<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers\Stats;


use CDCMastery\Controllers\Stats;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Bases\BaseHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\RoleHelpers;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class Users extends Stats
{
    /**
     * @var BaseCollection
     */
    private $baseCollection;

    /**
     * @var RoleCollection
     */
    private $roleCollection;

    /**
     * @var \CDCMastery\Models\Statistics\Users
     */
    private $users;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        BaseCollection $baseCollection,
        RoleCollection $roleCollection,
        \CDCMastery\Models\Statistics\Users $users
    ) {
        parent::__construct($logger, $twig);

        $this->baseCollection = $baseCollection;
        $this->roleCollection = $roleCollection;
        $this->users = $users;
    }

    /**
     * @return Response
     */
    public function renderUsersStatsHome(): Response
    {
        return AppHelpers::redirect('/stats/users/bases');
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderUsersByBase(): Response
    {
        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Base',
            'counts' => StatisticsHelpers::formatGraphDataUsers(
                $this->users->countsByBase(),
                BaseHelpers::listNamesKeyed($this->baseCollection->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            Messages::add(
                Messages::INFO,
                "There are no users in the system"
            );

            return AppHelpers::redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderUsersByRole(): Response
    {
        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Role',
            'counts' => StatisticsHelpers::formatGraphDataUsers(
                $this->users->countsByRole(),
                RoleHelpers::listNamesKeyed($this->roleCollection->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            Messages::add(
                Messages::INFO,
                "There are no users in the system"
            );

            return AppHelpers::redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }
}