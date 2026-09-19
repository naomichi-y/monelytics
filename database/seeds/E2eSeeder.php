<?php
namespace Seeds;

use Eloquent;
use Illuminate\Database\Seeder;

/**
 * E2E 用のデータ。
 *
 * PHPUnit の TestSeeder とは別に持つ。あちらは単体テストの都合で最小限の
 * データしか作らず、画面を操作するには月リストも推移グラフの目盛りも足りない。
 */
class E2eSeeder extends Seeder {
    public function run()
    {
        Eloquent::unguard();

        $this->call('Seeds\E2e\UserTableSeeder');
        $this->call('Seeds\E2e\ActivityCategoryTableSeeder');
        $this->call('Seeds\E2e\ActivityCategoryGroupTableSeeder');
        $this->call('Seeds\E2e\ActivityTableSeeder');
    }
}
