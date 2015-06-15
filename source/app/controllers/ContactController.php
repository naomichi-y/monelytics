<?php
class ContactController extends BaseController {
  protected $required_auth = false;

  private $contact;

  /**
   * @see BaseController::__construct()
   */
  public function __construct(ContactService $contact)
  {
    $this->contact = $contact;
  }

  /**
   * お問い合わせページを表示する。
   */
  public function index()
  {
    $data = array();
    $data['contact_type_list'] = $this->contact->getContactTypeList();


    return View::make('contact/create', $data);
  }

  /**
   * 問い合わせを送信する。
   */
  public function send()
  {
    $user_id = null;

    if (Auth::check()) {
      $user_id = Auth::id();
    }

    $fields = Input::only(
      'contact_name',
      'email',
      'contact_type',
      'contact_message'
    );
    $errors = array();

    if (!$this->contact->send($fields, $user_id, $errors)) {
      return Redirect::back()->withErrors($errors)->withInput();
    }

    Session::flash('send_email', true);

    return Redirect::to('contact/done')
      ->with('success', Lang::get('validation.custom.send_success'));
  }

  /**
   * お問い合わせ完了ページを表示する。
   v*/
  public function done()
  {
    if (!Session::has('send_email')) {
      return Redirect::back();
    }

    return View::make('contact/done');
  }
}
