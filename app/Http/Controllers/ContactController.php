<?php
namespace App\Http\Controllers;

use Auth;
use Request;
use Lang;
use Redirect;
use Session;
use View;

use App\Services;

class ContactController extends Controller {
    private $contact;

    /**
     * @see BaseController::__construct()
     */
    public function __construct(Services\ContactService $contact)
    {
        $this->contact = $contact;
    }

    /**
     * お問い合わせページを表示する。
     */
    public function index()
    {
        $data = [];
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

        $fields = Request::only(
            'contact_name',
            'email',
            'contact_type',
            'contact_message'
        );
        $errors = [];

        if (!$this->contact->send($fields, $user_id, $errors)) {
            return Redirect::to('contact')->withErrors($errors)->withInput();
        }

        Session::flash('send_email', true);

        return Redirect::to('contact/done')
            ->with('success', Lang::get('validation.custom.send_success'));
    }

    /**
     * お問い合わせ完了ページを表示する。
     */
    public function done()
    {
        if (!Session::has('send_email')) {
            return Redirect::to('contact');
        }

        return View::make('contact/done');
    }
}
