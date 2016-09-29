<?php
namespace App\Http\Controllers\User;

use Auth;
use Input;
use URL;
use Redirect;
use Route;
use View;

use OAuth;

use App\Services;
use App\Models;

class SessionController extends \App\Http\Controllers\Controller {
    private $user;

    public function __construct(Services\UserService $user)
    {
        $this->user = $user;
        $this->middleware('guest', [
            'only' => [
                'getLogin',
                'postLogin',
                'loginOAuth',
                'loginOAuthCallback',
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
        $email = Input::get('email');
        $password = Input::get('password');
        $remember_me = (bool) Input::get('remember_me');
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
     * OAuthでシステムにログインする。
     */
    public function loginOAuth()
    {
        return Redirect::to((string) OAuth::consumer('Facebook', URL::to('user/login-oauth-callback'))->getAuthorizationUri());
    }

    /**
     * OAuthでシステムにログインする。(コールバックパス)
     */
    public function loginOAuthCallback()
    {
        $params = [
            'code' => Input::get('code')
        ];
        $errors = [];

        if (!$this->user->loginOAuth(Models\UserCredential::CREDENTIAL_TYPE_FACEBOOK, $params, $errors)) {
            return Redirect::to('user/login')
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
