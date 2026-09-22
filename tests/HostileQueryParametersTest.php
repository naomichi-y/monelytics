<?php
namespace Tests;

/**
 * クエリ文字列の形は送る側が決められる。?keyword[]=x のように配列を送ると、
 * strlen / strtolower / strtotime / (string) が軒並み TypeError や
 * 「Array to string conversion」になり、絞り込みが 1 つ効かないでは済まずに
 * 画面ごと 500 になっていた。帯の検索はどの画面にもあるため、URL 1 つで
 * ほぼ全画面を落とせた。
 *
 * 落ちた層が Condition・ビューの初期値・集計と分かれていたので、画面を
 * 一巡して 200 が返ることで押さえる。
 */
class HostileQueryParametersTest extends TestCase {
    public function testEveryScreenSurvivesArrayShapedParameters()
    {
        $this->login();

        $uris = [
            '/dashboard?keyword[]=x',
            '/summary/daily?activity_category_item_id=1',
            '/summary/daily?location[]=x',
            '/summary/daily?credit_flag[]=1',
            '/summary/daily?cost_type[]=1',
            '/summary/daily?keyword[]=x',
            '/summary/daily?date_month[]=x',
            '/summary/daily?begin_date[]=x&end_date[]=x',
            '/summary/daily?sort_field[]=x&sort_type[]=x',
            '/summary/daily/condition?credit_flag[]=1&keyword[]=x&begin_date[]=x',
            '/summary/monthly?date_month[]=x',
            '/summary/monthly/report?date_month[]=x',
            '/summary/monthly/condition?begin_date[]=x&end_date[]=x',
            '/summary/monthly/calendar?date_month[]=x',
            '/summary/monthly/pie-chart?balance_type[]=1',
            '/summary/monthly/pie-chart-data?balance_type[]=1',
            '/summary/monthly/ranking?date_month[]=x',
            '/summary/yearly?begin_year[]=1&keyword[]=x',
            '/summary/yearly/report?begin_year[]=1&keyword[]=x',
            '/summary/yearly/condition?output_type[]=1',
            '/summary/yearly/line-chart-data?begin_year[]=1&keyword[]=x',
            '/cost/constant/create?date_month[]=x',
            '/settings/activityCategory?keyword[]=x',
            '/settings/activityCategoryItem?activity_category_id[]=1',
            '/gadget/variable-expense?keyword[]=x',
            '/gadget/activity-history?keyword[]=x',
            '/holidays?year[]=1',
            '/user?nickname[]=x&email[]=x',
        ];

        foreach ($uris as $uri) {
            $this->call('GET', $uri)->assertOk($uri . ' が 200 を返さない');
        }

        $this->logout();

        // 問い合わせはログイン不要。未認証で落とせる経路がないことも見る。
        $this->call('GET', '/contact?contact_name[]=x&contact_message[]=x&contact_type[]=1')->assertOk();
    }
}
