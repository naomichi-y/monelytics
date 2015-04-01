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

Route::get('/', 'IndexController@getLogin');
Route::controller('/user', 'UserController');
Route::controller('/home', 'HomeController');
Route::controller('/contact', 'ContactController');
Route::controller('/gadget', 'GadgetController');
Route::controller('/variableCost', 'VariableCostController');
Route::controller('/constantCost', 'ConstantCostController');
Route::controller('/dailySummary', 'DailySummaryController');
Route::controller('/monthlySummary', 'MonthlySummaryController');
Route::controller('/yearlySummary', 'YearlySummaryController');
Route::controller('/activityCategory', 'ActivityCategoryController');
Route::controller('/activityCategoryGroup', 'ActivityCategoryGroupController');

