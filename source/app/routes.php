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

Route::get('/', array('uses' => 'Monelytics\Controllers\IndexController@getIndex', 'as' => 'home'));

Route::group(array('prefix' => 'user'), function($route) {
  $route->get('login', 'Monelytics\Controllers\User\SessionController@getLogin');
  $route->post('login-oauth', 'Monelytics\Controllers\User\SessionController@loginOAuth');
  $route->get('login-oauth-callback', 'Monelytics\Controllers\User\SessionController@loginOAuthCallback');
  $route->post('login', 'Monelytics\Controllers\User\SessionController@postLogin');
  $route->get('logout', 'Monelytics\Controllers\User\SessionController@logout');

  $route->get('done', 'Monelytics\Controllers\User\RegistrationController@done');
  $route->post('create-oauth', 'Monelytics\Controllers\User\RegistrationController@createOAuth');
  $route->get('create-oauth-callback', 'Monelytics\Controllers\User\RegistrationController@createOAuthCallback');
  $route->put('update', 'Monelytics\Controllers\User\RegistrationController@update');
  $route->post('withdrawal', 'Monelytics\Controllers\User\RegistrationController@withdrawal');

  $route->resource('', 'Monelytics\Controllers\User\RegistrationController', array('only' => array('index', 'create', 'store')));
});

Route::resource('dashboard', 'Monelytics\Controllers\DashboardController');

Route::group(array('prefix' => 'contact'), function($route) {
  $route->post('send', 'Monelytics\Controllers\ContactController@send');
  $route->get('done', 'Monelytics\Controllers\ContactController@done');
});
Route::resource('contact', 'Monelytics\Controllers\ContactController', array('only' => array('index')));

Route::group(array('prefix' => 'gadget'), function($route) {
  $route->get('activity-status', 'Monelytics\Controllers\GadgetController@activityStatus');
  $route->get('activity-graph', 'Monelytics\Controllers\GadgetController@activityGraph');
  $route->get('activity-history', 'Monelytics\Controllers\GadgetController@activityHistory');
});

Route::group(array('prefix' => 'cost'), function($route) {
  $route->resource('variable', 'Monelytics\Controllers\Cost\VariableController');
  $route->resource('constant', 'Monelytics\Controllers\Cost\ConstantController');
});

Route::group(array('prefix' => 'summary'), function($route) {
  $route->group(array('prefix' => 'daily'), function($route) {
    $route->get('condition', 'Monelytics\Controllers\Summary\DailyController@condition');
  });
  $route->resource('daily', 'Monelytics\Controllers\Summary\DailyController', array('only' => array('index')));

  Route::group(array('prefix' => 'monthly'), function($route) {
    $route->get('condition', 'Monelytics\Controllers\Summary\MonthlyController@condition');
    $route->get('report', 'Monelytics\Controllers\Summary\MonthlyController@report');
    $route->get('calendar', 'Monelytics\Controllers\Summary\MonthlyController@calendar');
    $route->get('pie-chart', 'Monelytics\Controllers\Summary\MonthlyController@pieChart');
    $route->get('pie-chart-data', 'Monelytics\Controllers\Summary\MonthlyController@pieChartData');
    $route->get('ranking', 'Monelytics\Controllers\Summary\MonthlyController@ranking');
  });
  $route->resource('monthly', 'Monelytics\Controllers\Summary\MonthlyController');

  $route->group(array('prefix' => 'yearly'), function($route) {
    $route->get('report', 'Monelytics\Controllers\Summary\YearlyController@report');
    $route->get('condition', 'Monelytics\Controllers\Summary\YearlyController@condition');
  });
  $route->resource('yearly', 'Monelytics\Controllers\Summary\YearlyController');
});

Route::group(array('prefix' => 'settings'), function($route) {
  $route->group(array('prefix' => 'activityCategory'), function($route) {
    $route->post('sort', 'Monelytics\Controllers\Settings\ActivityCategoryController@sort');
  });
  $route->resource('activityCategory', 'Monelytics\Controllers\Settings\ActivityCategoryController');

  $route->group(array('prefix' => 'activityCategoryGroup'), function($route) {
    $route->post('sort', 'Monelytics\Controllers\Settings\ActivityCategoryGroupController@sort');
  });
  $route->resource('activityCategoryGroup', 'Monelytics\Controllers\Settings\ActivityCategoryGroupController');
});
