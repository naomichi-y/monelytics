<?php
namespace App\Http\Controllers;

use Auth;
use Redirect;

class IndexController extends ApplicationController {
  /**
   * トップページを表示する。
   */
  public function getIndex()
  {
    if (Auth::guest()) {
      return \View::make('index/index');
    }

    return Redirect::to('dashboard');
  }
}
