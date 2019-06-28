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

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

//
Route::group(['middleware' => ['authen']], function () {

	Route::group(['middleware' => ['admin']], function () {
		Route::get('/admin', 'AdminController@index')->name('admin');
		Route::post('/admin', 'AdminController@create')->name('admin.create');
		Route::get('/admin/manageusers', 'AdminController@manageUsers')->name('admin.manageusers.show');
		Route::get('/admin/activity','AdminController@activityLog')->name('admin.activity.show');
		Route::get('/admin/sharing','AdminController@sharingForm')->name('admin.sharing.show');
		Route::put('/admin/sharing','AdminController@sharing')->name('admin.sharing.update');
		//Route::get('/admin/sharing/edit/{organization}','AdminController@sharingedit')->name('admin.sharingedit.show');
		Route::get('/admin/create', 'AdminController@createForm')->name('admin.create.show');
		Route::get('/admin/{user}', 'AdminController@user')->name('admin.id.show');
		Route::put('/admin/{user}', 'AdminController@update')->name('admin.id.update');
		Route::delete('/admin/{user}', 'AdminController@delete')->name('admin.id.delete');
	});

	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('/portraits/create', 'PortraitsController@create')->name('portrait.new');
	Route::get('/portraits', 'PortraitsController@index');
	Route::post('/portraits', 'PortraitsController@store');
	Route::post('/portraits/search', 'PortraitsController@search')->name('search.file');

	// Case
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
    Route::post('/cases/getDetailFaceInfo','CaseController@getDetailFaceInfo')->name('search.detailfaceinfo');
	Route::post('/cases/getPersonGallery','CaseController@getPersonGallery')->name('search.persongallery');

	// Compare
	Route::get('/compare', 'CompareController@index')->name('compare.index');
	Route::post('/compare/create', 'CompareController@compare')->name('compare.create');
	Route::post('/compare/history', 'CompareController@history')->name('compare.history');
	Route::post('/compare/save', 'CompareController@save')->name('compare.save');

	// AWS test
	Route::get('/awstest', 'TestController@index')->name('aws.test');
	Route::post('/awstest_createCollection', 'TestController@createCollection');
	Route::post('/awstest_faceindexing', 'TestController@awstestFaceindexing');
	Route::post('/awstest_searchface', 'TestController@awstestSearchface');
	Route::post('/awstest_delete_face', 'TestController@awstestDeleteFace');
	
	// Support
	Route::get('/support', 'SupportController@index');
	
	// Organization Searches (SuperAdmin)
	Route::group(['middleware' => ['superadmin']], function() {
		Route::group(['middleware' => ['can:create,App\Models\Organization']], function () {
			Route::get('/organizations', 'SuperAdmin\OrganizationController@index')->name('organization');
			Route::get('/organizations/new', 'SuperAdmin\OrganizationController@new')->name('organization.new');
			Route::post('/organizations', 'SuperAdmin\OrganizationController@create')->name('organization.create');
		});

		Route::get('/allcases','SuperAdmin\AllCasesController@index')->name('allcases.show');
		Route::get('/allcases/{org}', 'SuperAdmin\AllCasesController@orgIndex')->name('allcases.org.show');
		Route::get('/allcases/{org}/{cases}', 'SuperAdmin\AllCasesController@cases')->name('allcases.id.show');
		Route::post('/allcases/{org}/{cases}/imagelist', 'SuperAdmin\AllCasesController@imagelist')->name('allcases.id.image.show');

		Route::get('/faces','SuperAdmin\FaceController@index')->name('faces.index');
		Route::post('/faces/importcsv','SuperAdmin\FaceController@importCSV')->name('faces.importcsv');
		Route::post('/faces/enrollphoto','SuperAdmin\FaceController@enrollPhoto')->name('faces.enrollphoto');
		Route::post('/faces/searchimage','SuperAdmin\FaceController@searchImage')->name('faces.searchimage');
		Route::post('/faces/removeface','SuperAdmin\FaceController@removeFace')->name('faces.removeface');
	});
});
