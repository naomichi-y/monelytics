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
}
