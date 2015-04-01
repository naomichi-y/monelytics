<?php
class UserController extends BaseController {
  protected $required_auth = true;
  private $user;

  public function __construct(UserService $user)
  {
    $this->user = $user;

    $action_name = Route::getCurrentRoute()->getActionName();
    $action_name = substr($action_name, strpos($action_name, '@') + 1);
    $excludes = array('postLogin', 'postCreate');

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

    return Redirect::intended('home');
  }

  /**
   * 会員登録完了ページを表示する。
   */
  public function getCreate()
  {
    return View::make('user/create');
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
      return Redirect::back()
        ->withErrors($errors)
        ->withInput();
    }

    return Redirect::to('user/create');
  }

  /**
   * 会員データを更新する。
   */
  public function postUpdate()
  {
    return Redirect::to('user');
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
