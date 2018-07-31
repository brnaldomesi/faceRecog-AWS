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
	return redirect('/home');
})->name('root');


//Auth::routes();
// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegisterForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');

Route::group(['middleware' => ['auth']], function () {

	// Password Reset Routes...
	Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
	Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
	Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
	Route::post('password/reset', 'Auth\ResetPasswordController@reset');

	Route::group(['middleware' => ['admin']], function () {
		Route::get('/admin', 'AdminController@index')->name('admin');
		Route::get('/admin/{user}', 'AdminController@user')->name('admin.id.show');
		Route::put('/admin/{user}', 'AdminController@update')->name('admin.id.update');
	});

	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('/portraits/create', 'PortraitsController@create');
	Route::get('/portraits', 'PortraitsController@index');
	Route::post('/portraits', 'PortraitsController@store');
	Route::post('/portraits/search', 'PortraitsController@search')->name('search.file');

	//Case

	Route::get('/cases', 'CaseController@index')->name('cases.show');

	Route::group(['middleware' => ['can:create,App\Models\Cases']], function () {
		Route::post('/cases', 'CaseController@create')->name('cases.post');
		Route::get('/cases/create', 'CaseController@createForm')->name('cases.create.show');
	});

	Route::group(['middleware' => ['can:update,cases']], function () {
		Route::put('/cases/{cases}', 'CaseController@update')->name('cases.id.update');
		Route::post('/cases/{cases}/addimage', 'CaseController@addImage')->name('cases.id.image.add');
		Route::get('/cases/{cases}', 'CaseController@cases')->name('cases.id.show');
		Route::post('/cases/{cases}/imagelist', 'CaseController@imagelist')->name('cases.id.image.show');
	});

	Route::post('/cases/search', 'CaseController@search')->name('search.case');
	Route::post('/cases/search-history', 'CaseController@searchHistory')->name('search.history');
});
