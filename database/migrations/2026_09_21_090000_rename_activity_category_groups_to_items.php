<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 科目カテゴリの子を group から item へ改名する。
 *
 * 「カテゴリのグループ」はカテゴリを束ねる側、つまり親に読める。実際は
 * activity_categories の子で、画面では「科目」と呼んでいるもの。名前だけが
 * 親子を逆に見せていた。
 *
 * 作成時の migration はそのままにして、ここで改名する。過去の migration を
 * 書き換えると、本番のように既に古い名前で動いている DB へ適用する手が
 * なくなる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('activity_category_groups', 'activity_category_items');

        Schema::table('activity_category_items', function(Blueprint $table) {
            $table->renameColumn('group_name', 'item_name');
        });

        Schema::table('activities', function(Blueprint $table) {
            $table->renameColumn('activity_category_group_id', 'activity_category_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function(Blueprint $table) {
            $table->renameColumn('activity_category_item_id', 'activity_category_group_id');
        });

        Schema::table('activity_category_items', function(Blueprint $table) {
            $table->renameColumn('item_name', 'group_name');
        });

        Schema::rename('activity_category_items', 'activity_category_groups');
    }
};
