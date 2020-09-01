<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\Bases\Bases as BaseStats;
use CDCMastery\Models\Users\UserCollection;
use DateTime;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Bases extends Admin
{
    /**
     * @var UserCollection
     */
    private $users;

    /**
     * @var BaseCollection
     */
    private $bases;

    /**
     * @var BaseStats
     */
    private $stats;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        BaseStats $base_stats
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->users = $users;
        $this->bases = $bases;
        $this->stats = $base_stats;
    }

    private function get_base(string $uuid): ?Base
    {
        $base = $this->bases->fetch($uuid);

        if ($base === null || $base->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified Base does not exist');

            $this->redirect('/admin/bases')->send();
            exit;
        }

        return $base;
    }

    public function do_add(?Base $base = null): Response
    {
        $edit = $base !== null;

        $params = [
            'name',
        ];

        if (!$this->checkParameters($params)) {
            goto out_return;
        }

        $name = $this->filter_string_default('name');

        if (!$edit) {
            $base = new Base();
            $base->setUuid(UUID::generate());
        }

        $base->setName($name);

        $db_bases = $this->bases->fetchAll();
        foreach ($db_bases as $db_base) {
            if ($edit && $db_base->getUuid() === $base->getUuid()) {
                continue;
            }

            if ($db_base->getName() === $name) {
                $this->flash()->add(MessageTypes::ERROR,
                                    "The specified Base '{$base->getName()}' already exists in the database");
                goto out_return;
            }
        }

        $this->bases->save($base);

        $this->flash()->add(MessageTypes::SUCCESS,
                            $edit
                                ? "The specified Base '{$base->getName()}' was modified successfully"
                                : "The specified Base '{$base->getName()}' was added successfully");

        out_return:
        return $this->redirect('/admin/bases');
    }

    public function do_edit(string $uuid): Response
    {
        return $this->do_add($this->get_base($uuid));
    }

    public function show_home(): Response
    {
        $bases = $this->bases->fetchAll();

        usort(
            $bases,
            static function (Base $a, Base $b): int {
                return $a->getName() <=> $b->getName();
            }
        );

        $data = [
            'bases' => $bases,
        ];

        return $this->render(
            'admin/bases/list.html.twig',
            $data
        );
    }

    public function show_edit(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $data = [
            'base' => $base,
        ];

        return $this->render(
            "admin/bases/edit.html.twig",
            $data
        );
    }

    private function format_overview_graph_data(array &$stats, int $limit = -1): ?array
    {
        /*
         *  [
         *    user_uuid => [
         *      tAvg => test average (float),
         *      tCount => test count (int)
         *    ]
         *  ]
         */

        $users = $this->users->fetchArray(array_keys($stats));

        $average_data = [];
        $count_data = [];
        $i = 0;
        foreach ($stats as $user_uuid => $stat) {
            if (!isset($users[ $user_uuid ])) {
                continue;
            }

            $name = $users[ $user_uuid ]->getName();
            $ll = $users[ $user_uuid ]->getLastLogin();
            $stats[ $user_uuid ][ 'name' ] = $name;
            $stats[ $user_uuid ][ 'last_login' ] = $ll
                ? $ll->format(DateTimeHelpers::DT_FMT_SHORT)
                : 'Never';

            if (++$i <= $limit && $limit !== -1) {
                $average_data[] = [
                    'toolTipContent' => <<<LBL
{$name}<br>
Average: {$stat['tAvg']}<br>
Tests: {$stat['tCount']}
LBL,
                    'x' => $i,
                    'y' => $stat[ 'tAvg' ],
                ];

                $count_data[] = [
                    'toolTipContent' => null,
                    'x' => $i,
                    'y' => $stat[ 'tCount' ],
                ];
            }
        }

        if (!$average_data || !$count_data) {
            return null;
        }

        return ['avg' => json_encode($average_data), 'count' => json_encode($count_data)];
    }

    public function show_overview(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $limit = 100;
        $base_users_avg_count = $this->stats->averageCountOverallByUser($base,
                                                                        (new DateTime())->modify('-2 year'));
        $data = [
            'base' => $base,
            'bases' => $this->bases->fetchAll(),
            'graph' => $this->format_overview_graph_data($base_users_avg_count, $limit),
            'stats' => [
                'tests' => $this->stats->countOverall($base),
                'average' => $this->stats->averageOverall($base),
                'data' => $base_users_avg_count,
                'limit' => $limit,
            ],
        ];

        return $this->render(
            "admin/bases/overview.html.twig",
            $data
        );
    }
}