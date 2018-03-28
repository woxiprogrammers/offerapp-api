<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->post('login',array('uses' => 'LoginController@login'));
$app->post('register',array('uses' => 'RegisterController@register'));
$app->post('getOtp',array('uses' => 'RegisterController@getOtp'));





$app->group(['prefix' => 'seller'], function () use($app){
    $app->group(['prefix' => 'offer'], function () use($app){
        $app->post('listing', array('uses' => 'Seller\OfferController@getOfferListing'));
        $app->post('detail',array('uses' => 'Seller\OfferController@getOfferDetail'));
    });
});

