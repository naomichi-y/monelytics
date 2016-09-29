<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityCategoriesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('activity_categories', function(Blueprint $table)
    {
      $table->increments('id')->unsigned();
      $table->integer('user_id')->unsigned();
      $table->string('category_name', 32);
      $table->string('content', 255)->nullable();
      $table->tinyInteger('cost_type')->unsigned();
      $table->tinyInteger('balance_type')->unsigned();
      $table->tinyInteger('sort_order')->default(1)->unsigned();
      $table->timestamp('create_date')->default(DB::raw('CURRENT_TIMESTAMP'));
      $table->timestamp('last_update_date')->default(DB::raw('CURRENT_TIMESTAMP'))->nullableTimestamps();
      $table->timestamp('delete_date')->nullableTimestamps()->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('activity_categories');
  }

}
