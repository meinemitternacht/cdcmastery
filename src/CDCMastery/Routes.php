<?php

use CDCMastery\Controllers\About;
use CDCMastery\Controllers\Admin;
use CDCMastery\Controllers\Admin\Activations;
use CDCMastery\Controllers\Admin\AfscApprovals;
use CDCMastery\Controllers\Admin\CdcData;
use CDCMastery\Controllers\Admin\FlashCards;
use CDCMastery\Controllers\Admin\OfficeSymbols;
use CDCMastery\Controllers\Admin\RoleApprovals;
use CDCMastery\Controllers\Auth;
use CDCMastery\Controllers\Cards;
use CDCMastery\Controllers\Home;
use CDCMastery\Controllers\Overviews\TrainingOverview;
use CDCMastery\Controllers\Profile;
use CDCMastery\Controllers\Stats;
use CDCMastery\Controllers\Stats\Afscs;
use CDCMastery\Controllers\Stats\Bases;
use CDCMastery\Controllers\Stats\Tests;
use CDCMastery\Controllers\Stats\Users;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(static function (RouteCollector $r) {
    /** @uses \CDCMastery\Controllers\Home::show_home() */
    $r->addRoute('GET', '/', [
        Home::class,
        'show_home',
    ]);

    $r->addGroup('/about', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\About::show_disclaimer() */
        $r->addRoute('GET', '/disclaimer', [
            About::class,
            'show_disclaimer',
        ]);

        /** @uses \CDCMastery\Controllers\About::show_privacy_policy() */
        $r->addRoute('GET', '/privacy', [
            About::class,
            'show_privacy_policy',
        ]);

        /** @uses \CDCMastery\Controllers\About::show_terms_of_use() */
        $r->addRoute('GET', '/terms', [
            About::class,
            'show_terms_of_use',
        ]);
    });

    /** @uses \CDCMastery\Controllers\Admin::show_admin_home() */
    $r->addRoute('GET', '/admin', [
        Admin::class,
        'show_admin_home',
    ]);

    $r->addGroup('/admin', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Admin\Activations::show_home() */
        $r->addRoute('GET', '/activations', [
            Activations::class,
            'show_home',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Activations::do_manual_activation() */
        $r->addRoute('POST', '/activations', [
            Activations::class,
            'do_manual_activation',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Bases::show_home() */
        $r->addRoute('GET', '/bases', [
            Admin\Bases::class,
            'show_home',
        ]);

        $r->addGroup('/bases', static function (RouteCollector $r) {
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

            /** @uses \CDCMastery\Controllers\Admin\Bases::show_overview() */
            $r->addRoute('GET', '/{uuid}', [
                Admin\Bases::class,
                'show_overview',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_home() */
        $r->addRoute('GET', '/cards', [
            FlashCards::class,
            'show_home',
        ]);

        $r->addGroup('/cards', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_category_add() */
            $r->addRoute('GET', '/add', [
                FlashCards::class,
                'show_category_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_category_add() */
            $r->addRoute('POST', '/add', [
                FlashCards::class,
                'do_category_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_category_add_afsc() */
            $r->addRoute('GET', '/add-afsc', [
                FlashCards::class,
                'show_category_add_afsc',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_category_add_afsc() */
            $r->addRoute('POST', '/add-afsc', [
                FlashCards::class,
                'do_category_add_afsc',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_category() */
            $r->addRoute('GET', '/{uuid}', [
                FlashCards::class,
                'show_category',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_category_delete() */
            $r->addRoute('GET', '/{uuid}/delete', [
                FlashCards::class,
                'show_category_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_category_delete() */
            $r->addRoute('POST', '/{uuid}/delete', [
                FlashCards::class,
                'do_category_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_category_edit() */
            $r->addRoute('GET', '/{uuid}/edit', [
                FlashCards::class,
                'show_category_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_category_edit() */
            $r->addRoute('POST', '/{uuid}/edit', [
                FlashCards::class,
                'do_category_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_card_add() */
            $r->addRoute('GET', '/{uuid}/data/add', [
                FlashCards::class,
                'show_card_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_card_add() */
            $r->addRoute('POST', '/{uuid}/data/add', [
                FlashCards::class,
                'do_card_add',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_card() */
            $r->addRoute('GET', '/{uuid}/data/{card_uuid}', [
                FlashCards::class,
                'show_card',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_card_edit() */
            $r->addRoute('GET', '/{uuid}/data/{card_uuid}/edit', [
                FlashCards::class,
                'show_card_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_card_edit() */
            $r->addRoute('POST', '/{uuid}/data/{card_uuid}/edit', [
                FlashCards::class,
                'do_card_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::show_card_delete() */
            $r->addRoute('GET', '/{uuid}/data/{card_uuid}/delete', [
                FlashCards::class,
                'show_card_delete',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\FlashCards::do_card_delete() */
            $r->addRoute('POST', '/{uuid}/data/{card_uuid}/delete', [
                FlashCards::class,
                'do_card_delete',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_list() */
        $r->addRoute('GET', '/cdc/afsc', [
            CdcData::class,
            'show_afsc_list',
        ]);

        $r->addGroup('/cdc/afsc', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\CdcData::do_afsc_add() */
            $r->addRoute('POST', '/add', [
                CdcData::class,
                'do_afsc_add',
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

            $r->addGroup('/{uuid}/questions', static function (RouteCollector $r) {
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

            /** @uses \CDCMastery\Controllers\Admin\CdcData::show_afsc_users() */
            $r->addRoute('GET', '/{uuid}/users', [
                CdcData::class,
                'show_afsc_users',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Admin\OfficeSymbols::show_home() */
        $r->addRoute('GET', '/office-symbols', [
            OfficeSymbols::class,
            'show_home',
        ]);

        $r->addGroup('/office-symbols', static function (RouteCollector $r) {
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

        /** @uses \CDCMastery\Controllers\Admin\AfscApprovals::show_home() */
        $r->addRoute('GET', '/pending-afscs', [
            AfscApprovals::class,
            'show_home',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\AfscApprovals::do_approve_assocs() */
        $r->addRoute('POST', '/pending-afscs', [
            AfscApprovals::class,
            'do_approve_assocs',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\RoleApprovals::show_home() */
        $r->addRoute('GET', '/pending-roles', [
            RoleApprovals::class,
            'show_home',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\RoleApprovals::do_approve_roles() */
        $r->addRoute('POST', '/pending-roles', [
            RoleApprovals::class,
            'do_approve_roles',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Tests::show_tests_complete() */
        $r->addRoute('GET', '/tests', [
            Admin\Tests::class,
            'show_tests_complete',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Tests::show_tests_incomplete() */
        $r->addRoute('GET', '/tests/incomplete', [
            Admin\Tests::class,
            'show_tests_incomplete',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Tests::show_test() */
        $r->addRoute('GET', '/tests/{test_uuid}', [
            Admin\Tests::class,
            'show_test',
        ]);

        /** @uses \CDCMastery\Controllers\Admin\Users::show_home() */
        $r->addRoute('GET', '/users', [
            Admin\Users::class,
            'show_home',
        ]);

        $r->addGroup('/users', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Admin\Users::show_home() */
            $r->addRoute('GET', '/{uuid}', [
                Admin\Users::class,
                'show_profile',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_edit() */
            $r->addRoute('GET', '/{uuid}/edit', [
                Admin\Users::class,
                'show_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::do_edit() */
            $r->addRoute('POST', '/{uuid}/edit', [
                Admin\Users::class,
                'do_edit',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_disable() */
            $r->addRoute('GET', '/{uuid}/disable', [
                Admin\Users::class,
                'show_disable',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::toggle_disabled() */
            $r->addRoute('POST', '/{uuid}/disable', [
                Admin\Users::class,
                'toggle_disabled',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_reactivate() */
            $r->addRoute('GET', '/{uuid}/reactivate', [
                Admin\Users::class,
                'show_reactivate',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::toggle_disabled() */
            $r->addRoute('POST', '/{uuid}/reactivate', [
                Admin\Users::class,
                'toggle_disabled',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_password_reset() */
            $r->addRoute('GET', '/{uuid}/reset-password', [
                Admin\Users::class,
                'show_password_reset',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::do_password_reset() */
            $r->addRoute('POST', '/{uuid}/reset-password', [
                Admin\Users::class,
                'do_password_reset',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_resend_activation() */
            $r->addRoute('GET', '/{uuid}/resend-activation', [
                Admin\Users::class,
                'show_resend_activation',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::do_resend_activation() */
            $r->addRoute('POST', '/{uuid}/resend-activation', [
                Admin\Users::class,
                'do_resend_activation',
            ]);

            /** @uses \CDCMastery\Controllers\Admin\Users::show_afsc_associations() */
            $r->addRoute('GET', '/{uuid}/afsc', [
                Admin\Users::class,
                'show_afsc_associations',
            ]);

            $r->addGroup('/{uuid}/afsc', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Admin\Users::do_afsc_association_add() */
                $r->addRoute('POST', '/add', [
                    Admin\Users::class,
                    'do_afsc_association_add',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::do_afsc_association_approve() */
                $r->addRoute('POST', '/approve', [
                    Admin\Users::class,
                    'do_afsc_association_approve',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::do_afsc_association_remove() */
                $r->addRoute('POST', '/remove', [
                    Admin\Users::class,
                    'do_afsc_association_remove',
                ]);
            });

            /** @uses \CDCMastery\Controllers\Admin\Users::show_supervisor_associations() */
            $r->addRoute('GET', '/{uuid}/supervisors', [
                Admin\Users::class,
                'show_supervisor_associations',
            ]);

            $r->addGroup('/{uuid}/supervisors', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Admin\Users::do_supervisor_association_add() */
                $r->addRoute('POST', '/add', [
                    Admin\Users::class,
                    'do_supervisor_association_add',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::do_supervisor_association_remove() */
                $r->addRoute('POST', '/remove', [
                    Admin\Users::class,
                    'do_supervisor_association_remove',
                ]);
            });

            /** @uses \CDCMastery\Controllers\Admin\Users::show_tm_associations() */
            $r->addRoute('GET', '/{uuid}/training-managers', [
                Admin\Users::class,
                'show_tm_associations',
            ]);

            $r->addGroup('/{uuid}/training-managers', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Admin\Users::do_tm_association_add() */
                $r->addRoute('POST', '/add', [
                    Admin\Users::class,
                    'do_tm_association_add',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::do_tm_association_remove() */
                $r->addRoute('POST', '/remove', [
                    Admin\Users::class,
                    'do_tm_association_remove',
                ]);
            });

            /** @uses \CDCMastery\Controllers\Admin\Users::show_test_history_complete() */
            $r->addRoute('GET', '/{uuid}/tests', [
                Admin\Users::class,
                'show_test_history_complete',
            ]);

            $r->addGroup('/{uuid}/tests', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Admin\Users::show_test_history_incomplete() */
                $r->addRoute('GET', '/incomplete', [
                    Admin\Users::class,
                    'show_test_history_incomplete',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::show_delete_incomplete_tests() */
                $r->addRoute('GET', '/incomplete/delete', [
                    Admin\Users::class,
                    'show_delete_incomplete_tests',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::do_delete_incomplete_tests() */
                $r->addRoute('POST', '/incomplete/delete', [
                    Admin\Users::class,
                    'do_delete_incomplete_tests',
                ]);

                /** @uses \CDCMastery\Controllers\Admin\Users::show_test() */
                $r->addRoute('GET', '/{test_uuid}', [
                    Admin\Users::class,
                    'show_test',
                ]);
            });
        });
    });

    $r->addGroup('/auth', static function (RouteCollector $r) {
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

        /** @uses \CDCMastery\Controllers\Auth::do_activation() */
        $r->addRoute('GET', '/activate/{code}', [
            Auth::class,
            'do_activation',
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
        $r->addRoute('GET', '/register[/{type}]', [
            Auth::class,
            'show_registration',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_registration() */
        $r->addRoute('POST', '/register/{type}', [
            Auth::class,
            'do_registration',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_password_reset() */
        $r->addRoute('GET', '/reset', [
            Auth::class,
            'show_password_reset',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_password_reset_send() */
        $r->addRoute('POST', '/reset', [
            Auth::class,
            'do_password_reset_send',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::show_password_reset_change() */
        $r->addRoute('GET', '/reset/{code}', [
            Auth::class,
            'show_password_reset_change',
        ]);

        /** @uses \CDCMastery\Controllers\Auth::do_password_reset() */
        $r->addRoute('POST', '/reset/{code}', [
            Auth::class,
            'do_password_reset',
        ]);
    });

    /** @uses \CDCMastery\Controllers\Cards::show_home() */
    $r->addRoute('GET', '/cards', [
        Cards::class,
        'show_home',
    ]);

    $r->addGroup('/cards', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Cards::show_category_add() */
        $r->addRoute('GET', '/add', [
            Cards::class,
            'show_category_add',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_category_add() */
        $r->addRoute('POST', '/add', [
            Cards::class,
            'do_category_add',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_category() */
        $r->addRoute('GET', '/{uuid}', [
            Cards::class,
            'show_category',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_category_delete() */
        $r->addRoute('GET', '/{uuid}/delete', [
            Cards::class,
            'show_category_delete',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_category_delete() */
        $r->addRoute('POST', '/{uuid}/delete', [
            Cards::class,
            'do_category_delete',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_category_edit() */
        $r->addRoute('GET', '/{uuid}/edit', [
            Cards::class,
            'show_category_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_category_edit() */
        $r->addRoute('POST', '/{uuid}/edit', [
            Cards::class,
            'do_category_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Tests::show_test() */
        $r->addRoute('GET', '/{uuid}/study', [
            Cards::class,
            'show_study',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_card_handler() */
        $r->addRoute('POST', '/{uuid}/study', [
            Cards::class,
            'do_card_handler',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_card_add() */
        $r->addRoute('GET', '/{uuid}/data/add', [
            Cards::class,
            'show_card_add',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_card_add() */
        $r->addRoute('POST', '/{uuid}/data/add', [
            Cards::class,
            'do_card_add',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_card() */
        $r->addRoute('GET', '/{uuid}/data/{card_uuid}', [
            Cards::class,
            'show_card',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_card_edit() */
        $r->addRoute('GET', '/{uuid}/data/{card_uuid}/edit', [
            Cards::class,
            'show_card_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_card_edit() */
        $r->addRoute('POST', '/{uuid}/data/{card_uuid}/edit', [
            Cards::class,
            'do_card_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::show_card_delete() */
        $r->addRoute('GET', '/{uuid}/data/{card_uuid}/delete', [
            Cards::class,
            'show_card_delete',
        ]);

        /** @uses \CDCMastery\Controllers\Cards::do_card_delete() */
        $r->addRoute('POST', '/{uuid}/data/{card_uuid}/delete', [
            Cards::class,
            'do_card_delete',
        ]);
    });

    /** @uses \CDCMastery\Controllers\Profile::show_home() */
    $r->addRoute('GET', '/profile', [
        Profile::class,
        'show_home',
    ]);

    $r->addGroup('/profile', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Profile::show_afsc_associations() */
        $r->addRoute('GET', '/afsc', [
            Profile::class,
            'show_afsc_associations',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::do_afsc_association_add() */
        $r->addRoute('POST', '/afsc/add', [
            Profile::class,
            'do_afsc_association_add',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::do_afsc_association_remove() */
        $r->addRoute('POST', '/afsc/remove', [
            Profile::class,
            'do_afsc_association_remove',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::show_edit() */
        $r->addRoute('GET', '/edit', [
            Profile::class,
            'show_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::do_edit() */
        $r->addRoute('POST', '/edit', [
            Profile::class,
            'do_edit',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::show_role_request() */
        $r->addRoute('GET', '/role', [
            Profile::class,
            'show_role_request',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::do_role_request() */
        $r->addRoute('POST', '/role', [
            Profile::class,
            'do_role_request',
        ]);

        /** @uses \CDCMastery\Controllers\Profile::show_supervisor_associations() */
        $r->addRoute('GET', '/supervisors', [
            Profile::class,
            'show_supervisor_associations',
        ]);

        $r->addGroup('/supervisors', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Profile::do_supervisor_association_add() */
            $r->addRoute('POST', '/add', [
                Profile::class,
                'do_supervisor_association_add',
            ]);

            /** @uses \CDCMastery\Controllers\Profile::do_supervisor_association_remove() */
            $r->addRoute('POST', '/remove', [
                Profile::class,
                'do_supervisor_association_remove',
            ]);
        });

        /** @uses \CDCMastery\Controllers\Profile::show_tm_associations() */
        $r->addRoute('GET', '/training-managers', [
            Profile::class,
            'show_tm_associations',
        ]);

        $r->addGroup('/training-managers', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Profile::do_tm_association_add() */
            $r->addRoute('POST', '/add', [
                Profile::class,
                'do_tm_association_add',
            ]);

            /** @uses \CDCMastery\Controllers\Profile::do_tm_association_remove() */
            $r->addRoute('POST', '/remove', [
                Profile::class,
                'do_tm_association_remove',
            ]);
        });
    });

    /** @uses \CDCMastery\Controllers\Stats::show_stats_home() */
    $r->addRoute('GET', '/stats', [
        Stats::class,
        'show_stats_home',
    ]);

    $r->addGroup('/stats', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Stats\Afscs::show_stats_afsc_home() */
        $r->addRoute('GET', '/afscs', [
            Afscs::class,
            'show_stats_afsc_home',
        ]);

        $r->addGroup('/afscs', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats\Afscs::show_stats_afsc_tests() */
            $r->addRoute('GET', '/{afscUuid}/tests', [
                Afscs::class,
                'show_stats_afsc_tests',
            ]);

            $r->addGroup('/{afscUuid}/tests', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Stats\Afscs::show_afsc_stats_tests_last_seven() */
                $r->addRoute('GET', '/last-seven', [
                    Afscs::class,
                    'show_afsc_stats_tests_last_seven',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Afscs::show_afsc_stats_tests_month() */
                $r->addRoute('GET', '/month', [
                    Afscs::class,
                    'show_afsc_stats_tests_month',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Afscs::show_afsc_stats_tests_week() */
                $r->addRoute('GET', '/week', [
                    Afscs::class,
                    'show_afsc_stats_tests_week',
                ]);

                /** @uses \CDCMastery\Controllers\Stats\Afscs::show_afsc_stats_tests_year() */
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

        $r->addGroup('/bases', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Stats\Bases::show_bases_tests_timespan_home() */
            $r->addRoute('GET', '/{baseUuid}/tests', [
                Bases::class,
                'show_bases_tests_timespan_home',
            ]);

            $r->addGroup('/{baseUuid}/tests', static function (RouteCollector $r) {
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

        $r->addGroup('/tests', static function (RouteCollector $r) {
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

        $r->addGroup('/users', static function (RouteCollector $r) {
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

    $r->addGroup('/tests', static function (RouteCollector $r) {
        /** @uses \CDCMastery\Controllers\Tests::show_test_history_complete() */
        $r->addRoute('GET', '/history', [
            \CDCMastery\Controllers\Tests::class,
            'show_test_history_complete',
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

    /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_overview() */
    $r->addRoute('GET', '/training', [
        TrainingOverview::class,
        'show_overview',
    ]);

    $r->addGroup('/training', static function (RouteCollector $r) {
        $r->addGroup('/users', static function (RouteCollector $r) {
            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_profile() */
            $r->addRoute('GET', '/{uuid}', [
                TrainingOverview::class,
                'show_profile',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_afsc_associations() */
            $r->addRoute('GET', '/{uuid}/afsc', [
                TrainingOverview::class,
                'show_afsc_associations',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_afsc_association_add() */
            $r->addRoute('POST', '/{uuid}/afsc/add', [
                TrainingOverview::class,
                'do_afsc_association_add',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_afsc_association_approve() */
            $r->addRoute('POST', '/{uuid}/afsc/approve', [
                TrainingOverview::class,
                'do_afsc_association_approve',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_afsc_association_remove() */
            $r->addRoute('POST', '/{uuid}/afsc/remove', [
                TrainingOverview::class,
                'do_afsc_association_remove',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_password_reset() */
            $r->addRoute('GET', '/{uuid}/reset-password', [
                TrainingOverview::class,
                'show_password_reset',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_password_reset() */
            $r->addRoute('POST', '/{uuid}/reset-password', [
                TrainingOverview::class,
                'do_password_reset',
            ]);

            /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_test_history_complete() */
            $r->addRoute('GET', '/{uuid}/tests', [
                TrainingOverview::class,
                'show_test_history_complete',
            ]);

            $r->addGroup('/{uuid}/tests', static function (RouteCollector $r) {
                /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_test_history_incomplete() */
                $r->addRoute('GET', '/incomplete', [
                    TrainingOverview::class,
                    'show_test_history_incomplete',
                ]);

                /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_delete_incomplete_tests() */
                $r->addRoute('GET', '/incomplete/delete', [
                    TrainingOverview::class,
                    'show_delete_incomplete_tests',
                ]);

                /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_delete_incomplete_tests() */
                $r->addRoute('POST', '/incomplete/delete', [
                    TrainingOverview::class,
                    'do_delete_incomplete_tests',
                ]);

                /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_test() */
                $r->addRoute('GET', '/{test_uuid}', [
                    TrainingOverview::class,
                    'show_test',
                ]);
            });
        });

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_offline_tests() */
        $r->addRoute('GET', '/offline', [
            TrainingOverview::class,
            'show_offline_tests',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_offline_test() */
        $r->addRoute('GET', '/offline/{uuid}', [
            TrainingOverview::class,
            'show_offline_test',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_offline_test_print() */
        $r->addRoute('GET', '/offline/{uuid}/print', [
            TrainingOverview::class,
            'show_offline_test_print',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_generate_offline_test() */
        $r->addRoute('POST', '/offline/new', [
            TrainingOverview::class,
            'do_generate_offline_test',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::show_subordinates() */
        $r->addRoute('GET', '/subordinates', [
            TrainingOverview::class,
            'show_subordinates',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_subordinates_add() */
        $r->addRoute('POST', '/subordinates/add', [
            TrainingOverview::class,
            'do_subordinates_add',
        ]);

        /** @uses \CDCMastery\Controllers\Overviews\TrainingOverview::do_subordinates_remove() */
        $r->addRoute('POST', '/subordinates/remove', [
            TrainingOverview::class,
            'do_subordinates_remove',
        ]);
    });
});