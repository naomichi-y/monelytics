<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

#Event::listen('illuminate.query', function($sql){
#  var_dump($sql);
#});

Route::get('/', array('uses' => 'IndexController@getIndex', 'as' => 'home'));

Route::controller('user', 'UserController');
Route::controller('dashboard', 'DashboardController');
Route::controller('contact', 'ContactController');
Route::controller('gadget', 'GadgetController');

Route::group(array('prefix' => 'cost'), function() {
  Route::controller('variable', 'VariableController');
  Route::controller('constant', 'ConstantController');
});

Route::group(array('prefix' => 'summary'), function() {
  Route::controller('daily', 'DailyController');
  Route::controller('monthly', 'MonthlyController');
  Route::controller('yearly', 'YearlyController');
});

Route::group(array('prefix' => 'settings'), function() {
  Route::controller('activityCategory', 'ActivityCategoryController');
  Route::controller('activityCategoryGroup', 'ActivityCategoryGroupController');
});
