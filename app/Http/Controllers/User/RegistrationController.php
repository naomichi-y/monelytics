<?php
namespace App\Http\Controllers\User;

use Auth;
use Request;
use Lang;
use Redirect;
use Route;
use View;

use App\Libraries\Condition;
use App\Services;

class RegistrationController extends \App\Http\Controllers\Controller {
    private $user;

    public function __construct(Services\UserService $user)
    {
        $this->user = $user;
        $this->middleware('guest', [
            'only' => [
                'create',
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
     * 会員登録を行なう。
     */
    public function store()
    {
        $fields = Request::only(
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
        $fields = Request::only(
            'nickname',
            'email',
            'current_password',
            'password',
            'password_confirmation'
        );

        $errors = [];

        if (!$this->user->update(Auth::id(), $fields, $errors)) {
            // 入力の引き継ぎからパスワードを外す。withInput() は受け取った
            // 入力をそのままセッションへ書き、パスワードが平文で残る。
            // 画面はどの欄も password 入力なので戻す先もない。
            return Redirect::to('/user')
                ->withErrors($errors)
                ->withInput(Request::except(
                    'current_password',
                    'password',
                    'password_confirmation'
                ));
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
