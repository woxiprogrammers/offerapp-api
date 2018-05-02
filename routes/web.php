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

    $app->group(['prefix' => 'offer'], function () use($app){
        $app->post('detail',array('uses' => 'Customer\OfferController@getCustomerOfferDetail'));

        $app->group(['prefix' => 'reach_in_time'], function () use($app) {
            $app->get('listing', array('uses' => 'Customer\OfferController@getReachInTime'));
        });
        
        $app->group(['prefix' => 'category'], function () use($app) {
            $app->get('listing', array('uses' => 'Customer\OfferController@getCategory'));
        });
        $app->group(['prefix' => 'types'], function () use($app) {
            $app->get('listing', array('uses' => 'Customer\OfferController@getOfferType'));
        });

        $app->group(['prefix' => 'trending'], function () use($app) {
            $app->post('listing',array('uses' => 'Customer\OfferController@swipperOffer'));
        });

        $app->group(['prefix' => 'nearby'], function () use($app) {
            $app->post('listing',array('uses' => 'Customer\OfferController@nearByOffer'));
        });
        $app->group(['prefix' => 'wishlist'], function () use($app){
            $app->post('listing',array('uses' => 'Customer\OfferController@offerListing'));
            $app->post('add',array('uses' => 'Customer\OfferController@addToWishlist'));
            $app->post('remove',array('uses' => 'Customer\OfferController@removeFromWishlist'));
        });
        $app->group(['prefix' => 'interested'], function () use($app){

            $app->post('listing',array('uses' => 'Customer\OfferController@offerListing'));
            //$app->post('detail',array('uses' => 'Customer\OfferController@getInterestedOfferDetail'));

            $app->post('add',array('uses' => 'Customer\OfferController@addToInterest'));

        });

        $app->group(['prefix' => 'map'], function () use($app) {
            $app->post('listing',array('uses' => 'Customer\OfferController@mapOffers'));
        });

        $app->group(['prefix' => 'AR'], function () use($app) {
            $app->post('seller_info', array('uses' => 'Customer\OfferController@AROffers'));
            $app->get('listing', array('uses' => 'Customer\OfferController@getReachInTime'));
        });
    });

    $app->group(['prefix' => 'group'], function () use($app){
        $app->get('list',array('uses' => 'Customer\GroupController@getGroupList'));
        $app->post('offers',array('uses' => 'Customer\GroupController@getGroupOffers'));
        $app->post('remove',array('uses' => 'Customer\GroupController@leaveGroup'));
    });
});

$app->group(['prefix' => 'seller'], function () use($app){
        $app->group(['prefix' => 'account'], function () use($app) {
            $app->get('info',array('uses' => 'Seller\SellerController@getAccountInfo'));
            $app->post('edit',array('uses' => 'Seller\SellerController@editAccountInfo'));
        });
        $app->group(['prefix' => 'address'], function ()use($app){
            $app->group(['prefix' => 'floors'], function () use($app) {
                $app->get('listing', array('uses' => 'Customer\OfferController@getFloor'));
            });
            $app->post('add',array('uses' => 'Seller\SellerController@addSellerAddress'));
            $app->post('update',array('uses' => 'Seller\SellerController@updateSellerAddress'));

        });
        $app->group(['prefix' => 'category'], function () use($app) {
            $app->get('main', array('uses' => 'Seller\CategoryController@getMainCategory'));
            $app->post('sub', array('uses' => 'Seller\CategoryController@getSubCategory'));
         });
        $app->group(['prefix' => 'offer'], function () use($app){
            $app->get('type',array('uses' => 'Seller\OfferController@getOfferType'));
            $app->post('create', array('uses' => 'Seller\OfferController@createOffer'));
            $app->post('listing', array('uses' => 'Seller\OfferController@getOfferListing'));
            $app->post('detail',array('uses' => 'Seller\OfferController@getOfferDetail'));
        });
        $app->group(['prefix' => 'group'], function () use($app) {
            $app->get('list', array('uses' => 'Seller\GroupController@getGroupList'));
            $app->post('add-member', array('uses' => 'Seller\GroupController@addMemberToGroup'));
            $app->post('detail', array('uses' => 'Seller\GroupController@getGroupDetail'));
            $app->post('offers', array('uses' => 'Seller\GroupController@groupOfferListing'));
            $app->post('create', array('uses' => 'Seller\GroupController@createGroup'));
            $app->post('promote', array('uses' => 'Seller\GroupController@promoteOffer'));

        });
});

$app->post('save-image',array('uses' => 'ImageController@image'));

$app->post('getdistance',array('uses' => 'Customer\OfferController@getDistanceByGoogleApi'));
