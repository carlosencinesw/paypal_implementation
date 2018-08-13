<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', 'PaymentController@index')->name('main');
// route for processing payment
Route::post('paypal', 'PaymentController@pay')->name('paypal');
// route for check status of the payment
Route::get('status', 'PaymentController@getStatus')->name('status');
