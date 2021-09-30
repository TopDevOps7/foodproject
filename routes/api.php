<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});



Route::group(['as' => 'api.', 'namespace' => 'API'], function () {

    Route::post('login', 'AuthController@login')->name('api.login');
    Route::post('register', 'AuthController@register')->name('api.register');

    Route::get('setting_contacts', 'HomePageController@setting_contacts')->name('api.setting_contacts');
    Route::get('setting', 'HomePageController@setting')->name('api.setting');

    Route::get('category', 'HomePageController@category')->name('api.category');
    Route::get('sub_category', 'HomePageController@sub_category')->name('api.sub_category');
    Route::get('city', 'HomePageController@city')->name('api.city');
    Route::post('offers', 'HomePageController@offers')->name('api.city');

    Route::post('get-restaurant-category', 'HomePageController@restaurant')->name('api.restaurant');
    Route::get('get-restaurant-api', 'RestaurantApiController@GetRestaurant')->name('api.restaurant');
    Route::get('get-restaurant-category/{id}', 'RestaurantApiController@GetRestaurantCategory')->name('api.restaurant');
    Route::get('get-product-api/{id}', 'RestaurantApiController@GetProduct')->name('api.restaurant');
    Route::post('search-product', 'RestaurantApiController@GetProductSearch')->name('api.restaurant');
    // Zita added
    Route::post('search-restaurant', 'RestaurantApiController@SearchRestaurant')->name('api.restaurant');
    // Zita added
    Route::get('get-verify-sms/{phone}', 'RestaurantApiController@GetVerifySMS')->name('api.restaurant');
    Route::post('restaurant_view', 'HomePageController@restaurant_view')->name('api.restaurant_view');

    Route::post('store-order-information', 'HomePageController@cart_save')->name('api.cart_save');
    Route::post('store-order-payment', 'HomePageController@payment_type')->name('api.payment_type');
    Route::post('store-order-address', 'HomePageController@cart_save_address')->name('api.cart_save_address');
    Route::post('send-verify-sms', 'HomePageController@complete_order')->name('api.complete_order');
    Route::post('check-verifycode', 'HomePageController@complete_order_verfiy')->name('api.complete_order_verfiy');
    Route::post('restaurant_comment', 'HomePageController@restaurant_comment')->name('api.restaurant_comment');
    Route::post('get-client-comment', 'HomePageController@client_comment')->name('api.client_comment');
    Route::post('store-pos-order', 'HomePageController@createPosOrder');

    Route::group(['prefix' => 'secret-api-not-allow-test'], function () {
        Route::post('reset-product-avatar/{product_id}', 'ProductController@resetProductAvatar');
        Route::post('delete-product/{product_id}', 'ProductController@deleteProduct');
    });
});
