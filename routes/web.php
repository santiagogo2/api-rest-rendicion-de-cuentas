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

Route::get('/', function () {
    return view('welcome');
});

Route::resource('/api/user', 'UserController')->middleware('api-auth');
Route::post('/api/user/login','UserController@login');
Route::resource('/api/suggestions', 'SuggestionController');
Route::resource('/api/locations', 'LocationController');
