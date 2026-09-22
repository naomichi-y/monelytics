<style>
.activity-history {
    cursor: pointer;
}

/* 入りきらない場所・用途は折り返さず省略する。全文は title と、たどった先の
   日別集計で読める。折り返させると、長い 1 件だけが何行にも伸びて、
   5 件の並びが読み取りにくくなる。 */
.activity-history-table .location,
.activity-history-table .content {
    max-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/*
 * 狭い画面の列幅。
 *
 * colgroup の割合は横幅のある画面のためのもので、そのまま狭い画面に当てると
 * 金額が「250,000 円」で折り返し、場所と用途には数文字しか残らない。
 *
 * 以前はここで場所と用途を両方 d-none で隠していた。スマホでは発生日・小項目・
 * 金額しか出ず、どこで何に使ったのかが分からない。隠した列のぶん右が空いて
 * いたので、幅が足りなかったわけでもなかった。
 *
 * とはいえ 5 列をそのまま並べると 1 列あたり 4、5 文字しか残らず、場所も用途も
 * 先頭だけの省略になる。用途は列ごと畳んで場所の列へ入れる。
 *
 * 割合で配り直す形も試したが、320px では金額の 19% が「250,000 円」に足りず、
 * 隣の列へはみ出して重なった。表の幅は端末で変わるので、書き方の決まっている
 * 日付と金額は中身の幅で確定させ、残りを場所・用途に回す。
 */
@media (max-width: 767.98px) {
    /* colgroup の幅はインラインなので、!important が無いと勝てない。
       付け忘れると、畳んだ用途の列がそのまま 28% を抱えたままになり、
       場所・用途に回るはずの幅が空白として残る。 */
    .activity-history-table col {
        width: auto !important;
    }

    .activity-history-table th,
    .activity-history-table td {
        padding-left: 4px;
        padding-right: 4px;
    }

    /* 日付と金額は中身の幅ちょうどに縮める (auto レイアウトでの 1% は
       「最小幅まで」の意味になる)。金額は Html::amount の .money が既に
       折り返しを止めている。 */
    .activity-history-table .activity-date,
    .activity-history-table .amount {
        width: 1%;
    }
}

/* 日付を折り返させない。折り返すと曜日だけが次の行に落ち、5 件すべての行が
   二段になる。

   ただし 360px 未満では折り返させる。1 行に伸ばすと日付だけで 111px を取り、
   表が入れ物より 14px はみ出して右端が切れていた。狭いほうを詰めるより、
   日付が二段になるほうがまし。 */
@media (min-width: 360px) and (max-width: 767.98px) {
    .activity-history-table .activity-date {
        white-space: nowrap;
    }

    /* 残った幅は場所・用途へ。小項目は利用者が 32 文字まで付けられるので、
       伸ばさずに省略する (全文は title)。 */
    .activity-history-table .item-name {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        width: 20%;
    }
}
</style>
<script>
    $(function() {
        $(document).on("click", ".activity-history", function() {
            var activityDate = $(this).attr("data-date");
            var url = "/summary/daily?begin_date=" + activityDate + "&end_date=" + activityDate;

            location.href = url;
        });
    });
</script>
@if (sizeof($histories))
    <table class="table table-striped table-hover activity-history-table">
        <colgroup>
            <col style="width: 20%" />
            <col style="width: 18%" />
            <col style="width: 12%" />
            <col style="width: 22%" />
            <col style="width: 28%" />
        </colgroup>
        <thead>
            <th class="text-center">発生日</th>
            <th class="text-center">小項目</th>
            <th class="text-center">金額</th>
            {{-- 狭い画面では用途の列を畳んで場所の列へ入れる。見出しもそれに
                 合わせる。日別集計の検索欄が「場所・用途」で 1 つなので、
                 まとめ方もそちらに揃う。 --}}
            <th class="text-center">場所<span class="d-md-none">・用途</span></th>
            <th class="text-center d-none d-md-table-cell">用途</th>
        </thead>
        <tbody>
            @foreach ($histories as $history)
                @php
                    $location = $history->location ?? '';
                    $content = $history->content ?? '';

                    // 狭い画面用の 1 列分。片方しか無い行があるので、区切りは
                    // 両方そろっているときだけ入れる。
                    $location_and_content = trim(
                        $location
                        . (strlen($location) && strlen($content) ? ' / ' : '')
                        . $content
                    );
                @endphp
                <tr data-date="{{$history->activity_date}}" class="activity-history">
                    <td class="text-center activity-date">{{Html::date($history->activity_date)}}</td>
                    <td class="item-name" title="{{$history->activityCategoryItem->item_name}}">{{{$history->activityCategoryItem->item_name}}}</td>
                    <td class="text-end amount">{!! Html::amount($history->amount) !!}</td>
                    <td class="location" title="{{$location_and_content}}">
                        <span class="d-none d-md-inline">{{{$location}}}</span>
                        <span class="d-md-none">{{{$location_and_content}}}</span>
                    </td>
                    <td class="content d-none d-md-table-cell" title="{{$content}}">{{{$content}}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{-- 先の日付で登録した家賃や給与を出さないことを書いておく。書かないと、
         今さっき登録したものが一覧に出てこない理由が分からない。 --}}
    <p class="note text-end">※発生日が本日までのものを表示しています</p>
@else
    <p>データがありません。</p>
@endif
