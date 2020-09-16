<?php


namespace CDCMastery\Controllers\Overviews;


use CDCMastery\Controllers\RootController;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\Subordinates\SubordinateStats;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserSupervisorAssociations;
use CDCMastery\Models\Users\UserTrainingManagerAssociations;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class TrainingOverview extends RootController
{
    private AuthHelpers $auth_helpers;
    private UserCollection $users;
    private BaseCollection $bases;
    private RoleCollection $roles;
    private OfficeSymbolCollection $symbols;
    private TestStats $test_stats;
    private SubordinateStats $sub_stats;
    private TestCollection $tests;
    private TestDataHelpers $test_data;
    private AfscCollection $afscs;
    private UserAfscAssociations $afsc_assocs;
    private UserTrainingManagerAssociations $tm_assocs;
    private UserSupervisorAssociations $su_assocs;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        RoleCollection $roles,
        OfficeSymbolCollection $symbols,
        TestStats $test_stats,
        SubordinateStats $sub_stats,
        TestCollection $tests,
        TestDataHelpers $test_data,
        AfscCollection $afscs,
        UserAfscAssociations $afsc_assocs,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->users = $users;
        $this->bases = $bases;
        $this->roles = $roles;
        $this->symbols = $symbols;
        $this->test_stats = $test_stats;
        $this->sub_stats = $sub_stats;
        $this->tests = $tests;
        $this->test_data = $test_data;
        $this->afscs = $afscs;
        $this->afsc_assocs = $afsc_assocs;
        $this->tm_assocs = $tm_assocs;
        $this->su_assocs = $su_assocs;
    }

    private function get_user(string $uuid): User
    {
        $user = $this->users->fetch($uuid);

        if ($user === null || $user->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'You do not exist');

            $this->redirect('/')->send();
            exit;
        }

        return $user;
    }

    public function show_profile(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $this->symbols->fetch($u_symbol);
        }

        $incomplete_tests = array_filter(
            $this->tests->fetchAllByUser($user),
            function (Test $v) {
                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);
        $afsc_assocs = $this->afsc_assocs->fetchAllByUser($user);
        $tm_assocs = $this->users->fetchArray($this->tm_assocs->fetchAllByUser($user), $user_sort);
        $su_assocs = $this->users->fetchArray($this->su_assocs->fetchAllByUser($user), $user_sort);

        $subs = null;

        switch ($role->getType()) {
            case Role::TYPE_SUPERVISOR:
                $subs = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                break;
        }

        $data = [
            'user' => $user,
            'base' => $base,
            'symbol' => $symbol ?? null,
            'role' => $role,
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
            ],
            'assocs' => [
                'tm' => $tm_assocs,
                'su' => $su_assocs,
                'subordinates' => $subs,
            ],
            'stats' => [
                'tests' => [
                    'complete' => [
                        'count' => $this->test_stats->userCountOverall($user),
                        'avg' => $this->test_stats->userAverageOverall($user),
                    ],
                    'incomplete' => [
                        'count' => count($incomplete_tests),
                    ],
                ],
            ],
        ];

        return $this->render(
            'training/users/profile.html.twig',
            $data
        );
    }

    /**
     * @param array $stats
     * @param User[] $users
     * @return array|null
     */
    private function format_overview_graph_data(array &$stats, array $users): ?array
    {
        /*
         *  [
         *    user_uuid => [
         *      tAvg => test average (float),
         *      tCount => test count (int)
         *    ]
         *  ]
         */

        if (!$stats) {
            return null;
        }

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
            $stats[ $user_uuid ][ 'name_last' ] = $users[ $user_uuid ]->getLastName();
            $stats[ $user_uuid ][ 'name_first' ] = $users[ $user_uuid ]->getFirstName();
            $stats[ $user_uuid ][ 'last_login' ] = $ll
                ? $ll->format(DateTimeHelpers::DT_FMT_SHORT)
                : 'Never';

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

            $i++;
        }

        if (!$average_data || !$count_data) {
            return null;
        }

        return ['avg' => json_encode($average_data), 'count' => json_encode($count_data)];
    }

    public function show_overview(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->flash()->add(MessageTypes::WARNING,
                                'We could not properly determine the state of your account. ' .
                                'Please contact the site administrator.');

            return $this->redirect('/');
        }

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $this->tm_assocs->fetchAllByTrainingManager($user);
                break;
            case Role::TYPE_SUPERVISOR:
                $subs = $this->su_assocs->fetchAllBySupervisor($user);
                break;
            default:
                $this->flash()->add(MessageTypes::WARNING,
                                    'We could not properly determine the state of your account. ' .
                                    'Please contact the site administrator.');

                return $this->redirect('/');
        }

        $users = $this->users->fetchArray($subs);

        $n_supervisors = 0;
        $graph_data = null;
        $sub_stats_count_avg_grouped = null;
        $sub_stats_latest = null;
        $sub_stats_count_overall = null;
        $sub_stats_avg_overall = null;

        if ($subs) {
            $sub_stats_count_avg_grouped = $this->sub_stats->subordinate_tests_count_avg($user);
            $sub_stats_latest = $this->sub_stats->subordinate_tests_latest_score($user);
            $sub_stats_count_overall = $this->sub_stats->subordinate_tests_count_overall($user);
            $sub_stats_avg_overall = $this->sub_stats->subordinate_tests_avg_overall($user);

            $graph_data = $this->format_overview_graph_data($sub_stats_count_avg_grouped, $users);

            if ($role->getType() === Role::TYPE_TRAINING_MANAGER) {
                $super_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);
                $n_supervisors = count(array_filter($users, static function (User $v) use ($super_role) {
                    return $v->getRole() === $super_role->getUuid();
                }));
            }

            uasort($sub_stats_count_avg_grouped, static function (array $a, array $b): int {
                if ($a[ 'name_last' ] === $b[ 'name_last' ]) {
                    return $a[ 'name_first' ] <=> $b[ 'name_first' ];
                }

                return $a[ 'name_last' ] <=> $b[ 'name_last' ];
            });
        }

        $data = [
            'user' => $user,
            'role' => $role,
            'graph' => $graph_data,
            'stats' => [
                'count_avg' => $sub_stats_count_avg_grouped,
                'latest' => $sub_stats_latest,
                'tests' => $sub_stats_count_overall,
                'average' => $sub_stats_avg_overall,
                'n_users' => count($sub_stats_count_avg_grouped),
                'n_supervisors' => $n_supervisors,
            ],
        ];

        return $this->render(
            'training/overview.html.twig',
            $data
        );
    }
}