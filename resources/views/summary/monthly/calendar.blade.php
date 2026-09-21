@if (sizeof($calendar))
    <style>
        .current_date {
            background-color: #f8f5f0;
        }

        .date {
            border-bottom: 1px dashed #dfd7ca;
            font-weight: bold;
            padding-bottom: 8px;
        }

        .saturday {
            color: #40AAEF;
        }

        .sunday {
            color: #F26964;
        }

        .holiday {
            color: #58BE89;
        }

        .amounts {
            padding-top: 8px;
            padding-bottom: 4px;
        }

        /*
         * 変動と固定を段の位置ではなくラベルで見分けさせる。位置で読ませて
         * いた頃は表の下に注釈が要り、その注釈が実際の表示 (括弧は付いて
         * いない) と食い違ったまま 10 年残っていた。
         *
         * 金額は右端へ寄せて桁を揃え、ラベルは左端に置く。入り切らない幅では
         * 金額だけを次の行へ送る (7 列で 1 か月を並べる表なので、狭い画面では
         * 1 セルが 50px ほどになる)。折り返しを禁じると表が画面の倍近くまで
         * 広がり、折り返しを許さずラベルだけを縮めると「変」「動」と縦に
         * 割れる。実測では 390px 幅で、横のはみ出しはラベルを付ける前と
         * 同じ数 px に収まる。
         */
        .amount-row {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 4px;
        }

        /* 金額は行を分けても右端。margin で寄せるのは、ラベルと同じ行に
           並んだときも、次の行へ落ちたときも同じ位置に来るため。 */
        .amount-row .money {
            margin-left: auto;
        }

        /* 読むのは金額のほう。ラベルは桁を数える邪魔をしない濃さに落とす。 */
        .cost-type {
            white-space: nowrap;
            color: #999;
            font-size: 0.85em;
        }
    </style>
    <div style="overflow: auto">
        <table class="table table-hover table-bordered">
            <colgroup>
                <col span="7" style="width: 14%" />
            </colgroup>
            <thead>
                <th class="text-center">日</th>
                <th class="text-center">月</th>
                <th class="text-center">火</th>
                <th class="text-center">水</th>
                <th class="text-center">木</th>
                <th class="text-center">金</th>
                <th class="text-center">土</th>
            </thead>
            <tbody>
                @foreach ($calendar as $week => $params)
                    <tr>
                        @for ($i = 0; $i < 7; $i++)
                            @if (isset($params[$i]) && $params[$i]['current_date'])
                                <td class="current_date">
                            @else
                                <td>
                            @endif
                                @if (isset($params[$i]))
                                    <div class="date text-center">
                                        @if ($params[$i]['holiday'])
                                            <span class="holiday">{{$params[$i]['short_date']}} ({{$params[$i]['holiday_name'][0]}})</span>
                                        @elseif ($params[$i]['day'] == 0)
                                            <span class="sunday">{{$params[$i]['short_date']}}</span>
                                        @elseif ($params[$i]['day'] == 6)
                                            <span class="saturday">{{$params[$i]['short_date']}}</span>
                                        @else
                                            {{$params[$i]['short_date']}}
                                        @endif
                                    </div>
                                    <div class="amounts">
                                        {{-- 区切りは URL そのものの文字である '&' にする。HTML への逃がしは
                                             link_to が行うため、ここで '&amp;' を入れると二重になり、
                                             2 つ目以降のパラメータ名が amp;xxx になって読み捨てられる。 --}}
                                        <div class="amount-row">
                                            <span class="cost-type">変動</span>
                                            {!! Html::amountLink($params[$i]['variable_amount'], fn($text) => link_to("summary/daily?begin_date={$params[$i]['date']}&end_date={$params[$i]['date']}&cost_type=1", $text)) !!}
                                        </div>
                                        {{-- 固定収支のない日も 2 段目を残す。段を畳むと週の中で
                                             セルの高さが揃わず、日付の行まで上下に動いて見える。 --}}
                                        <div class="amount-row">
                                            @if ($params[$i]['constant_amount'])
                                                <span class="cost-type">固定</span>
                                                {!! Html::amountLink($params[$i]['constant_amount'], fn($text) => link_to("summary/daily?begin_date={$params[$i]['date']}&end_date={$params[$i]['date']}&cost_type=2", $text)) !!}
                                            @else
                                                &nbsp;
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif (is_null($calendar))
    <p>カレンダーを表示するには月指定の検索条件を行って下さい。</p>
@else
    <p>データがありません。</p>
@endif
