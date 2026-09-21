<style>
.variable-expense {
    background-color: #f8f5f0;
    border-radius: 4px;
    margin: 0;
    padding: 15px 20px;
}

.variable-expense .total {
    align-items: baseline;
    border-bottom: 1px solid #dfd7ca;
    display: flex;
    gap: 12px;
    padding-bottom: 10px;
}

.variable-expense .total .label {
    flex: 1 1 auto;
}

.variable-expense .total .amount {
    font-size: 1.6rem;
    font-weight: 600;
}

.variable-expense table {
    margin: 10px 0 0;
    /* 列幅を colgroup のとおりに固定する。小項目名は利用者が 32 文字まで
       付けられ、成り行きに任せると狭い画面で名前だけが何行にも折り返して
       棒が潰れる。 */
    table-layout: fixed;
    width: 100%;
}

.variable-expense td {
    padding: 4px 6px;
    vertical-align: middle;
}

/* 入りきらない小項目名は折り返さず省略する。全文は title と、たどった先の
   一覧で読める。 */
.variable-expense .group-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 小項目名は本文と同じ色にする。5 行すべてがリンクなので、色まで付けると
   並びの中でリンクだけが目立ち、肝心の棒と額から目を引く。下線は残して
   あるので、たどれることは分かる。 */
.variable-expense .group-name a {
    color: inherit;
}

/* 色は識別ではなく「支出」を示すためだけに使う。アクティビティの
   ヒートマップが支出に使っている赤に揃える。 */
.variable-expense .bar {
    background-color: #ec5748;
    /* 値の側だけ丸め、目盛りの 0 側は角のままにする。 */
    border-radius: 0 4px 4px 0;
    display: block;
    height: 14px;
    min-width: 2px;
}

/*
 * 先月の同じ時点の棒。今月と同じ赤を薄めた色にして、細く敷く。
 *
 * 別の色を当てない。2 つは同じものの別の時点で、色を分けると種類の違いに
 * 読める。太さと濃さだけで前後関係を出す。
 *
 * 幅が 0 のときも要素は残す。display: block なので高さは取り、先月の記録が
 * ない小項目だけ行が詰まって並びがでこぼこになるのを避けられる。今月の棒に
 * ある min-width は付けない。使っていないのに 2px 出ると、わずかに使った
 * ように読めるため。
 */
.variable-expense .bar.previous {
    background-color: #f6b3ad;
    height: 6px;
    margin-top: 2px;
    min-width: 0;
}

.variable-expense .amount,
.variable-expense .difference {
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: nowrap;
}

.variable-expense .difference {
    color: #6c757d;
}

.variable-expense .note {
    color: #6c757d;
    font-size: 0.8rem;
    margin: 10px 0 0;
    text-align: right;
}
</style>
@if (sizeof($expense['groups']))
    <div class="variable-expense">
        <div class="total">
            <span class="label">変動支出合計</span>
            <span class="amount">{!! Html::amount($expense['total']['amount']) !!}</span>
            <span class="difference">{!! Html::withUnit(Html::comparisonAmount($expense['total']['difference'])) !!}</span>
        </div>

        <table>
            <colgroup>
                <col style="width: 28%" />
                <col style="width: 20%" />
                <col style="width: 26%" />
                <col style="width: 26%" />
            </colgroup>
            <tbody>
                @foreach ($expense['groups'] as $group)
                    <tr>
                        <td class="group-name">{!! Html::linkWithQueryString('/summary/daily', [
                            'begin_date' => $expense['period']['begin_date'],
                            'end_date' => $expense['period']['end_date'],
                            'activity_category_item_id[]' => $group['activity_category_item_id']
                        ], $group['item_name'], ['title' => $group['item_name']]) !!}</td>
                        <td>
                            {{-- 棒の長さは合計ではなく最も多い小項目を基準にする。
                                 小項目どうしの多い少ないを見るための図なので。
                                 基準は今月と先月を通した最大額で、Service が返す。 --}}
                            <span class="bar" style="width: {{round($group['amount'] / $expense['largest_amount'] * 100)}}%"></span>
                            <span class="bar previous" style="width: {{round($group['previous_amount'] / $expense['largest_amount'] * 100)}}%"></span>
                        </td>
                        <td class="amount">{!! Html::amount($group['amount']) !!}</td>
                        <td class="difference">{!! Html::withUnit(Html::comparisonAmount($group['difference'])) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 合計は今月の変動支出すべてで、棒は小項目を絞ったとき合計に届かない。
             黙って並べると棒の合計が上の数字と合わないように見えるため、
             絞ったときだけそう書く。 --}}
        <p class="note">
            @if ($expense['group_count'] > sizeof($expense['groups']))
                {{$expense['group_count']}} 小項目中の上位 {{sizeof($expense['groups'])}} /
            @endif
            薄い棒と増減は先月の {{Html::date($expense['period']['previous_end_date'], false)}} までとの比較
        </p>
    </div>
@else
    <p>データがありません。</p>
@endif
