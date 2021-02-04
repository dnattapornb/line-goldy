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

Route::post('/bot', 'LineBotController@index')->name('line.bot.index');

Route::get('/user', function () {
    return view('user/index');
});

Route::resource('users', UserController::class)->only([
    'index', 'show', 'update', 'destroy'
]);
Route::get('/test', 'UserController@test')->name('test');

Route::get('/novel', 'NovelController@index')->name('novel.gets');
Route::get('/novel/{novel}', 'NovelController@eBookAdaptor')->name('novel.ebook');
Route::get('/file', 'NovelController@file')->name('novel.files');