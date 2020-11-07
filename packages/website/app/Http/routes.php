<?php

use Illuminate\Routing\Router;

/* @var Router $router */

$router->get('/', ['as' => 'home', 'uses' => 'HomeController@home']);

$router->post('kobe', ['as' => 'kobe', 'middleware' => ['ip'], 'uses' => 'KobeController@kobe']);

$router->group(['prefix' => 'ranking', 'as' => 'ranking.'], function (Router $router) {
    $router->get('daily', ['as' => 'daily', 'uses' => 'RankingController@daily']);
    $router->get('weekly', ['as' => 'weekly', 'uses' => 'RankingController@weekly']);
    $router->get('monthly', ['as' => 'monthly', 'uses' => 'RankingController@monthly']);
});

$router->get('auth/sign-in', ['as' => 'auth.sign-in', 'uses' => 'AuthController@signIn']);
$router->post('auth/auth', ['as' => 'auth.auth', 'uses' => 'AuthController@auth']);
$router->get('auth/sign-out', ['as' => 'auth.sign-out', 'uses' => 'AuthController@signOut']);

$router->get('oauth/facebook', ['as' => 'oauth.facebook', 'uses' => 'OAuthController@facebook']);
$router->get('oauth/facebook/callback', ['as' => 'oauth.facebook.callback', 'uses' => 'OAuthController@facebookCallback']);

$router->get('terms-of-service-and-privacy-policy', ['as' => 'tos-pp', 'uses' => 'HomeController@tosAndPp']);

$router->group(['prefix' => 'dashboard', 'namespace' => 'Dashboard', 'as' => 'dashboard.', 'middleware' => 'auth:manager'], function (Router $router) {
    $router->group(['prefix' => 'posts', 'as' => 'posts.'], function (Router $router) {
        $router->get('/', ['as' => 'index', 'uses' => 'PostController@index']);
        $router->get('{id}/block', ['as' => 'block', 'uses' => 'PostController@block']);
        $router->get('{id}/delete', ['as' => 'delete', 'uses' => 'PostController@destroy']);
    });

    $router->group(['prefix' => 'block-words', 'as' => 'block-words.'], function (Router $router) {
        $router->get('/', ['as' => 'index', 'uses' => 'BlockWordController@index']);
        $router->post('/', ['as' => 'store', 'uses' => 'BlockWordController@store']);
        $router->get('{value}/delete', ['as' => 'delete', 'uses' => 'BlockWordController@destroy']);
    });

    $router->get('terms-of-service-privacy-policy', ['as' => 'tos-pp.index', 'uses' => 'ApplicationController@tosAndPp']);
    $router->post('terms-of-service-privacy-policy', ['as' => 'tos-pp.update', 'uses' => 'ApplicationController@tosAndPp']);

    $router->get('page', ['as' => 'page.index', 'uses' => 'ApplicationController@page']);
    $router->post('page', ['as' => 'page.update', 'uses' => 'ApplicationController@page']);

    $router->group(['prefix' => 'website', 'as' => 'website.'], function (Router $router) {
        $router->get('/', ['as' => 'index', 'uses' => 'WebsiteController@index']);
        $router->get('reset', ['as' => 'reset', 'uses' => 'WebsiteController@reset']);
    });
});

$router->group(['prefix' => 'install', 'as' => 'install.'], function (Router $router) {
    $router->get('/', ['as' => 'index', 'uses' => 'InstallController@index']);

    $router->get('facebook-service', ['as' => 'facebook', 'uses' => 'InstallController@facebook']);
    $router->post('facebook-service', ['as' => 'facebook.store', 'uses' => 'InstallController@storeFacebook']);

    $router->get('recaptcha-service', ['as' => 'recaptcha', 'uses' => 'InstallController@recaptcha']);
    $router->post('recaptcha-service', ['as' => 'recaptcha.store', 'uses' => 'InstallController@storeRecaptcha']);

    $router->get('google-service', ['as' => 'google', 'uses' => 'InstallController@google']);
    $router->post('google-service', ['as' => 'google.store', 'uses' => 'InstallController@storeGoogle']);

    $router->get('application-service', ['as' => 'application', 'uses' => 'InstallController@application']);
    $router->post('application-service', ['as' => 'application.store', 'uses' => 'InstallController@storeApplication']);

    $router->get('finish', ['as' => 'finish', 'uses' => 'InstallController@finish']);
});
