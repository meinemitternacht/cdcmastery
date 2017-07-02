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
});