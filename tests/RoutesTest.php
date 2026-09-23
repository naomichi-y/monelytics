<?php
namespace Tests;

class RoutesTest extends TestCase {
    /**
     * Route::resource の既定は index / create / store / show / edit / update /
     * destroy を全て登録する。実装のない action が載ったままだと、開いた瞬間に
     * BadMethodCallException で 500 になる。どの画面も持たない show が
     * 載っていて、ダッシュボードに至っては index 以外の 6 つが全て 500 だった。
     */
    public function testUnimplementedResourceActionsAreNotRegistered()
    {
        $this->login();

        $cases = [
            ['GET', '/dashboard/create'],
            ['GET', '/dashboard/1'],
            ['GET', '/dashboard/1/edit'],
            ['POST', '/dashboard'],
            ['PUT', '/dashboard/1'],
            ['DELETE', '/dashboard/1'],
            ['GET', '/settings/activityCategory/1'],
            ['GET', '/settings/activityCategoryItem/1'],
        ];

        // 同じ URI に別の method のルートがあるものは 405 になる。どちらでも
        // 「ルートが無い」ことを示すので、見たいのは 500 でないことのほう。
        foreach ($cases as [$method, $uri]) {
            $this->assertContains(
                $this->call($method, $uri)->getStatusCode(),
                [404, 405],
                sprintf('%s %s がルートに載っている', $method, $uri)
            );
        }

        $this->logout();
    }

    /**
     * どのルートにも当たらない URL の 404 は、以前はミドルウェアを通る前に
     * 描かれていた。セッションが始まっていないため、ログイン済みでも
     * ナビバーに「ログイン」が出て、ログアウトされたように見えた。
     */
    public function testNotFoundPageKeepsTheLoggedInNavbar()
    {
        $this->login();

        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $response->assertSee('アカウント');
        $response->assertDontSee('/user/login', false);

        $this->logout();

        // 未ログインでは従来どおりログインへの入口を出す。
        $this->get('/no-such-page')->assertNotFound()->assertSee('/user/login', false);
    }
}
