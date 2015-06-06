<?php
class UserController extends BaseController {
  protected $required_auth = true;
  private $user;

  public function __construct(UserService $user)
  {
    $this->user = $user;

    $action_name = Route::getCurrentRoute()->getActionName();
    $action_name = substr($action_name, strpos($action_name, '@') + 1);
    $excludes = array(
      'getCreate',
      'postCreate',
      'postCreateWithOAuth',
      'getLogin',
      'postLogin',
      'postLoginWithOAuth',
      'getLoginWithOAuth',
      'getCreateWithOAuth'
    );

    if (in_array($action_name, $excludes)) {
      $this->required_auth = false;
    }

    parent::__construct();
  }

  /**
   * 設定ページを表示する。
   */
  public function getIndex()
  {
    return View::make('user/index');
  }

  /**
   * 会員登録を行なう。
   */
  public function postCreate()
  {
    $fields = Input::only(
      'nickname',
      'email',
      'password'
    );

    $errors = array();

    if (!$this->user->create($fields, $errors)) {
      return Redirect::to('user/create')
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::to('user/create-done');
  }

  /**
   * OAuthで会員登録を行う。
   */
  public function postCreateWithOAuth()
  {
    return Redirect::to((string) OAuth::consumer('Facebook')->getAuthorizationUri());
  }

  /**
   * OAuthで会員登録を行う (コールバックパス)
   */
  public function getCreateWithOAuth()
  {
    try {
      $oauth = new OAuthCredential(
        UserCredential::CREDENTIAL_TYPE_FACEBOOK,
        array('code' => Input::get('code'))
      );
      $oauth->build();

      $profile = $oauth->getProfile();
      $errors = array();

      if (!$this->user->createWithOAuth($profile, $oauth->access_token, $errors)) {
        return Redirect::to('user/create')
          ->withErrors($errors)
          ->withInput();
      }

      return Redirect::to('user/create-done');

    } catch (Exception $e) {
      return Redirect::to('user/create');
    }
  }

  public function getCreate()
  {
    return View::make('user/create');
  }

  /**
   * 会員登録完了ページを表示する。
   */
  public function getCreateDone()
  {
    return View::make('user/create_done');
  }

  public function getLogin()
  {
    return View::make('user/login');
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
      return Redirect::back()
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::intended('dashboard');
  }

  /**
   * OAuthでシステムにログインする。
   */
  public function postLoginWithOAuth()
  {
    return Redirect::to((string) OAuth::consumer('Facebook')->getAuthorizationUri());
  }

  /**
   * OAuthでシステムにログインする。(コールバックパス)
   */
  public function getLoginWithOAuth()
  {
    $params = array(
      'code' => Input::get('code')
    );
    $errors = array();

    if (!$this->user->loginWithOAuth(UserCredential::CREDENTIAL_TYPE_FACEBOOK, $params, $errors)) {
      return Redirect::to('user/login')
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::intended('dashboard');
  }

  /**
   * 会員データを更新する。
   */
  public function postUpdate()
  {
    $fields = Input::only(
      'nickname',
      'email',
      'password',
      'password_confirmation'
    );

    $errors = array();

    if (!$this->user->update(Auth::id(), $fields, $errors)) {
      return Redirect::back()
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::back()
      ->with('success', Lang::get('validation.custom.update_success'));
  }

  /**
   * 退会処理を行う。
   */
  public function postWithdrawal()
  {
    $this->user->delete(Auth::id());
    Auth::logout();

    return View::make('user/withdrawal');
  }

  /**
   * システムからログアウトする。
   */
  public function getLogout()
  {
    $this->user->logout();

    return Redirect::to('/');
  }
}
