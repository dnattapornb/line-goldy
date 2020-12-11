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


Route::get('/', 'DefaultController@index')->name('default.index');
Route::get('/sheet', 'DefaultController@sheet')->name('default.sheet');
Route::get('/collection', 'DefaultController@collection')->name('default.collection');

Route::get('/user', function () {
    return view('user/index');
});
Route::get('/users', 'UserController@index')->name('users.index');
Route::put('/users', 'UserController@update')->name('users.update');

Route::post('/bot', 'LineBotController@index')->name('line.bot.index');