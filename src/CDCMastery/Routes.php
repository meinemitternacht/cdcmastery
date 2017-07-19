<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:30 PM
 */

return FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', [
        \CDCMastery\Controllers\Home::class,
        'renderFrontPage'
    ]);

    $r->addRoute('GET', '/admin', [
        '\CDCMastery\Controllers\Admin',
        'renderAdminHome'
    ]);

    $r->addGroup('/admin', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/cdc/afsc', [
            \CDCMastery\Controllers\Admin\CdcData::class,
            'renderAfscList'
        ]);

        $r->addRoute('POST', '/cdc/afsc', [
            \CDCMastery\Controllers\Admin\CdcData::class,
            'processNewAfsc'
        ]);
        
        $r->addGroup('/cdc/afsc', function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/migrate', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'renderMigrateAfsc'
            ]);

            $r->addRoute('POST', '/migrate', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'processMigrateAfsc'
            ]);

            $r->addRoute('GET', '/{afscUuid}', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'renderAfscHome'
            ]);

            $r->addRoute('POST', '/{afscUuid}', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'processEditAfsc'
            ]);

            $r->addRoute('GET', '/{afscUuid}/delete', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'renderDeleteAfsc'
            ]);

            $r->addRoute('POST', '/{afscUuid}/delete', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'processDeleteAfsc'
            ]);

            $r->addRoute('GET', '/{afscUuid}/questions', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'renderQuestions'
            ]);

            $r->addRoute('POST', '/{afscUuid}/questions', [
                \CDCMastery\Controllers\Admin\CdcData::class,
                'processAddQuestion'
            ]);
            
            $r->addGroup('/{afscUuid}/questions', function (\FastRoute\RouteCollector $r) {
                $r->addRoute('GET', '/{questionUuid}', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'renderQuestionHome'
                ]);

                $r->addRoute('POST', '/{questionUuid}', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'processEditQuestion'
                ]);

                $r->addRoute('GET', '/{questionUuid}/delete', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'renderDeleteQuestion'
                ]);

                $r->addRoute('POST', '/{questionUuid}/delete', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'processDeleteQuestion'
                ]);

                $r->addRoute('POST', '/{questionUuid}/answers', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'processEditAnswers'
                ]);

                $r->addRoute('POST', '/{questionUuid}/answers/{answerUuid}', [
                    \CDCMastery\Controllers\Admin\CdcData::class,
                    'processEditAnswer'
                ]); 
            });
        });
    });

    $r->addGroup('/auth', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/activate', [
            \CDCMastery\Controllers\Auth::class,
            'renderUserActivation'
        ]);

        $r->addRoute('POST', '/activate', [
            \CDCMastery\Controllers\Auth::class,
            'processUserActivation'
        ]);

        $r->addRoute('GET', '/activate/resend', [
            \CDCMastery\Controllers\Auth::class,
            'renderUserActivationResendEmail'
        ]);

        $r->addRoute('POST', '/activate/resend', [
            \CDCMastery\Controllers\Auth::class,
            'processUserActivationResendEmail'
        ]);

        $r->addRoute('GET', '/login', [
            \CDCMastery\Controllers\Auth::class,
            'renderLogin'
        ]);

        $r->addRoute('POST', '/login', [
            \CDCMastery\Controllers\Auth::class,
            'processLogin'
        ]);

        $r->addRoute('GET', '/logout', [
            \CDCMastery\Controllers\Auth::class,
            'processLogout'
        ]);

        $r->addRoute('GET', '/register', [
            \CDCMastery\Controllers\Auth::class,
            'renderUserRegistration'
        ]);

        $r->addRoute('GET', '/reset', [
            \CDCMastery\Controllers\Auth::class,
            'renderUserPasswordReset'
        ]);
    });

    $r->addGroup('/errors', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/400', [
            \CDCMastery\Controllers\Errors::class,
            'renderError400'
        ]);

        $r->addRoute('GET', '/401', [
            \CDCMastery\Controllers\Errors::class,
            'renderError401'
        ]);

        $r->addRoute('GET', '/403', [
            \CDCMastery\Controllers\Errors::class,
            'renderError403'
        ]);

        $r->addRoute('GET', '/404', [
            \CDCMastery\Controllers\Errors::class,
            'renderError404'
        ]);

        $r->addRoute('GET', '/500', [
            \CDCMastery\Controllers\Errors::class,
            'renderError500'
        ]);

        $r->addRoute('GET', '/maintenance', [
            \CDCMastery\Controllers\Errors::class,
            'renderSiteMaintenance'
        ]);

        $r->addRoute('GET', '/offline', [
            \CDCMastery\Controllers\Errors::class,
            'renderSiteOffline'
        ]);
    });

    $r->addRoute('GET', '/search', [
        \CDCMastery\Controllers\Search::class,
        'renderSearchHome'
    ]);

    $r->addRoute('POST', '/search', [
        \CDCMastery\Controllers\Search::class,
        'processSearch'
    ]);

    $r->addRoute('GET', '/search/results', [
        \CDCMastery\Controllers\Search::class,
        'renderSearchResults'
    ]);

    $r->addRoute('GET', '/stats', [
        \CDCMastery\Controllers\Stats::class,
        'renderStatsHome'
    ]);

    $r->addGroup('/stats', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/bases', [
            \CDCMastery\Controllers\Stats\Bases::class,
            'renderBasesStatsHome'
        ]);

        $r->addRoute('GET', '/bases/tests', [
            \CDCMastery\Controllers\Stats\Bases::class,
            'renderBasesTests'
        ]);

        $r->addGroup('/bases', function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/{baseUuid}/tests', [
                \CDCMastery\Controllers\Stats\Bases::class,
                'renderBaseTests'
            ]);

            $r->addGroup('/{baseUuid}/tests', function (\FastRoute\RouteCollector $r) {
                $r->addRoute('GET', '/last-seven', [
                    \CDCMastery\Controllers\Stats\Bases::class,
                    'renderBaseTestsLastSeven'
                ]);

                $r->addRoute('GET', '/month', [
                    \CDCMastery\Controllers\Stats\Bases::class,
                    'renderBaseTestsByMonth'
                ]);

                $r->addRoute('GET', '/week', [
                    \CDCMastery\Controllers\Stats\Bases::class,
                    'renderBaseTestsByWeek'
                ]);

                $r->addRoute('GET', '/year', [
                    \CDCMastery\Controllers\Stats\Bases::class,
                    'renderBaseTestsByYear'
                ]);
            });
        });

        $r->addRoute('GET', '/tests', [
            \CDCMastery\Controllers\Stats\Tests::class,
            'renderTestsStatsHome'
        ]);

        $r->addGroup('/tests', function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/last-seven', [
                \CDCMastery\Controllers\Stats\Tests::class,
                'renderTestsLastSeven'
            ]);

            $r->addRoute('GET', '/month', [
                \CDCMastery\Controllers\Stats\Tests::class,
                'renderTestsByMonth'
            ]);

            $r->addRoute('GET', '/week', [
                \CDCMastery\Controllers\Stats\Tests::class,
                'renderTestsByWeek'
            ]);

            $r->addRoute('GET', '/year', [
                \CDCMastery\Controllers\Stats\Tests::class,
                'renderTestsByYear'
            ]);
        });

        $r->addRoute('GET', '/users', [
            \CDCMastery\Controllers\Stats\Users::class,
            'renderUsersStatsHome'
        ]);

        $r->addGroup('/users', function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/bases', [
                \CDCMastery\Controllers\Stats\Users::class,
                'renderUsersByBase'
            ]);

            $r->addRoute('GET', '/groups', [
                \CDCMastery\Controllers\Stats\Users::class,
                'renderUsersByRole'
            ]);
        });
    });

    $r->addRoute('GET', '/tests', [
        \CDCMastery\Controllers\Tests::class,
        'renderTestsHome'
    ]);

    $r->addGroup('/tests', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/history', [
            \CDCMastery\Controllers\Tests::class,
            'renderTestHistoryComplete'
        ]);

        $r->addRoute('GET', '/history/incomplete', [
            \CDCMastery\Controllers\Tests::class,
            'renderTestHistoryIncomplete'
        ]);

        $r->addRoute('GET', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'renderNewTest'
        ]);

        $r->addRoute('POST', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'processNewTest'
        ]);

        $r->addRoute('GET', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'renderDeleteIncompleteTests'
        ]);

        $r->addRoute('POST', '/incomplete/delete', [
            \CDCMastery\Controllers\Tests::class,
            'processDeleteIncompleteTests'
        ]);

        $r->addRoute('GET', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'renderTest'
        ]);

        $r->addRoute('POST', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'processTest'
        ]);

        $r->addRoute('GET', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'renderDeleteTest'
        ]);

        $r->addRoute('POST', '/{testUuid}/delete', [
            \CDCMastery\Controllers\Tests::class,
            'processDeleteTest'
        ]);
    });
});