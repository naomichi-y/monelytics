<?php

class BaseController extends Controller {
  protected $layout = 'layouts.master';
  protected $required_auth = true;

  /**
   * @see Controller::__construct()
   */
  public function __construct()
  {
    if ($this->required_auth) {
      $this->beforeFilter('auth');
    }
  }

  /**
   * @see Controller::setupLayout()
   */
  protected function setupLayout()
  {
    if (!is_null($this->layout)) {
      $this->layout = View::make($this->layout);
    }
  }
}
