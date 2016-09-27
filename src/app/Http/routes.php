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

Route::get('/', ['uses' => 'IndexController@getIndex', 'as' => 'home']);

Route::group(['namespace' => 'User', 'prefix' => 'user'], function($route) {
  $route->get('login', 'SessionController@getLogin');
  $route->post('login-oauth', 'SessionController@loginOAuth');
  $route->get('login-oauth-callback', 'SessionController@loginOAuthCallback');
  $route->post('login', 'SessionController@postLogin');
  $route->get('logout', 'SessionController@logout');

  $route->get('done', 'RegistrationController@done');
  $route->post('create-oauth', 'RegistrationController@createOAuth');
  $route->get('create-oauth-callback', 'RegistrationController@createOAuthCallback');
  $route->put('update', 'RegistrationController@update');
  $route->post('withdrawal', 'RegistrationController@withdrawal');

  $route->resource('', 'RegistrationController', ['only' => ['index', 'create', 'store']]);
});

Route::group(['prefix' => 'contact'], function($route) {
  $route->post('send', 'ContactController@send');
  $route->get('done', 'ContactController@done');
});
Route::resource('contact', 'ContactController', ['only' => ['index']]);

Route::group(['middleware' => 'auth'], function() {
  Route::resource('dashboard', 'DashboardController');

  Route::group(['prefix' => 'gadget'], function($route) {
    $route->get('activity-status', 'GadgetController@activityStatus');
    $route->get('activity-graph', 'GadgetController@activityGraph');
    $route->get('activity-history', 'GadgetController@activityHistory');
  });

  Route::group(['namespace' => 'Cost', 'prefix' => 'cost'], function($route) {
    $route->resource('variable', 'VariableController');
    $route->resource('constant', 'ConstantController');
  });

  Route::group(['namespace' => 'Summary', 'prefix' => 'summary'], function($route) {
    $route->group(['prefix' => 'daily'], function($route) {
      $route->get('condition', 'DailyController@condition');
    });
    $route->resource('daily', 'DailyController', ['only' => ['index']]);

    Route::group(['prefix' => 'monthly'], function($route) {
      $route->get('condition', 'MonthlyController@condition');
      $route->get('report', 'MonthlyController@report');
      $route->get('calendar', 'MonthlyController@calendar');
      $route->get('pie-chart', 'MonthlyController@pieChart');
      $route->get('pie-chart-data', 'MonthlyController@pieChartData');
      $route->get('ranking', 'MonthlyController@ranking');
    });
    $route->resource('monthly', 'MonthlyController', ['only' => ['index']]);

    $route->group(['prefix' => 'yearly'], function($route) {
      $route->get('report', 'YearlyController@report');
      $route->get('condition', 'YearlyController@condition');
    });
    $route->resource('yearly', 'YearlyController', ['only' => ['index']]);
  });

  Route::group(['namespace' => 'Settings', 'prefix' => 'settings'], function($route) {
    $route->group(['prefix' => 'activityCategory'], function($route) {
      $route->post('sort', 'ActivityCategoryController@sort');
    });
    $route->resource('activityCategory', 'ActivityCategoryController');

    $route->group(['prefix' => 'activityCategoryGroup'], function($route) {
      $route->post('sort', 'ActivityCategoryGroupController@sort');
    });
    $route->resource('activityCategoryGroup', 'ActivityCategoryGroupController');
  });
});
