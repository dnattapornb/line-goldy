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

/* Test */
Route::get('/', 'DefaultController@index')->name('default.index');
Route::get('/sheet', 'DefaultController@sheet')->name('default.sheet');
Route::get('/collection', 'DefaultController@collection')->name('default.collection');
Route::get('/party', 'DefaultController@party')->name('default.party');
Route::get('/regx', 'DefaultController@regx')->name('default.string');

/* Line Bot */
Route::post('/bot', 'LineBotController@index')->name('line.bot.index');

/* User Line */
Route::get('/user', function () {
    return view('user/index');
});

Route::resource('users', UserController::class)->only([
    'index', 'show', 'update', 'destroy'
]);

Route::get('/test', 'UserController@test')->name('test');

/* Novel */
Route::get('/novel', 'NovelController@index')->name('novel.gets');
Route::get('/novel/{code}', 'NovelController@show')->name('novel.ebook');
Route::get('/file', 'NovelController@file')->name('novel.files');