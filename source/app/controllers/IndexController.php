<?php
class IndexController extends BaseController {
  protected $required_auth = false;

  /**
   * ログイン画面を表示する。
   */
  public function getLogin()
  {
    if (Auth::guest()) {
      return View::make('index/login');
    }

    return Redirect::to('dashboard');
  }
}
