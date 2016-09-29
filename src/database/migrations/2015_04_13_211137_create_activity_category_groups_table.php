<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityCategoryGroupsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('activity_category_groups', function(Blueprint $table)
    {
      $table->increments('id')->unsigned();
      $table->integer('activity_category_id')->unsigned();
      $table->integer('user_id')->unsigned();
      $table->string('group_name', 32);
      $table->tinyInteger('credit_flag')->default(0)->unsigned();
      $table->string('content', 255)->nullable();
      $table->tinyInteger('sort_order')->default(0)->unsinged();
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
    Schema::drop('activity_category_groups');
  }

}
