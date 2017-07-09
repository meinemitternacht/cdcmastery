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

class Users extends Stats
{
    /**
     * @return string
     */
    public function renderUsersStatsHome(): string
    {
        return AppHelpers::redirect('/stats/users/bases');
    }

    /**
     * @return string
     */
    public function renderUsersByBase(): string
    {
        $statsUsers = $this->container->get(\CDCMastery\Models\Statistics\Users::class);

        $baseCollection = $this->container->get(BaseCollection::class);

        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Base',
            'counts' => StatisticsHelpers::formatGraphDataUsers(
                $statsUsers->countsByBase(),
                BaseHelpers::listNamesKeyed($baseCollection->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            Messages::add(
                Messages::INFO,
                "There are no users in the system"
            );

            AppHelpers::redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    public function renderUsersByRole(): string
    {
        $statsUsers = $this->container->get(\CDCMastery\Models\Statistics\Users::class);

        $roleCollection = $this->container->get(RoleCollection::class);

        $data = [
            'title' => 'All Users',
            'subTitle' => 'Users by Role',
            'counts' => StatisticsHelpers::formatGraphDataUsers(
                $statsUsers->countsByRole(),
                RoleHelpers::listNamesKeyed($roleCollection->fetchAll())
            )
        ];

        if (empty($data['counts'])) {
            Messages::add(
                Messages::INFO,
                "There are no users in the system"
            );

            AppHelpers::redirect('/stats');
        }

        return $this->render(
            'public/stats/users.html.twig',
            $data
        );
    }
}