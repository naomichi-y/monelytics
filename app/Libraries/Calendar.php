<?php
namespace App\Libraries;

use RuntimeException;
use Throwable;

use Cache;
use Log;

class Calendar {
    /**
     * 内閣府が公開する「国民の祝日」の一覧。
     * 祝日は法律で定まり内閣府が一次情報を出しているため、認証情報の要る
     * 外部 API ではなくこれを直接読む。
     */
    private const SOURCE_URL = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';

    /**
     * 取得した一覧を保持する秒数。
     * 内容は年 1 回 (毎年 2 月頃に翌年分が追加される) しか変わらないが、
     * 収録は約 14 か月先までで、更新を取り逃がすと祝日が消えるため 1 日で見直す。
     */
    private const TTL = 86400;

    /**
     * 取得に失敗したときに再び問い合わせるまでの秒数。
     * 失敗も記録しないと、カレンダーを開くたびに外部へ出ていく。
     */
    private const FAILURE_TTL = 600;

    /**
     * 接続と読み取りを打ち切るまでの秒数。
     * 既定は default_socket_timeout の 60 秒で、無応答時に画面がその間止まる。
     */
    private const REQUEST_TIMEOUT = 5;

    /** 取り込み済みの一覧を置く場所。運用で消すこともあるため公開する。 */
    public const CACHE_KEY = 'holidays.cao';

    /**
     * 対象月の祝日を取得する。
     *
     * 祝日は表示の補助情報でしかないため、キャッシュにも配布元にも依存して
     * 画面を落とさない。取得できなければ空配列を返す。
     *
     * @param string $target_month Y-m
     * @return array [Y-m-d => [名称]]
     */
    public static function getHolidays($target_month)
    {
        return self::filter($target_month . '-');
    }

    /**
     * 対象年の祝日を取得する。日付入力のカレンダーは年の単位で読む。
     *
     * @param string $target_year Y
     * @return array [Y-m-d => [名称]]
     */
    public static function getHolidaysOfYear($target_year)
    {
        return self::filter($target_year . '-');
    }

    /**
     * 日付の先頭が一致するものだけを返す。
     *
     * @param string $prefix
     * @return array [Y-m-d => [名称]]
     */
    private static function filter($prefix)
    {
        return array_filter(
            self::all(),
            static fn ($date) => str_starts_with($date, $prefix),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * 収録されている全期間の祝日を返す。
     *
     * @return array [Y-m-d => [名称]]
     */
    private static function all()
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);

            if ($cached !== null) {
                return $cached;
            }

        } catch (Throwable $e) {
            Log::error('祝日キャッシュの読み出しに失敗した。', ['exception' => $e]);

            return [];
        }

        try {
            $holidays = self::fetch();

        } catch (Throwable $e) {
            Log::error('祝日一覧の取得に失敗した。', ['exception' => $e]);
            self::remember([], self::FAILURE_TTL);

            return [];
        }

        self::remember($holidays, self::TTL);

        return $holidays;
    }

    /**
     * 内閣府の CSV を取得して解析する。
     *
     * @return array [Y-m-d => [名称]]
     * @throws RuntimeException
     */
    private static function fetch()
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::REQUEST_TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents(self::SOURCE_URL, false, $context);

        if ($body === false) {
            throw new RuntimeException('祝日 CSV を取得できなかった。');
        }

        // 配布元の文字コードは Shift_JIS (CP932)。
        $body = mb_convert_encoding($body, 'UTF-8', 'SJIS-win');
        $holidays = [];

        // \R は非 UTF-8 モードで 0x85 にもマッチし、「元」(e5 85 83) のような
        // 文字の途中で分割してしまう。改行文字を明示する。
        foreach (preg_split("/\r\n|\r|\n/", $body) as $line) {
            // 見出し行と空行を読み飛ばす。日付は YYYY/M/D 形式。
            if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2}),(.+)$/', trim($line), $matches)) {
                continue;
            }

            $date = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
            $holidays[$date] = [trim($matches[4])];
        }

        if (!$holidays) {
            throw new RuntimeException('祝日 CSV から 1 件も読み取れなかった。');
        }

        return $holidays;
    }

    /**
     * キャッシュへの書き込みは失敗しても表示を止めない。
     *
     * @param array $holidays
     * @param int $ttl 秒
     * @return void
     */
    private static function remember(array $holidays, $ttl)
    {
        try {
            Cache::put(self::CACHE_KEY, $holidays, $ttl);

        } catch (Throwable $e) {
            Log::error('祝日キャッシュの書き込みに失敗した。', ['exception' => $e]);
        }
    }
}
