<?php
namespace App\Libraries;

use RuntimeException;
use Throwable;

use Cache;
use Config;
use Log;

class Calendar {
    /**
     * 取得に失敗した月を再び問い合わせるまでの秒数。
     * 失敗も短時間キャッシュしないと、カレンダーを開くたびに外部へ出ていく。
     */
    private const FAILURE_TTL = 600;

    /**
     * Google への接続と読み取りを打ち切るまでの秒数。
     * 既定は default_socket_timeout の 60 秒で、無応答時に画面がその間止まる。
     */
    private const REQUEST_TIMEOUT = 5;

    /**
     * 対象月の休日を取得する。
     *
     * 休日は表示の補助情報でしかないため、キャッシュにも外部 API にも
     * 依存して画面を落とさない。取得できなければ空配列を返す。
     *
     * @param string $target_month
     * @return array
     */
    public static function getHolidays($target_month)
    {
        $cache_key = 'holidays.' . $target_month;

        try {
            $cached = Cache::get($cache_key);

            // 休日のない月は空配列で記録されるため、未取得は null で判別する。
            if ($cached !== null) {
                return $cached;
            }

        } catch (Throwable $e) {
            Log::error('休日キャッシュの読み出しに失敗した。', ['exception' => $e]);

            return [];
        }

        try {
            $holidays = self::fetch($target_month);

        } catch (Throwable $e) {
            Log::error('休日の取得に失敗した。', ['exception' => $e]);
            self::remember($cache_key, [], self::FAILURE_TTL);

            return [];
        }

        self::remember($cache_key, $holidays, null);

        return $holidays;
    }

    /**
     * Google Calendar API から対象月の休日を取得する。
     *
     * @param string $target_month
     * @return array
     * @throws RuntimeException
     */
    private static function fetch($target_month)
    {
        $begin_date = sprintf('%s-01T09:00:00Z', $target_month);
        $last_day = date('d', strtotime('last day of ' . $target_month));
        $end_date = sprintf('%s-%sT09:00:00Z', $target_month, $last_day);

        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events?'
            .'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
            rawurlencode((string) Config::get('app.google.calendar.id')),
            rawurlencode((string) Config::get('app.google.api_key')),
            $begin_date,
            $end_date,
            31
        );

        // ignore_errors を立てると 4xx でも本文が読めるので、原因をログに残せる。
        $context = stream_context_create([
            'http' => [
                'timeout' => self::REQUEST_TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new RuntimeException(sprintf('%s の休日を取得できなかった。', $target_month));
        }

        $result = json_decode($body);

        if (!isset($result->items)) {
            throw new RuntimeException(sprintf(
                '%s の休日を取得できなかった: %s',
                $target_month,
                $result->error->message ?? '応答を解釈できない'
            ));
        }

        $holidays = [];

        foreach ($result->items as $item) {
            $date = date('Y-m-d', strtotime($item->start->date));
            $holidays[$date] = explode(' / ', $item->summary);
        }

        return $holidays;
    }

    /**
     * キャッシュへの書き込みは失敗しても表示を止めない。
     *
     * @param string $cache_key
     * @param array $holidays
     * @param int|null $ttl 秒。null で無期限。
     * @return void
     */
    private static function remember($cache_key, array $holidays, $ttl)
    {
        try {
            if ($ttl === null) {
                Cache::forever($cache_key, $holidays);
            } else {
                Cache::put($cache_key, $holidays, $ttl);
            }

        } catch (Throwable $e) {
            Log::error('休日キャッシュの書き込みに失敗した。', ['exception' => $e]);
        }
    }
}
