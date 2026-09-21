<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
 *
 * 途中まで通った状態から再実行できるようにしてある。本番では表の改名まで
 * 進んだところで列の改名が落ちた。
 */
return new class extends Migration
{
    /**
     * 列の改名を阻む既定値を直す対象。
     *
     * 2015 年に作られた本番の timestamp 列は既定値が '0000-00-00 00:00:00' に
     * なっている。当時は通ったが、Laravel は strict 接続で NO_ZERO_DATE を
     * 立てるため、いま ALTER TABLE を投げると定義の検証に引っかかって
     * 「Invalid default value」で落ちる。改名の前に、作成時の migration が
     * 意図していた CURRENT_TIMESTAMP へ直す。
     *
     * 新しく作った DB の既定値は既に current_timestamp() なので、そちらでは
     * 何も変わらない。テストが通って本番だけ落ちたのはこの差による。
     */
    private const LEGACY_TIMESTAMP_TABLES = ['activity_category_items', 'activities'];

    public function up(): void
    {
        if (Schema::hasTable('activity_category_groups')) {
            Schema::rename('activity_category_groups', 'activity_category_items');
        }

        $this->repairTimestampDefaults();

        if (Schema::hasColumn('activity_category_items', 'group_name')) {
            Schema::table('activity_category_items', function(Blueprint $table) {
                $table->renameColumn('group_name', 'item_name');
            });
        }

        if (Schema::hasColumn('activities', 'activity_category_group_id')) {
            Schema::table('activities', function(Blueprint $table) {
                $table->renameColumn('activity_category_group_id', 'activity_category_item_id');
            });
        }
    }

    public function down(): void
    {
        $this->repairTimestampDefaults();

        if (Schema::hasColumn('activities', 'activity_category_item_id')) {
            Schema::table('activities', function(Blueprint $table) {
                $table->renameColumn('activity_category_item_id', 'activity_category_group_id');
            });
        }

        if (Schema::hasColumn('activity_category_items', 'item_name')) {
            Schema::table('activity_category_items', function(Blueprint $table) {
                $table->renameColumn('item_name', 'group_name');
            });
        }

        if (Schema::hasTable('activity_category_items')) {
            Schema::rename('activity_category_items', 'activity_category_groups');
        }
    }

    /**
     * 既定値が有効な表では何も変わらないため、何度実行してもよい。
     */
    private function repairTimestampDefaults(): void
    {
        foreach (self::LEGACY_TIMESTAMP_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s`'
                    . ' MODIFY `create_date` timestamp NOT NULL DEFAULT current_timestamp(),'
                    . ' MODIFY `last_update_date` timestamp NOT NULL DEFAULT current_timestamp()',
                $table
            ));
        }
    }
};
