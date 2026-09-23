<?php
namespace Tests\Services;

use Auth;
use Session;

use Tests\TestCase;

class UserServiceTest extends TestCase {
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app('App\Services\UserService');
    }

    /**
     * Auth::logout() は認証キーと記憶用トークンしか捨てない。セッションを
     * 作り直さないと、ログアウト前に抜き取られた Cookie が同じセッションを
     * 指したままになる。
     */
    public function testLogoutReplacesTheSession()
    {
        $this->login();
        Session::put('probe', 'alive');
        $before = Session::getId();

        $this->user->logout();

        $this->assertTrue(Auth::guest(), 'ログアウトできていない');
        $this->assertNotSame($before, Session::getId(), 'セッション ID が据え置かれている');
        $this->assertNull(Session::get('probe'), 'ログアウト前の値が残っている');
    }

    /**
     * CSRF トークンはセッションと対になっている。片方だけ作り直すと、
     * ログアウト後の画面が持つトークンと食い違って POST が 419 になる。
     */
    public function testLogoutReplacesTheCsrfToken()
    {
        $this->login();
        $before = Session::token();

        $this->user->logout();

        $this->assertNotSame($before, Session::token(), 'CSRF トークンが据え置かれている');
    }

    /**
     * 更新する相手は Auth::id() から決まり、入力では動かせない。
     *
     * 以前は $fields['id'] に混ぜて User::updateValidate へ渡しており、
     * 規則にも 'id' => 'required' が並んでいた。入力から来る値のように
     * 見えるが、実際には UserService が上書きしていたため効いていない。
     * 入力に id を紛れ込ませても他人が更新されないことを固定する。
     */
    public function testUpdateIgnoresAnIdInTheInput()
    {
        $other = \App\Models\User::create([
            'email' => 'other@monelytics.me',
            'password' => \Hash::make('otherother'),
            'nickname' => 'other'
        ]);

        $errors = [];
        $result = $this->user->update(1, [
            'id' => $other->id,
            'nickname' => '書き換え後',
            'email' => 'test@monelytics.me'
        ], $errors);

        $this->assertTrue($result, '更新に失敗した: ' . implode(' / ', $errors));
        $this->assertSame('書き換え後', \App\Models\User::find(1)->nickname, '自分が更新されていない');
        $this->assertSame('other', $other->fresh()->nickname, '入力の id で他人が更新された');
    }

    /**
     * 更新の規則は email と password しか組み立てておらず、nickname は
     * どこでも検証されていなかった。空でも通り、列 (32 文字) に入らない値は
     * QueryException が素通りして画面が 500 で落ちた。
     */
    public function testUpdateValidatesTheNickname()
    {
        foreach (['' => '空の名前', str_repeat('あ', 33) => '33 文字の名前'] as $nickname => $label) {
            $errors = [];
            $result = $this->user->update(1, [
                'nickname' => $nickname,
                'email' => 'test@monelytics.me'
            ], $errors);

            $this->assertFalse($result, $label . 'が通った');
            $this->assertNotEmpty($errors, $label . 'で検証の誤りが返っていない');
        }

        $this->assertSame('test', $this->getUser()->fresh()->nickname, '名前が書き換わっている');
    }
}
