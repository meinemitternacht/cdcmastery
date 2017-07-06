<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;

class Home extends RootController
{
    public function renderFrontPage(): string
    {
        return AuthHelpers::isLoggedIn()
            ? $this->renderFrontPageAuth()
            : $this->renderFrontPageAnon();
    }

    private function renderFrontPageAnon(): string
    {
        $stats_test = $this->container->get(Tests::class);

        $data = [
            'lastSevenStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests(
                    $stats_test->averageLastSevenDays()
                ),
                'count' => StatisticsHelpers::formatGraphDataTests(
                    $stats_test->countLastSevenDays()
                )
            ],
            'yearStats' => [
                'avg' => StatisticsHelpers::formatGraphDataTests(
                    $stats_test->averageByYear()
                ),
                'count' => StatisticsHelpers::formatGraphDataTests(
                    $stats_test->countByYear()
                )
            ]
        ];

        return $this->render(
            'public/home/home.html.twig',
            $data
        );
    }

    private function renderFrontPageAuth(): string
    {
        return $this->render(
            'home/home.html.twig'
        );
    }
}