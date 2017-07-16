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
        $r->addRoute('GET', '/log', [
            '\CDCMastery\Controllers\Admin',
            'renderLogEntries'
        ]);

        $r->addRoute('GET', '/log/{uuid}', [
            '\CDCMastery\Controllers\Admin',
            'renderLogDetail'
        ]);
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

        $r->addRoute('GET', '/create-subscription', [
            \CDCMastery\Controllers\Auth::class,
            'renderCreateSubscription'
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

    $r->addGroup('/tests', function (\FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'renderNewTest'
        ]);

        $r->addRoute('POST', '/new', [
            \CDCMastery\Controllers\Tests::class,
            'processNewTest'
        ]);

        $r->addRoute('GET', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'renderTest'
        ]);

        $r->addRoute('POST', '/{testUuid}', [
            \CDCMastery\Controllers\Tests::class,
            'processTest'
        ]);
    });
});