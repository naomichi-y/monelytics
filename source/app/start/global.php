<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
	app_path().'/models',
	app_path().'/database/seeds',
	app_path().'/services',
	app_path().'/libraries',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

Log::useDailyFiles(storage_path() . '/logs/laravel.log');
//Log::useFiles(storage_path().'/logs/laravel.log');

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Exception $exception, $code)
{
  Log::error($exception);
  ob_clean();

  // デバッグモードが無効な場合 (本番環境)
  if (!Config::get('app.debug')) {
    // デバッグが無効モード時はシステムエラーページを出力
    return Response::view('errors.500', array('exception' => $exception), '500');
  }
});

App::error(function(Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception, $code)
  {
  Log::warning($exception);
  ob_clean();

  $view = null;

  switch ($code) {
    case 404:
      $view = Response::view('errors.404', array('exception' => $exception), '404');
      break;

    default:
      $view = Response::view('errors.500', array('exception' => $exception), '500');
      break;
  }

  return $view;
});

/**
 * ログリスナーの登録。
 *
 * @param string $level
 * @param mixed $message
 * @param array $context
 */
Log::listen(function($level, $message, $context) {
  // エラーメールの送信
  if ($level === 'error' && is_a($message, 'Exception') && !Config::get('app.debug')) {
    $exception = $message;

    $data = array();
    $data['exception'] = $exception;

    Mail::send(array('text' => 'emails/error'), $data, function($message) use ($exception) {
      $subject = '[ERROR] ' . $exception->getMessage();
      $message->to(Config::get('app.email.alert'))
        ->subject($subject);
    });
  }
});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';
require app_path().'/macros.php';
require app_path().'/validators.php';
