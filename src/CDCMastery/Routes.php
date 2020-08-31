<?php

use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Admin\CdcData;
use CDCMastery\Controllers\Admin\OfficeSymbols;
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
    /** @uses \CDCMastery\Controllers\Home::show_home() */
    $r->addRoute('GET', '/', [
        Home::class,
        'show_home',
    ]);

    /** @uses \CDCMastery\Controllers\Admin::show_admin_home() */
    $r->addRoute('GET', '/admin', [
        Admin::class,
        'show_admin_home',
    ]);

    $r->addGroup('/admin', function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Admin\Bases::show_home() */
        $r->addRoute('GET', '/bases', [
            Admin\Bases::class,
            'show_home',
        ]);

        $r->addGroup('/bases', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\Bases::do_edit() */
            $r->addRoute('POST', '/add', [
                Admin\Bases::class,
                'do_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Bases::show_edit() */
            $r->addRoute('GET', '/{uuid}/edit', [
                Admin\Bases::class,
                'show_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Bases::do_edit() */
            $r->addRoute('POST', '/{uuid}/edit', [
                Admin\Bases::class,
                'do_edit',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_list() */
        $r->addRoute('GET', '/cdc/afsc', [
            CdcData::class,
            'show_afsc_list',
        ]);

        $r->addGroup('/cdc/afsc', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_add() */
            $r->addRoute('POST', '/add', [
                CdcData::class,
                'do_afsc_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_migrate() */
            $r->addRoute('GET', '/migrate', [
                CdcData::class,
                'show_afsc_migrate',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_migrate() */
            $r->addRoute('POST', '/migrate', [
                CdcData::class,
                'do_afsc_migrate',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_home() */
            $r->addRoute('GET', '/{uuid}', [
                CdcData::class,
                'show_afsc_home',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_edit() */
            $r->addRoute('POST', '/{uuid}', [
                CdcData::class,
                'do_afsc_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_delete() */
            $r->addRoute('GET', '/{uuid}/delete', [
                CdcData::class,
                'show_afsc_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_delete() */
            $r->addRoute('POST', '/{uuid}/delete', [
                CdcData::class,
                'do_afsc_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_disable() */
            $r->addRoute('GET', '/{uuid}/disable', [
                CdcData::class,
                'show_afsc_disable',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_disable() */
            $r->addRoute('POST', '/{uuid}/disable', [
                CdcData::class,
                'do_afsc_disable',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_edit() */
            $r->addRoute('GET', '/{uuid}/edit', [
                CdcData::class,
                'show_afsc_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_edit() */
            $r->addRoute('POST', '/{uuid}/edit', [
                CdcData::class,
                'do_afsc_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_questions() */
            $r->addRoute('GET', '/{uuid}/questions', [
                CdcData::class,
                'show_afsc_questions',
            ]);

            $r->addGroup('/{uuid}/questions', function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_add() */
                $r->addRoute('GET', '/add', [
                    CdcData::class,
                    'show_afsc_question_add',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_add_legacy() */
                $r->addRoute('GET', '/add/legacy', [
                    CdcData::class,
                    'show_afsc_question_add_legacy',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_add() */
                $r->addRoute('POST', '/add', [
                    CdcData::class,
                    'do_afsc_question_add',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question() */
                $r->addRoute('GET', '/{quuid}', [
                    CdcData::class,
                    'show_afsc_question',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_edit() */
                $r->addRoute('POST', '/{quuid}', [
                    CdcData::class,
                    'do_afsc_question_edit',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_delete() */
                $r->addRoute('GET', '/{quuid}/delete', [
                    CdcData::class,
                    'show_afsc_question_delete',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_delete() */
                $r->addRoute('POST', '/{quuid}/delete', [
                    CdcData::class,
                    'do_afsc_question_delete',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_disable() */
                $r->addRoute('GET', '/{quuid}/disable', [
                    CdcData::class,
                    'show_afsc_question_disable',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_disable() */
                $r->addRoute('POST', '/{quuid}/disable', [
                    CdcData::class,
                    'do_afsc_question_disable',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_edit() */
                $r->addRoute('GET', '/{quuid}/edit', [
                    CdcData::class,
                    'show_afsc_question_edit',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_edit() */
                $r->addRoute('POST', '/{quuid}/edit', [
                    CdcData::class,
                    'do_afsc_question_edit',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_question_restore() */
                $r->addRoute('GET', '/{quuid}/enable', [
                    CdcData::class,
                    'show_afsc_question_restore',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_restore() */
                $r->addRoute('POST', '/{quuid}/enable', [
                    CdcData::class,
                    'do_afsc_question_restore',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_answers_edit() */
                $r->addRoute('POST', '/{quuid}/answers', [
                    CdcData::class,
                    'do_afsc_question_answers_edit',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_question_answer_edit() */
                $r->addRoute('POST', '/{quuid}/answers/{answerUuid}', [
                    CdcData::class,
                    'do_afsc_question_answer_edit',
                ]);
            });

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_restore() */
            $r->addRoute('GET', '/{uuid}/restore', [
                CdcData::class,
                'show_afsc_restore',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_restore() */
            $r->addRoute('POST', '/{uuid}/restore', [
                CdcData::class,
                'do_afsc_restore',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::show_home() */
        $r->addRoute('GET', '/office-symbols', [
            OfficeSymbols::class,
            'show_home',
        ]);

        $r->addGroup('/office-symbols', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::do_add() */
            $r->addRoute('POST', '/add', [
                OfficeSymbols::class,
                'do_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::show_delete() */
            $r->addRoute('GET', '/{uuid}/delete', [
                OfficeSymbols::class,
                'show_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::do_delete() */
            $r->addRoute('POST', '/{uuid}/delete', [
                OfficeSymbols::class,
                'do_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::show_edit() */
            $r->addRoute('GET', '/{uuid}/edit', [
                OfficeSymbols::class,
                'show_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::do_edit() */
            $r->addRoute('POST', '/{uuid}/edit', [
                OfficeSymbols::class,
                'do_edit',
            ]);
        });
    });

    $r->addGroup('/auth', function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Auth::show_activation() */
        $r->addRoute('GET', '/activate', [
            Auth::class,
            'show_activation',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_activation() */
        $r->addRoute('POST', '/activate', [
            Auth::class,
            'do_activation',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_activation_resend() */
        $r->addRoute('GET', '/activate/resend', [
            Auth::class,
            'show_activation_resend',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_activation_resend() */
        $r->addRoute('POST', '/activate/resend', [
            Auth::class,
            'do_activation_resend',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_login() */
        $r->addRoute('GET', '/login', [
            Auth::class,
            'show_login',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_login() */
        $r->addRoute('POST', '/login', [
            Auth::class,
            'do_login',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_logout() */
        $r->addRoute('GET', '/logout', [
            Auth::class,
            'do_logout',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_registration() */
        $r->addRoute('GET', '/register', [
            Auth::class,
            'show_registration',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_registration() */
        $r->addRoute('POST', '/register', [
            Auth::class,
            'do_registration',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_reset() */
        $r->addRoute('GET', '/reset', [
            Auth::class,
            'show_reset',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_reset() */
        $r->addRoute('POST', '/reset', [
            Auth::class,
            'do_reset',
        ]);
    });

    /** @uses \CDCMastery\Controllers\Search::show_search_home() */
    $r->addRoute('GET', '/search', [
        Search::class,
        'show_search_home',
    ]);

    /** @uses \CDCMastery\Controllers\Search::do_search() */
    $r->addRoute('POST', '/search', [
        Search::class,
        'do_search',
    ]);

    /** @uses \CDCMastery\Controllers\Search::show_search_results() */
    $r->addRoute('GET', '/search/results', [
        Search::class,
        'show_search_results',
    ]);

    /** @uses \CDCMastery\Controllers\Stats::show_stats_home() */
    $r->addRoute('GET', '/stats', [
        Stats::class,
        'show_stats_home',
    ]);

    $r->addGroup('/stats', function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Stats::show_stats_afsc_home() */
        $r->addRoute('GET', '/afscs', [
            Afscs::class,
            'show_stats_afsc_home',
        ]);

        $r->addGroup('/afscs', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats::show_stats_afsc_tests() */
            $r->addRoute('GET', '/{afscUuid}/tests', [
                Afscs::class,
                'show_stats_afsc_tests',
            ]);

            $r->addGroup('/{afscUuid}/tests', function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Stats::show_afsc_stats_tests_last_seven() */
                $r->addRoute('GET', '/last-seven', [
                    Afscs::class,
                    'show_afsc_stats_tests_last_seven',
                ]);

                /** @uses \CDCMastery\Controllers\Stats::show_afsc_stats_tests_month() */
                $r->addRoute('GET', '/month', [
                    Afscs::class,
                    'show_afsc_stats_tests_month',
                ]);

                /** @uses \CDCMastery\Controllers\Stats::show_afsc_stats_tests_week() */
                $r->addRoute('GET', '/week', [
                    Afscs::class,
                    'show_afsc_stats_tests_week',
                ]);

                /** @uses \CDCMastery\Controllers\Stats::show_afsc_stats_tests_year() */
                $r->addRoute('GET', '/year', [
                    Afscs::class,
                    'show_afsc_stats_tests_year',
                ]);
            });
        });

        /** @uses \CDCMastery\Controllers\Stats\Bases::show_bases_home() */
        $r->addRoute('GET', '/bases', [
            Bases::class,
            'show_bases_home',
        ]);

        /** @uses \CDCMastery\Controllers\Stats\Bases::show_bases_tests() */
        $r->addRoute('GET', '/bases/tests', [
            Bases::class,
            'show_bases_tests',
        ]);

        $r->addGroup('/bases', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats\Bases::show_bases_tests_timespan_home() */
            $r->addRoute('GET', '/{baseUuid}/tests', [
                Bases::class,
                'show_bases_tests_timespan_home',
            ]);

            $r->addGroup('/{baseUuid}/tests', function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Stats\Bases::show_base_tests_last_seven() */
                $r->addRoute('GET', '/last-seven', [
                    Bases::class,
                    'show_base_tests_last_seven',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Bases::show_base_tests_month() */
                $r->addRoute('GET', '/month', [
                    Bases::class,
                    'show_base_tests_month',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Bases::show_base_tests_week() */
                $r->addRoute('GET', '/week', [
                    Bases::class,
                    'show_base_tests_week',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Bases::show_base_tests_year() */
                $r->addRoute('GET', '/year', [
                    Bases::class,
                    'show_base_tests_year',
                ]);
            });
        });

        /** @uses \CDCMastery\Controllers\Stats\Tests::show_stats_tests_home() */
        $r->addRoute('GET', '/tests', [
            Tests::class,
            'show_stats_tests_home',
        ]);

        $r->addGroup('/tests', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats\Tests::show_tests_timespan_last_seven() */
            $r->addRoute('GET', '/last-seven', [
                Tests::class,
                'show_tests_timespan_last_seven',
            ]);

            /** @uses \CDCMastery\Controllers\Stats\Tests::show_tests_timespan_month() */
            $r->addRoute('GET', '/month', [
                Tests::class,
                'show_tests_timespan_month',
            ]);

            /** @uses \CDCMastery\Controllers\Stats\Tests::show_tests_timespan_week() */
            $r->addRoute('GET', '/week', [
                Tests::class,
                'show_tests_timespan_week',
            ]);

            /** @uses \CDCMastery\Controllers\Stats\Tests::show_tests_timespan_year() */
            $r->addRoute('GET', '/year', [
                Tests::class,
                'show_tests_timespan_year',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Stats\Users::show_stats_users_home() */
        $r->addRoute('GET', '/users', [
            Users::class,
            'show_stats_users_home',
        ]);

        $r->addGroup('/users', function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats\Users::show_users_by_base() */
            $r->addRoute('GET', '/bases', [
                Users::class,
                'show_users_by_base',
            ]);

            /** @uses \CDCMastery\Controllers\Stats\Users::show_users_by_role() */
            $r->addRoute('GET', '/groups', [
                Users::class,
                'show_users_by_role',
            ]);
        });
    });

    /** @uses \CDCMastery\Controllers\Tests::show_tests_home() */
    $r->addRoute('GET', '/tests', [
        \CDCMastery\Controllers\Tests::class,
        'show_tests_home',
    ]);

    $r->addGroup('/tests', function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Tests::show_test_history_complete() */
        $r->addRoute('GET', '/history', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_complete',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_test_history_all() */
        $r->addRoute('GET', '/history/all', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_all',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_test_history_incomplete() */
        $r->addRoute('GET', '/history/incomplete', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_incomplete',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_new_test() */
        $r->addRoute('GET', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'show_new_test',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::do_new_test() */
        $r->addRoute('POST', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'do_new_test',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_delete_incomplete_tests() */
        $r->addRoute('GET', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'show_delete_incomplete_tests',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::do_delete_incomplete_tests() */
        $r->addRoute('POST', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'do_delete_incomplete_tests',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_test() */
        $r->addRoute('GET', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'show_test',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::do_test_handler() */
        $r->addRoute('POST', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'do_test_handler',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_delete_test() */
        $r->addRoute('GET', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'show_delete_test',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::do_delete_test() */
        $r->addRoute('POST', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'do_delete_test',
        ]);
    });
});