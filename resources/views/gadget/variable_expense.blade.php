<style>
.variable-expense {
    background-color: #f8f5f0;
    border-radius: 4px;
    margin: 0;
    padding: 15px 20px;
}

.variable-expense table {
    margin: 0;
    width: 100%;
}

.variable-expense td,
.variable-expense th {
    padding: 4px 6px;
    vertical-align: middle;
}

/*
 * 列の配分。
 *
 * 数字の列 (今月・先月・先月との差) は中身の幅だけ取り、残りを小項目名と
 * 棒に回す。以前は数字が 2 列で、7 桁 (-9,999,999 円) が入るよう各列に
 * 26% ずつ固定していた。3 列を同じやり方で固定すると、表が 314px しかない画面で
 * 名前と棒に 22% しか残らない。中身の幅に任せれば、ふだんの 4、5 桁のときは
 * 名前と棒が広く取れ、7 桁が来ても数字の側ははみ出さない。
 *
 * 名前の列も中身の幅だけ取り、棒に残りを回す。表幅の 30% に固定していた
 * ときは、「食料品」のような短い名前でも 216px 取り、名前と棒の間が空きすぎた。
 * 小項目名は利用者が 32 文字まで付けられるので、上限を決めて超えた分は
 * 省略する。上限は td ではなく中の要素に付ける。表のセルの max-width は
 * ブラウザが守る決まりがない。
 *
 * 数字のセルのクラスを figure にしない。Bootstrap の .figure が
 * display: inline-block を当て、セルが表の列から外れて桁が重なった。
 *
 * 名前の右には間を足す。名前の幅だけにすると棒が名前に接して見えた。
 * 狭いときは 10px に留める (@see 下の @container)。数字の列に回す幅が無い。
 */
.variable-expense .group-name {
    padding-right: 32px;
    white-space: nowrap;
    width: 1%;
}

.variable-expense .group-name div {
    max-width: 12em;
}

/*
 * 数字の列どうしの間は広めに空ける。右揃えの数字が隣の列の数字に詰まって
 * 見え、どこまでが 1 つの値か読みにくかった。
 */
.variable-expense .number {
    font-variant-numeric: tabular-nums;
    padding-left: 18px;
    text-align: right;
    white-space: nowrap;
    width: 1%;
}

/*
 * 見出しは合計の上の 1 行だけに置き、小項目の数字もその下に揃える。合計と
 * 小項目の両方に見出しを付けると、同じ「今月」「先月との差」が 2 度並んだ。
 *
 * 見出しは数字より控えめにする。読みたいのは数字のほうで、見出しは
 * どの列が何かを一度確かめられれば足りる。
 */
.variable-expense .headings th {
    color: #6c757d;
    font-size: 0.8rem;
    font-weight: normal;
    padding-bottom: 0;
    padding-top: 0;
}

/*
 * 合計の行。数字は縦の中央で揃える。今月だけ字が大きく、上や下で揃えると
 * 先月と差の数字が今月の数字の端に寄って、同じ行の値に見えにくい。
 */
.variable-expense .total th,
.variable-expense .total td {
    border-bottom: 1px solid #dfd7ca;
    padding-bottom: 10px;
}

.variable-expense .total th {
    font-weight: normal;
    text-align: left;
}

.variable-expense .total .current {
    font-size: 1.6rem;
    font-weight: 600;
    line-height: 1.2;
}

/*
 * 狭いときは数字の列を細くする。数字の列の幅は名前の取り分をそのまま削る。
 * 7 桁が 3 列並んでも 414px の画面 (表は 353px) に収まるように、間を詰め、
 * 字を一回り小さくし、合計の今月も大きくしない。合計だけ大きくすると、今月の列ごと広がる。
 *
 * 合計の今月の大きさより後ろに置く。詳細度が同じなので、前に置くと
 * 大きい字の指定に負け、黙って効かない。
 *
 * 画面幅ではなくこの部品自身の幅で切り替える。ダッシュボードは画面が広がる
 * と多段組みになるため、両者は一致しない (700px の画面で表は 639px、768px の
 * 画面では 439px)。画面幅で分けると、広い画面のほうが狭い配分になる。
 */
.variable-expense {
    container-type: inline-size;
}

@container (max-width: 499px) {
    .variable-expense .number {
        font-size: 0.875rem;
        padding-left: 8px;
    }

    .variable-expense .total .current {
        font-size: 1rem;
    }

    .variable-expense .group-name {
        padding-right: 10px;
    }

    /* 12em まで取ると、狭い画面で棒がほとんど残らない。4.5em は 414px の
       画面で 7 桁が 3 列並んだときに表が枠に収まる幅。 */
    .variable-expense .group-name div {
        max-width: 4.5em;
    }
}

/* 合計の線と 1 行目の間を空ける。 */
.variable-expense tbody tr:first-child td {
    padding-top: 10px;
}

/* 入りきらない小項目名は折り返さず省略する。全文は title と、たどった先の
   一覧で読める。 */
.variable-expense .group-name div {
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

.variable-expense .number.difference {
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
        <table>
            <thead>
                <tr class="headings">
                    <td colspan="2"></td>
                    <th scope="col" class="number">今月</th>
                    <th scope="col" class="number">先月</th>
                    <th scope="col" class="number">先月との差</th>
                </tr>
                <tr class="total">
                    {{-- 「変動支出合計」とは書かない。何の合計かは部品の見出しが言っており、
                         6 文字は折り返さないので、狭い画面で小項目の列の幅を
                         それ以上縮められなくなる。 --}}
                    <th scope="row" colspan="2">合計</th>
                    <td class="number current">{!! Html::amount($expense['total']['amount']) !!}</td>
                    <td class="number">{!! Html::amount($expense['total']['previous_amount']) !!}</td>
                    <td class="number difference">{!! Html::withUnit(Html::comparisonAmount($expense['total']['difference'])) !!}</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($expense['groups'] as $group)
                    <tr>
                        <td class="group-name"><div>{!! Html::linkWithQueryString('/summary/daily', [
                            'begin_date' => $expense['period']['begin_date'],
                            'end_date' => $expense['period']['end_date'],
                            'activity_category_item_id[]' => $group['activity_category_item_id']
                        ], $group['item_name'], ['title' => $group['item_name']]) !!}</div></td>
                        <td>
                            {{-- 棒の長さは合計ではなく最も多い小項目を基準にする。
                                 小項目どうしの多い少ないを見るための図なので。
                                 基準は今月と先月を通した最大額で、Service が返す。 --}}
                            <span class="bar" style="width: {{round($group['amount'] / $expense['largest_amount'] * 100)}}%"></span>
                            <span class="bar previous" style="width: {{round($group['previous_amount'] / $expense['largest_amount'] * 100)}}%"></span>
                        </td>
                        <td class="number">{!! Html::amount($group['amount']) !!}</td>
                        <td class="number">{!! Html::amount($group['previous_amount']) !!}</td>
                        <td class="number difference">{!! Html::withUnit(Html::comparisonAmount($group['difference'])) !!}</td>
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
            先月は {{Html::date($expense['period']['previous_end_date'], false)}} までの額 (薄い棒も同じ)
        </p>
    </div>
@else
    <p>データがありません。</p>
@endif
