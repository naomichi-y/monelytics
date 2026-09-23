<?php
namespace App\Libraries\Condition;

class DailyPaginateCondition extends BaseDateCondition {
    /**
     * 並び替えを許す列。Html::sortLabel が一覧の見出しに出しているものと揃える。
     *
     * 利用者入力をそのまま orderBy へ渡すと、存在しない列名で QueryException が
     * 出て 500 になる。列名はクエリビルダが逃がすため注入は成立しないが、
     * 受け付ける値をここで閉じておく。
     */
    const SORT_FIELDS = [
        'activity_date',
        'activity_category_item_id',
        'location',
        'content',
        'amount',
        'credit_flag',
        'create_date',
    ];

    /**
     * 1 ページの件数。狭い画面では 1 件が「見出し / 値」の縦並びに畳まれ、
     * 同じ件数でも画面がかなり長くなるため少なくする。
     */
    const DEFAULT_LIMIT = 30;
    const MOBILE_LIMIT = 10;

    public $activity_category_item_id = [];
    public $keyword;
    public $location;
    public $credit_flag;
    public $cost_type;
    public $min_amount;
    public $max_amount;
    public $sort_field;
    public $sort_type;
    public $limit;

    public function __construct(array $fields = [])
    {
        if (!in_array($fields['sort_field'] ?? null, self::SORT_FIELDS, true)) {
            $fields['sort_field'] = 'activity_date';
        }

        // 並び順は利用者入力から来る。Laravel 13 は asc/desc 以外を拒否するため、
        // 旧来の「asc 以外は降順」という挙動を保ったまま値を確定させる。
        // 配列でも送れるので、文字列に倒せる値だけを見る。strtolower へ
        // そのまま渡すと TypeError になり、一覧が開けない。
        $sort_type = $fields['sort_type'] ?? '';
        $fields['sort_type'] = is_string($sort_type) && strtolower($sort_type) === 'asc' ? 'asc' : 'desc';

        if (empty($fields['limit'])) {
            $fields['limit'] = \Agent::isMobile() ? self::MOBILE_LIMIT : self::DEFAULT_LIMIT;
        }

        parent::__construct($fields);

        $this->min_amount = self::normalizeAmount($this->min_amount);
        $this->max_amount = self::normalizeAmount($this->max_amount);

        // 上下を逆に入れたときは入れ替える。そのまま検索すると必ず 0 件になり、
        // 条件のどこが悪いのかが画面から読めない。
        if ($this->min_amount !== null && $this->max_amount !== null && $this->min_amount > $this->max_amount) {
            [$this->min_amount, $this->max_amount] = [$this->max_amount, $this->min_amount];
        }

        // 期間の指定が 1 つも無いときは当月にする。
        //
        // 何も付けずに /summary/daily を開くと期間が効かず、全期間が発生日の
        // 降順で並んでいた。1 ページ目が最近の行で埋まるので当月に見えるが、
        // 先頭に来るのは未来日の行で、当月を出しているつもりの画面に翌年の
        // 収支が混ざる。帯の月セレクトが当月を表示しているぶん、一覧だけが
        // 別の期間を見ていることに気付けない。
        //
        // 全期間は月セレクトの「未指定」(date_month = 'all') で選べる。
        // 既定を当月にしてもそちらは塞がらない。
        //
        // 既定値は親の正規化を通したあとで入れる。?date_month[]=x のように
        // 配列で送られると正規化が null へ倒すため、生の $fields を見ると
        // 「指定あり」と読んでしまい、期間の無い状態が残る。
        $has_period = strlen((string) $this->date_year)
            || strlen((string) $this->date_month)
            || strlen((string) $this->begin_date)
            || strlen((string) $this->end_date);

        if (!$has_period) {
            $this->date_month = date('Y-m');
        }
    }

    /**
     * 金額の範囲指定を 0 以上の整数へ倒す。倒せないものは指定なし (null) にする。
     *
     * 値はクエリ文字列からそのまま来る。is_numeric で受けると '1.5' や '1e5' が
     * 通り、比べる列 (int) と食い違う額で絞ることになる。変動費の登録が
     * numeric で受けていたころに、同じ形の値が別の額で保存されていた。
     *
     * 負の数を受けないのは、範囲を符号を外した額で比べるため
     * (ActivityService::getDailyPaginate)。桁の多すぎる値は (int) が
     * PHP_INT_MAX に張り付くので、上限としては「どの行も超えない」のまま効く。
     *
     * @param mixed $value
     * @return int|null
     */
    private static function normalizeAmount($value)
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $value = trim((string) $value);

        return preg_match('/\A[0-9]+\z/', $value) ? (int) $value : null;
    }
}
