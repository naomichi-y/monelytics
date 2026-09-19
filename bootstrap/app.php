<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        // ルートは 'IndexController@getIndex' 形式で書かれている。Laravel 8 で
        // 自動の名前空間補完が外れたため、ここで明示して従来の記法を維持する。
        using: function (): void {
            Route::middleware('web')
                ->namespace('App\\Http\\Controllers')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 認証済みの利用者を /dashboard へ送る独自実装。Laravel 既定の
        // RedirectIfAuthenticated はリダイレクト先が異なるため差し替える。
        $middleware->alias([
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);

        // 既定では login という名前のルートへ飛ばそうとするが、このアプリの
        // ログインページは名前を持たないため遷移先を直接指定する。
        $middleware->redirectGuestsTo('/user/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
