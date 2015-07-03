<?php
namespace Monelytics\Seeds;

use Eloquent;

class TestSeeder extends \Illuminate\Database\Seeder {

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Eloquent::unguard();

    $this->call('UserTableSeeder');
    $this->call('ActivityTableSeeder');
    $this->call('ActivityCategoryTableSeeder');
    $this->call('ActivityCategoryGroupTableSeeder');
  }

}
