<?php
namespace Monelytics\Controllers\User;

use Auth;
use Input;
use URL;
use Redirect;
use Route;
use View;

use OAuth;

use Monelytics\Controllers;
use Monelytics\Services;
use Monelytics\Models;

class SessionController extends ApplicationController {
  protected $required_auth = true;
  private $user;

  public function __construct(Services\UserService $user)
  {
    $this->user = $user;

    $action_name = Route::getCurrentRoute()->getActionName();
    $action_name = substr($action_name, strpos($action_name, '@') + 1);
    $expect = array(
      'getLogin',
      'postLogin',
      'postLogin',
      'loginOAuth',
      'loginOAuthCallback',
    );

    $this->beforeFilter(function() {
      if (Auth::user()) {
        return Redirect::to('dashboard');
      }

    }, array('only' => $expect));

    if (in_array($action_name, $expect)) {
      $this->required_auth = false;
    }

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
    $errors = array();

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
    $params = array(
      'code' => Input::get('code')
    );
    $errors = array();

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
