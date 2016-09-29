<?php
namespace Seeds;

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

    $this->call('Seeds\Test\UserTableSeeder');
    $this->call('Seeds\Test\ActivityTableSeeder');
    $this->call('Seeds\Test\ActivityCategoryTableSeeder');
    $this->call('Seeds\Test\ActivityCategoryGroupTableSeeder');
  }

}
