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

    $app->group(['prefix' => 'offer'], function () use($app){
        $app->group(['prefix' => 'wishlist'], function () use($app){
            $app->post('listing',array('uses' => 'Customer\OfferController@offerListing'));
            $app->post('add',array('uses' => 'Customer\OfferController@addToWishlist'));
            $app->post('remove',array('uses' => 'Customer\OfferController@removeFromWishlist'));
        });
        $app->group(['prefix' => 'interested'], function () use($app){
            $app->post('listing',array('uses' => 'Customer\OfferController@offerListing'));
            $app->post('detail',array('uses' => 'Customer\OfferController@getInterestedOfferDetail'));
            $app->post('add',array('uses' => 'Customer\OfferController@addToInterest'));

        });
    });
});

    $app->group(['prefix' => 'seller'], function () use($app){
        $app->group(['prefix' => 'offer'], function () use($app){
            $app->get('type',array('uses' => 'Seller\OfferController@getOfferType'));
           // $app->get('select',array('uses' => 'Seller\OfferController@selectOffer'));
            $app->post('create', array('uses' => 'Seller\OfferController@createOffer'));
            $app->post('listing', array('uses' => 'Seller\OfferController@getOfferListing'));
            $app->post('detail',array('uses' => 'Seller\OfferController@getOfferDetail'));
        });
        $app->group(['prefix' => 'group'], function () use($app){
            $app->get('list',array('uses' => 'Seller\GroupController@getGroupList'));
            $app->post('add-member',array('uses' => 'Seller\GroupController@addMemberToGroup'));
            $app->post('detail',array('uses' => 'Seller\GroupController@getGroupDetail'));
            $app->post('offers',array('uses' => 'Seller\GroupController@groupOfferListing'));
            $app->post('create',array('uses' => 'Seller\GroupController@createGroup'));
        });
    });

    $app->group(['prefix' => 'category'], function () use($app){
        $app->get('main',array('uses' => 'Seller\CategoryController@getMainCategory'));
        $app->post('sub',array('uses' => 'Seller\CategoryController@getSubCategory'));

    });
    $app->post('save-image',array('uses' => 'ImageController@image'));



