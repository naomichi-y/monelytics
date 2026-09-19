<?php
namespace App\Http\Controllers\User;

use Auth;
use Request;
use Redirect;
use Route;
use View;

use App\Services;

class SessionController extends \App\Http\Controllers\Controller {
    private $user;

    public function __construct(Services\UserService $user)
    {
        $this->user = $user;
        $this->middleware('guest', [
            'only' => [
                'getLogin',
                'postLogin',
            ]
        ]);
        $this->middleware('auth', ['only' => 'logout']);

        parent::__construct();
    }

    /**
     * ログインページを表示する。
     */
    public function getLogin()
    {
        return View::make('user/session/login');
    }

    /**
     * システムにログインする。
     */
    public function postLogin()
    {
        $email = Request::input('email');
        $password = Request::input('password');
        $remember_me = (bool) Request::input('remember_me');
        $errors = [];

        $user = $this->user->login($email, $password, $remember_me, $errors);

        if (!$user) {
            return Redirect::to('/user/login')
                ->withErrors($errors)
                ->withInput();
        }

        return Redirect::intended('dashboard');
    }

    /**
     * システムからログアウトする。
     */
    public function logout()
    {
        $this->user->logout();

        return Redirect::route('home');
    }
}
