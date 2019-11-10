<?php

use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Admin\CdcData;
use CDCMastery\Controllers\Auth;
use CDCMastery\Controllers\Home;
use CDCMastery\Controllers\Search;
use CDCMastery\Controllers\Stats;
use CDCMastery\Controllers\Stats\Afscs;
use CDCMastery\Controllers\Stats\Bases;
use CDCMastery\Controllers\Stats\Tests;
use CDCMastery\Controllers\Stats\Users;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', [
        Home::class,
        'show_home',
    ]);

    $r->addRoute('GET', '/admin', [
        Admin::class,
        'show_admin_home',
    ]);

    $r->addGroup('/admin', function (RouteCollector $r) {
        $r->addRoute('GET', '/cdc/afsc', [
            CdcData::class,
            'renderAfscList',
        ]);

        $r->addRoute('POST', '/cdc/afsc', [
            CdcData::class,
            'processAddAfsc',
        ]);

        $r->addGroup('/cdc/afsc', function (RouteCollector $r) {
            $r->addRoute('GET', '/migrate', [
                CdcData::class,
                'renderMigrateAfsc',
            ]);

            $r->addRoute('POST', '/migrate', [
                CdcData::class,
                'processMigrateAfsc',
            ]);

            $r->addRoute('GET', '/{afscUuid}', [
                CdcData::class,
                'renderAfscHome',
            ]);

            $r->addRoute('POST', '/{afscUuid}', [
                CdcData::class,
                'processEditAfsc',
            ]);

            $r->addRoute('GET', '/{afscUuid}/delete', [
                CdcData::class,
                'renderDeleteAfsc',
            ]);

            $r->addRoute('POST', '/{afscUuid}/delete', [
                CdcData::class,
                'processDeleteAfsc',
            ]);

            $r->addRoute('POST', '/{afscUuid}/edit', [
                CdcData::class,
                'renderEditAfsc',
            ]);

            $r->addRoute('GET', '/{afscUuid}/questions', [
                CdcData::class,
                'renderQuestions',
            ]);

            $r->addRoute('POST', '/{afscUuid}/questions', [
                CdcData::class,
                'processAddQuestion',
            ]);

            $r->addGroup('/{afscUuid}/questions', function (RouteCollector $r) {
                $r->addRoute('GET', '/{questionUuid}', [
                    CdcData::class,
                    'renderQuestionHome',
                ]);

                $r->addRoute('POST', '/{questionUuid}', [
                    CdcData::class,
                    'processEditQuestion',
                ]);

                $r->addRoute('GET', '/{questionUuid}/delete', [
                    CdcData::class,
                    'renderDeleteQuestion',
                ]);

                $r->addRoute('POST', '/{questionUuid}/delete', [
                    CdcData::class,
                    'processDeleteQuestion',
                ]);

                $r->addRoute('GET', '/{questionUuid}/edit', [
                    CdcData::class,
                    'renderQuestionEdit',
                ]);

                $r->addRoute('POST', '/{questionUuid}/answers', [
                    CdcData::class,
                    'processEditAnswers',
                ]);

                $r->addRoute('POST', '/{questionUuid}/answers/{answerUuid}', [
                    CdcData::class,
                    'processEditAnswer',
                ]);
            });
        });
    });

    $r->addGroup('/auth', function (RouteCollector $r) {
        $r->addRoute('GET', '/activate', [
            Auth::class,
            'show_activation',
        ]);

        $r->addRoute('POST', '/activate', [
            Auth::class,
            'do_activation',
        ]);

        $r->addRoute('GET', '/activate/resend', [
            Auth::class,
            'show_activation_resend',
        ]);

        $r->addRoute('POST', '/activate/resend', [
            Auth::class,
            'do_activation_resend',
        ]);

        $r->addRoute('GET', '/login', [
            Auth::class,
            'show_login',
        ]);

        $r->addRoute('POST', '/login', [
            Auth::class,
            'do_login',
        ]);

        $r->addRoute('GET', '/logout', [
            Auth::class,
            'do_logout',
        ]);

        $r->addRoute('GET', '/register', [
            Auth::class,
            'show_registration',
        ]);

        $r->addRoute('POST', '/register', [
            Auth::class,
            'do_registration',
        ]);

        $r->addRoute('GET', '/reset', [
            Auth::class,
            'show_reset',
        ]);

        $r->addRoute('POST', '/reset', [
            Auth::class,
            'do_reset',
        ]);
    });

    $r->addRoute('GET', '/search', [
        Search::class,
        'show_search_home',
    ]);

    $r->addRoute('POST', '/search', [
        Search::class,
        'do_search',
    ]);

    $r->addRoute('GET', '/search/results', [
        Search::class,
        'show_search_results',
    ]);

    $r->addRoute('GET', '/stats', [
        Stats::class,
        'show_stats_home',
    ]);

    $r->addGroup('/stats', function (RouteCollector $r) {
        $r->addRoute('GET', '/afscs', [
            Afscs::class,
            'show_stats_afsc_home',
        ]);

        $r->addGroup('/afscs', function (RouteCollector $r) {
            $r->addRoute('GET', '/{afscUuid}/tests', [
                Afscs::class,
                'show_stats_afsc_tests',
            ]);

            $r->addGroup('/{afscUuid}/tests', function (RouteCollector $r) {
                $r->addRoute('GET', '/last-seven', [
                    Afscs::class,
                    'show_afsc_stats_tests_last_seven',
                ]);

                $r->addRoute('GET', '/month', [
                    Afscs::class,
                    'show_afsc_stats_tests_month',
                ]);

                $r->addRoute('GET', '/week', [
                    Afscs::class,
                    'show_afsc_stats_tests_week',
                ]);

                $r->addRoute('GET', '/year', [
                    Afscs::class,
                    'show_afsc_stats_tests_year',
                ]);
            });
        });

        $r->addRoute('GET', '/bases', [
            Bases::class,
            'show_bases_home',
        ]);

        $r->addRoute('GET', '/bases/tests', [
            Bases::class,
            'show_bases_tests',
        ]);

        $r->addGroup('/bases', function (RouteCollector $r) {
            $r->addRoute('GET', '/{baseUuid}/tests', [
                Bases::class,
                'show_bases_tests_timespan_home',
            ]);

            $r->addGroup('/{baseUuid}/tests', function (RouteCollector $r) {
                $r->addRoute('GET', '/last-seven', [
                    Bases::class,
                    'show_base_tests_last_seven',
                ]);

                $r->addRoute('GET', '/month', [
                    Bases::class,
                    'show_base_tests_month',
                ]);

                $r->addRoute('GET', '/week', [
                    Bases::class,
                    'show_base_tests_week',
                ]);

                $r->addRoute('GET', '/year', [
                    Bases::class,
                    'show_base_tests_year',
                ]);
            });
        });

        $r->addRoute('GET', '/tests', [
            Tests::class,
            'show_stats_tests_home',
        ]);

        $r->addGroup('/tests', function (RouteCollector $r) {
            $r->addRoute('GET', '/last-seven', [
                Tests::class,
                'show_tests_timespan_last_seven',
            ]);

            $r->addRoute('GET', '/month', [
                Tests::class,
                'show_tests_timespan_month',
            ]);

            $r->addRoute('GET', '/week', [
                Tests::class,
                'show_tests_timespan_week',
            ]);

            $r->addRoute('GET', '/year', [
                Tests::class,
                'show_tests_timespan_year',
            ]);
        });

        $r->addRoute('GET', '/users', [
            Users::class,
            'show_stats_users_home',
        ]);

        $r->addGroup('/users', function (RouteCollector $r) {
            $r->addRoute('GET', '/bases', [
                Users::class,
                'show_users_by_base',
            ]);

            $r->addRoute('GET', '/groups', [
                Users::class,
                'show_users_by_role',
            ]);
        });
    });

    $r->addRoute('GET', '/tests', [
        \CDCMastery\Controllers\Tests::class,
        'show_tests_home',
    ]);

    $r->addGroup('/tests', function (RouteCollector $r) {
        $r->addRoute('GET', '/history', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_complete',
        ]);

        $r->addRoute('GET', '/history/all', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_all',
        ]);

        $r->addRoute('GET', '/history/incomplete', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_incomplete',
        ]);

        $r->addRoute('GET', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'show_new_test',
        ]);

        $r->addRoute('POST', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'do_new_test',
        ]);

        $r->addRoute('GET', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'show_delete_incomplete_tests',
        ]);

        $r->addRoute('POST', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'do_delete_incomplete_tests',
        ]);

        $r->addRoute('GET', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'show_test',
        ]);

        $r->addRoute('POST', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'do_test_handler',
        ]);

        $r->addRoute('GET', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'show_delete_test',
        ]);

        $r->addRoute('POST', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'do_delete_test',
        ]);
    });
});