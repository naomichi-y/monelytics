<?php
namespace App\Http\Controllers\User;

use Auth;
use Input;
use Lang;
use Redirect;
use Route;
use URL;
use View;

use OAuth;

use App\Controllers;
use App\Libraries\Condition;
use App\Models;
use App\Services;

class RegistrationController extends \App\Http\Controllers\ApplicationController {
  private $user;

  public function __construct(Services\UserService $user)
  {
    $this->user = $user;
    $this->middleware('guest', [
      'only' => [
        'create',
        'createOAuth',
        'createOAuthCallback',
        'store'
      ]
    ]);

    $this->middleware('auth', [
      'only' => [
        'done',
        'index',
        'edit',
        'update',
        'withdrawal'
      ]
    ]);

    parent::__construct();
  }

   /**
   * 会員情報を表示する。
   */
  public function index()
  {
    return $this->edit();
  }

  /**
   * 会員登録ページを表示する。
   */
  public function create()
  {
    return View::make('user/registration/create');
  }

  /**
   * OAuthで会員登録を行う。
   */
  public function createOAuth()
  {
    return Redirect::to((string) OAuth::consumer('Facebook', URL::to('user/create-oauth-callback'))->getAuthorizationUri());
  }

  /**
   * OAuthで会員登録を行う (コールバックパス)
   */
  public function createOAuthCallback()
  {
    $params = [
      'code' => Input::get('code')
    ];
    $errors = [];

    if (!$this->user->createOAuth(Models\UserCredential::CREDENTIAL_TYPE_FACEBOOK, $params, $errors)) {
      return Redirect::to('user/create')
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::to('user/done');
  }

  /**
   * 会員登録を行なう。
   */
  public function store()
  {
    $fields = Input::only(
      'nickname',
      'email',
      'password'
    );

    $errors = [];

    if (!$this->user->create($fields, $errors)) {
      return Redirect::to('user/create')
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::to('user/done');
  }

  /**
   * 会員登録完了ページを表示する。
   */
  public function done()
  {
    return View::make('user/registration/done');
  }

  /**
   * 会員データ編集ページを表示する。
   */
  public function edit()
  {
    return View::make('user/registration/edit');
  }

  /**
   * 会員データを更新する。
   */
  public function update()
  {
    $fields = Input::only(
      'nickname',
      'email',
      'password',
      'password_confirmation'
    );

    $errors = [];

    if (!$this->user->update(Auth::id(), $fields, $errors)) {
      return Redirect::to('/user')
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::to('/user')
      ->with('success', Lang::get('validation.custom.update_success'));
  }

  /**
   * 退会処理を行う。
   */
  public function withdrawal()
  {
    $this->user->delete(Auth::id());
    Auth::logout();

    return View::make('user/registration/withdrawal');
  }
}
