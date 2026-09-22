<?php
namespace App\Http\Controllers;

use Request;
use Response;

use App\Libraries\Calendar;

class HolidayController extends Controller {
    /**
     * 指定された年の祝日を返す。
     *
     * 日付入力のカレンダー (jQuery UI datepicker) が色と説明を付けるために読む。
     * 月別集計のカレンダーが使っているものと同じ、内閣府の一覧が元になる。
     *
     * 祝日は表示の補助でしかないため、取得できないときも空の一覧を返し、
     * 画面側は土日の色分けだけで動く。
     */
    public function index()
    {
        $year = Request::input('year');

        // 配列も送れる。文字列へ倒す時点で「Array to string conversion」が
        // 例外になり、日付入力を持つ画面の色付けごと落ちる。
        if (!is_scalar($year) || !preg_match('/\A\d{4}\z/', (string) $year)) {
            $year = date('Y');
        }

        $holidays = [];

        foreach (Calendar::getHolidaysOfYear($year) as $date => $names) {
            $holidays[$date] = implode(' ', $names);
        }

        return Response::json($holidays);
    }
}
