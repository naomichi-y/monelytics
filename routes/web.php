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
    // 総当たりを抑える。失敗も成功も同じ IP で数える。
    $route->post('login', 'SessionController@postLogin')->middleware('throttle:' . config('app.rate_limits.login'));
    // POST にしてあるのは、GET だと <img src="/user/logout"> を踏ませるだけで
    // 他人をログアウトさせられるため。副作用のある操作は CSRF の検証が
    // 走る側へ置く。
    $route->post('logout', 'SessionController@logout');

    $route->get('done', 'RegistrationController@done');
    $route->put('update', 'RegistrationController@update');
    $route->post('withdrawal', 'RegistrationController@withdrawal');

    // 登録フォームに CAPTCHA がなく、同一 IP から際限なく作成できていた。
    $route->resource('', 'RegistrationController', ['only' => ['index', 'create', 'store']])
        ->middlewareFor('store', 'throttle:' . config('app.rate_limits.registration'));
});

Route::group(['prefix' => 'contact'], function($route) {
    // 問い合わせも同様に無制限だった。
    $route->post('send', 'ContactController@send')->middleware('throttle:' . config('app.rate_limits.contact'));
    $route->get('done', 'ContactController@done');
});
Route::resource('contact', 'ContactController', ['only' => ['index']]);

Route::group(['middleware' => 'auth'], function() {
    Route::resource('dashboard', 'DashboardController');

    // 日付入力のカレンダーが祝日の色付けに読む。外へ取りに行く処理を抱える
    // ため、画面と同じく認証済みにだけ開ける。
    Route::get('holidays', 'HolidayController@index');

    Route::group(['prefix' => 'gadget'], function($route) {
        $route->get('variable-expense', 'GadgetController@variableExpense');
        $route->get('activity-history', 'GadgetController@activityHistory');
    });

    Route::group(['namespace' => 'Cost', 'prefix' => 'cost'], function($route) {
        // どちらも一覧 (index) と単体表示 (show) の画面を持たない。既定の
        // resource はその 2 つもルートに載せてしまい、直接開くと
        // BadMethodCallException で 500 になる。実装のある動作だけを登録する。
        $route->resource('variable', 'VariableController', [
            'only' => ['create', 'store', 'edit', 'update', 'destroy']
        ]);
        $route->resource('constant', 'ConstantController', [
            'only' => ['create', 'store', 'destroy']
        ]);
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
            $route->get('line-chart', 'YearlyController@lineChart');
            $route->get('line-chart-data', 'YearlyController@lineChartData');
        });
        $route->resource('yearly', 'YearlyController', ['only' => ['index']]);
    });

    Route::group(['namespace' => 'Settings', 'prefix' => 'settings'], function($route) {
        $route->group(['prefix' => 'activityCategory'], function($route) {
            $route->post('sort', 'ActivityCategoryController@sort');
        });
        $route->resource('activityCategory', 'ActivityCategoryController');

        $route->group(['prefix' => 'activityCategoryItem'], function($route) {
            $route->post('sort', 'ActivityCategoryItemController@sort');
        });
        $route->resource('activityCategoryItem', 'ActivityCategoryItemController');
    });
});
