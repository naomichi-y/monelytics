<?php
namespace Monelytics\Libraries;

use Exception;

use Cache;
use Config;
use Log;

class Calendar {
  /**
   * 対象月のカレンダー配列を取得する。
   *
   * @param string $target_month
   * @return array
   */
  public static function getHolidays($target_month)
  {
    $cache_key = 'holidays.' . $target_month;

    // 対象月の休日データがキャッシュされているかチェック
    if (!Cache::has($cache_key)) {
      $begin_date = sprintf('%s-01', $target_month) . 'T00:00:00Z';
      $last_day = date('d', strtotime('last day of ' . $target_month));
      $end_date = sprintf('%s-%s', $target_month, $last_day) . 'T00:00:00Z';

      // Google Calendar APIで休日を取得
      $url = sprintf(
        'https://www.googleapis.com/calendar/v3/calendars/%s/events?'
        .'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
        Config::get('app.google.calendar.id'),
        Config::get('app.google.api_key'),
        $begin_date,
        $end_date,
        31
      );

      $holidays = array();

      try {
        $result = json_decode(file_get_contents($url));

        foreach ($result->items as $item) {
          $date = date('Y-m-d', strtotime($item->start->date));
          $title = explode(' / ', $item->summary);

          $holidays[$date] = $title;
        }

        Cache::forever($cache_key, $holidays);

      } catch (Exception $e) {
        Log::error($e);
      }

    } else {
      $holidays = Cache::get($cache_key);
    }

    return $holidays;
  }
}
