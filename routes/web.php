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

$app->post('login',array('uses' => 'Auth\LoginController@login'));
$app->post('register',array('uses' => 'Auth\RegisterController@register'));
$app->post('getOtp',array('uses' => 'Auth\OtpVerificationController@getOtp'));
$app->post('verifyOtp',array('uses' => 'Auth\OtpVerificationController@verifyOtp'));

$app->group(['prefix' => 'customer'], function () use($app){
    $app->group(['prefix' => 'location'], function () use($app){
        $app->post('get',array('uses' => 'Customer\CustomerController@getLocation'));
        $app->post('set',array('uses' => 'Customer\CustomerController@setLocation'));
    });

    $app->group(['prefix' => 'group'], function () use($app){
        $app->post('list',array('uses' => 'Customer\GroupController@getGroupList'));
        $app->post('offers',array('uses' => 'Customer\GroupController@getGroupOffers'));
        $app->post('remove',array('uses' => 'Customer\GroupController@leaveGroup'));
    });
    $app->group(['prefix' => 'wishlist'], function () use($app){

        $app->post('offers',array('uses' => 'Customer\WishListController@getWishListOffers'));
        $app->post('remove',array('uses' => 'Customer\WishListController@removeWishList'));
        $app->post('add',array('uses' => 'Customer\WishListController@addWishList'));
    });
    $app->group(['prefix' => 'interested'], function () use($app){
        $app->group(['prefix' => 'offer'], function () use($app){
            $app->post('list',array('uses' => 'Customer\InterestedOfferController@getInterestedOffers'));
            $app->post('add',array('uses' => 'Customer\InterestedOfferController@setInterestedOffers'));
            $app->post('detail',array('uses' => 'Customer\OfferDetailController@getInterestedOfferDetail'));
            $app->post('offerimages',array('uses' => 'Customer\OfferDetailController@getOfferImages'));
        });
    });

});

$app->group(['prefix' => 'seller'], function () use($app){
    $app->group(['prefix' => 'offer'], function () use($app){
        $app->post('listing', array('uses' => 'Seller\OfferController@getOfferListing'));
        $app->post('detail',array('uses' => 'Seller\OfferController@getOfferDetail'));
    });
});

