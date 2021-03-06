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

Route::middleware([ 'bindings', 'auth', 'can:base.admin.access' ])->prefix('admin')->name('site_types.')->group(function () {
  // Admin SiteTypes
  Route::prefix('site-types')->name('admin.')->group(function () {
    Route::get('/', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@index')->middleware([ 'can:site_types.admin.index' ])->name('index');
    Route::get('/create', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@create')->middleware([ 'can:site_types.admin.create' ])->name('create');
    Route::post('/create', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@store')->middleware([ 'can:site_types.admin.create' ])->name('store');
    Route::get('/edit/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@edit')->middleware([ 'can:site_types.admin.edit' ])->name('edit');
    Route::post('/edit/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@update')->middleware([ 'can:site_types.admin.edit' ])->name('update');
    Route::get('/delete/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@destroy')->middleware([ 'can:site_types.admin.delete' ])->name('delete');
    Route::get('/show/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@show')->middleware([ 'can:site_types.admin.show' ])->name('show');
    Route::get('/activate/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@showActivate')->middleware([ 'can:site_types.admin.activate' ])->name('show_activate');
    Route::post('/activate/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@postActivate')->middleware([ 'can:site_types.admin.activate' ])->name('post_activate');
    Route::post('/install/{slug}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@install')->middleware([ 'can:site_types.admin.install' ])->name('install');
    Route::get('/search', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@search')->middleware([ 'can:site_types.admin.search' ])->name('search');
    Route::get('/settings/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@showSettings')->middleware([ 'can:site_types.admin.settings' ])->name('show_settings');
    Route::post('/settings/{siteType:id}', '\Adaptcms\SiteTypes\Http\Controllers\Admin\SiteTypesController@postSettings')->middleware([ 'can:site_types.admin.settings' ])->name('post_settings');
  });
});
