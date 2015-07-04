<?php
namespace Monelytics\Seeds;

use Illuminate\Database\Seeder;

use Eloquent;

class TestSeeder extends Seeder {

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Eloquent::unguard();

    $this->call('Monelytics\Seeds\Test\UserTableSeeder');
    $this->call('Monelytics\Seeds\Test\ActivityTableSeeder');
    $this->call('Monelytics\Seeds\Test\ActivityCategoryTableSeeder');
    $this->call('Monelytics\Seeds\Test\ActivityCategoryGroupTableSeeder');
  }

}
