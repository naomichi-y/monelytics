<?php
class IndexController extends BaseController {
  protected $required_auth = false;

  /**
   * トップページを表示する。
   */
  public function getIndex()
  {
    if (Auth::guest()) {
      return View::make('index/index');
    }

    return Redirect::to('dashboard');
  }
}
