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
     * 配布元が名前を書かない祝日の表記と、こちらで補う名前。
     *
     * 内閣府の CSV は、祝日法 3 条 2 項の振替休日も 3 項の国民の休日も
     * まとめて「休日」とだけ書く。画面には日付の横に出るため、2026/9/22 の
     * ように敬老の日と秋分の日に挟まれた日が「休日」とだけ表示され、
     * 何の日か分からなかった。
     */
    private const UNNAMED = '休日';
    private const SUBSTITUTE = '振替休日';
    private const NATIONAL = '国民の休日';

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
        // 名前を補うには前後の日が要る。月や年で切り出したあとでは、境目に
        // 当たる日の判断材料が落ちるため、絞り込む前に全期間へ当てる。
        return array_filter(
            self::resolveUnnamed(self::all()),
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
     * 「休日」とだけ書かれた日に、祝日法どおりの名前を与える。
     *
     * 取り込みではなく読み出しの側で行う。取り込み時に名前を書き込むと、
     * すでにキャッシュへ入っている一覧が TTL の 1 日が切れるまで古い名前の
     * まま残る。ここなら、配布元から取り直さなくても次の表示から直る。
     *
     * 名前の付いた日しか持たない一覧に当てても何も起きない (冪等)。
     *
     * 元の一覧を見ながら別の配列へ書くのは、名前を付けた日をその場で
     * 上書きすると、後の日から見て「休日」が祝日に化けるため。国民の休日は
     * 前後が祝日であることが条件なので、判定が変わってしまう。
     *
     * @param array $holidays [Y-m-d => [名称]]
     * @return array [Y-m-d => [名称]]
     */
    private static function resolveUnnamed(array $holidays)
    {
        $resolved = $holidays;

        foreach ($holidays as $date => $names) {
            if ($names !== [self::UNNAMED]) {
                continue;
            }

            if (self::isSubstitute($date, $holidays)) {
                $resolved[$date] = [self::SUBSTITUTE];

            } else if (self::isNationalHoliday($date, $holidays)) {
                $resolved[$date] = [self::NATIONAL];
            }
        }

        return $resolved;
    }

    /**
     * 振替休日か。祝日法 3 条 2 項。
     *
     * 日曜に当たった祝日の後、最初の祝日でない日が振替休日になる。間に祝日が
     * 続けば後ろへずれるため (2026 年は 5/3 の憲法記念日が日曜で、みどりの日と
     * こどもの日を越えた 5/6 が振替休日)、祝日の連なりを遡って日曜の祝日に
     * 着くかどうかで見る。
     *
     * 国民の休日より先に判定する。3 条 3 項が振替休日を除いているため、
     * 両方の条件に当たる日 (日曜の憲法記念日に続く 5/4 など) は振替休日。
     *
     * @param string $date Y-m-d
     * @param array $holidays [Y-m-d => [名称]]
     * @return bool
     */
    private static function isSubstitute($date, array $holidays)
    {
        $cursor = strtotime($date . ' -1 day');

        while (isset($holidays[date('Y-m-d', $cursor)])) {
            if (self::isNamed($holidays, date('Y-m-d', $cursor)) && (int) date('w', $cursor) === 0) {
                return true;
            }

            $cursor = strtotime('-1 day', $cursor);
        }

        return false;
    }

    /**
     * 国民の休日か。祝日法 3 条 3 項。前日と翌日がともに祝日である日。
     *
     * 前後に見るのは名前の付いた祝日だけ。「休日」同士が並ぶことはあるが、
     * それは条文が言う「国民の祝日」ではない。
     *
     * @param string $date Y-m-d
     * @param array $holidays [Y-m-d => [名称]]
     * @return bool
     */
    private static function isNationalHoliday($date, array $holidays)
    {
        return self::isNamed($holidays, date('Y-m-d', strtotime($date . ' -1 day')))
            && self::isNamed($holidays, date('Y-m-d', strtotime($date . ' +1 day')));
    }

    /**
     * その日が、名前の付いた祝日として収録されているか。
     *
     * @param array $holidays [Y-m-d => [名称]]
     * @param string $date Y-m-d
     * @return bool
     */
    private static function isNamed(array $holidays, $date)
    {
        return isset($holidays[$date]) && $holidays[$date] !== [self::UNNAMED];
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
