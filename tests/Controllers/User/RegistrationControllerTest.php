<?php
namespace Tests\Controllers\User;

use Auth;
use Hash;

use Tests\TestCase;
use App\Models\User;

class RegistrationControllerTest extends TestCase {
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User;
    }

    public function testIndex()
    {
        $this->assertUserOnlyContent('GET', '/user');
    }

    public function testCreate()
    {
        $this->assertGuestOnlyContent('GET', '/user/create');
    }

    public function testStore()
    {
        $params = [
            'nickname' => 'test',
            'email' => 'test2@monelytics.me',
            'password' => 'testtest'
        ];

        $this->call('POST', '/user', $params);
        $this->assertRedirectedTo('/user/done');
        $this->assertTrue(Auth::check());
        $this->logout();

        $this->call('POST', '/user', $params);
        $this->assertRedirectedTo('/user/create');
        $this->assertTrue(Auth::guest());
    }

    /**
     * email 規則がなかった頃は、宛先として成立しない文字列でも登録できていた。
     */
    public function testStoreRejectsMalformedEmail()
    {
        $before_count = User::count();

        $this->call('POST', '/user', [
            'nickname' => 'test',
            'email' => 'this-is-not-an-email',
            'password' => 'testtest',
        ]);

        $this->assertRedirectedTo('/user/create');
        $this->assertTrue(Auth::guest());
        $this->assertSame($before_count, User::count());
    }

    public function testDone()
    {
        $this->assertUserOnlyContent('GET', '/user/done');
    }

    public function testEdit()
    {
        $this->assertUserOnlyContent('GET', '/user');
    }

    public function testUpdate()
    {
        $this->login();
        $params = [
            'nickname' => 'test',
            'email' => 'test2@monelytics.me',
            'current_password' => 'testtest',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ];

        $this->call('PUT', '/user/update', $params);
        $this->assertRedirectedTo('/user');

        $user = $this->user->find(1);

        $this->assertEquals($user->email, 'test2@monelytics.me');
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    /**
     * 現在のパスワードを問わなかった頃は、置き去りのセッションや盗まれた
     * Cookie を持っているだけでパスワードを差し替えられた。
     */
    public function testUpdateRejectsWrongCurrentPassword()
    {
        $this->login();

        $this->call('PUT', '/user/update', [
            'nickname' => 'renamed',
            'email' => 'test@monelytics.me',
            'current_password' => 'wrong-password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);

        $this->assertRedirectedTo('/user');

        $user = $this->user->find(1);

        // 名前だけが通ってしまわないこと。更新はまとめて捨てる。
        $this->assertEquals($user->nickname, 'test');
        $this->assertTrue(Hash::check('testtest', $user->password));
    }

    /**
     * 現在のパスワードが空のまま新しいパスワードだけを送っても通らない。
     */
    public function testUpdateRequiresCurrentPassword()
    {
        $this->login();

        $this->call('PUT', '/user/update', [
            'nickname' => 'test',
            'email' => 'test@monelytics.me',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);

        $this->assertRedirectedTo('/user');
        $this->assertTrue(Hash::check('testtest', $this->user->find(1)->password));
    }

    /**
     * パスワードを変えないときは現在のパスワードを聞かない。毎回入力させると、
     * 名前やメールアドレスだけを直したい人まで足止めすることになる。
     */
    public function testUpdateWithoutPasswordDoesNotRequireCurrentPassword()
    {
        $this->login();

        $this->call('PUT', '/user/update', [
            'nickname' => 'renamed',
            'email' => 'test@monelytics.me',
            'password' => '',
            'password_confirmation' => ''
        ]);

        $this->assertRedirectedTo('/user');

        $user = $this->user->find(1);

        $this->assertEquals($user->nickname, 'renamed');
        $this->assertTrue(Hash::check('testtest', $user->password));
    }

    /**
     * 廃止した Facebook ログインだけで作られた利用者は password が空で、
     * 入力できる現在のパスワードを持たない。ここで現在のパスワードを
     * 求めると、その人はパスワードを設定する手段を失う。
     */
    public function testUpdateAllowsFirstPasswordWithoutCurrentPassword()
    {
        $user = $this->user->find(1);
        $user->password = '';
        $user->save();
        $user->userCredential()->delete();

        $this->be($this->user->find(1));

        $this->call('PUT', '/user/update', [
            'nickname' => 'test',
            'email' => 'test@monelytics.me',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ]);

        $this->assertRedirectedTo('/user');
        $this->assertTrue(Hash::check('newpassword', $this->user->find(1)->password));
    }

    public function testWithdrawal()
    {
        $this->assertUserOnlyContent('POST', '/user/withdrawal');
        $this->seed('Seeds\TestSeeder');

        $this->login();
        $this->call('POST', '/user/withdrawal');
        $this->assertTrue(Auth::guest());

        $this->assertEquals($this->user->find(1), null);
    }
}
