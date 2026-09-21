<script>
    $(function() {
        // テーブルのヘッダのスクロールを固定する。幅の追従も含めて
        // fixTableHeader が面倒を見る (@see assets/js/common.js)。
        $('#tab-container').fixTableHeader({
            table: '#table-selector',
            widthAdjust: 4,
            fixRows: 1
        });
    });
</script>

@if ($summary['cost_size'][App\Models\ActivityCategory::COST_TYPE_VARIABLE] || $summary['cost_size'][App\Models\ActivityCategory::COST_TYPE_CONSTANT])
    <div id="tab-container">
        <table class="table table-hover table-bordered table-highlight" id="table-selector">
            <colgroup span="3" style="width: 10%"></colgroup>
            <colgroup span="2" style="width: 18%"></colgroup>
            <colgroup style="width: 16%"></colgroup>
            <colgroup style="width: 18%"></colgroup>
            <thead>
                <tr>
                    <th class="text-center">収支タイプ</th>
                    <th class="text-center">大項目</th>
                    <th class="text-center">小項目</th>
                    <th class="text-center">現金収支額</th>
                    <th class="text-center">クレジット収支額</th>
                    <th class="text-center">前月比</th>
                    <th class="text-center">小項目合計</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['category_summary'] as $cost_type => $cost_summary)
                    <tr>
                        <th rowspan="{{$summary['cost_size'][$cost_type]}}">
                            @if ($cost_type == App\Models\ActivityCategory::COST_TYPE_VARIABLE)
                                変動収支
                            @else
                                固定収支
                            @endif
                        </th>
                        <?php $i = 0; ?>
                        @if (sizeof($cost_summary))
                            @foreach ($cost_summary as $activity_category_id => $activity_category_summary)
                                @if ($i > 0)
                                    <tr>
                                @endif
                                <th rowspan="{{sizeof($activity_category_summary['data'])}}">{{{$activity_category_summary['category_name']}}}</th>

                                <?php $j = 0; ?>
                                @foreach ($activity_category_summary['data'] as $activity_category_item_id => $activity_category_item_summary)
                                    @if ($j > 0)
                                        <tr>
                                    @endif
                                        <th>{{{$activity_category_item_summary['item_name']}}}</th>
                                        <td class="text-end">{!! Html::amountLink($activity_category_item_summary['cash_amount'], fn($text) => Html::linkWithQueryString($base_link, ['activity_category_item_id[]' => $activity_category_item_id, 'credit_flag' => App\Models\Activity::CREDIT_FLAG_UNUSE], $text)) !!}</td>
                                        <td class="text-end">{!! Html::amountLink($activity_category_item_summary['credit_amount'], fn($text) => Html::linkWithQueryString($base_link, ['activity_category_item_id[]' => $activity_category_item_id, 'credit_flag' => App\Models\Activity::CREDIT_FLAG_USE], $text)) !!}</td>
                                        <td class="text-end">
                                            @if (isset($comparisons['groups'][$activity_category_item_id]))
                                                {{Html::comparisonRate($comparisons['groups'][$activity_category_item_id])}}
                                            @endif
                                        </td>
                                        <td class="text-end">{!! Html::amountLink($activity_category_item_summary['group_amount'], fn($text) => Html::linkWithQueryString($base_link, ['activity_category_item_id[]' => $activity_category_item_id], $text)) !!}</td>
                                    </tr>
                                    <?php $j++; ?>
                                @endforeach
                                <?php $i++; ?>
                            @endforeach
                        @else
                            <th class="text-center">-</th>
                            <th class="text-center">-</th>
                            <th class="text-center">-</th>
                            <th class="text-center">-</th>
                            <th class="text-center">-</th>
                            <th class="text-center">-</th>
                        @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">収入合計</th>
                    <td class="text-end">{!! Html::amount($summary['income_summary']['cash_amount']) !!}</td>
                    <td class="text-end">{!! Html::amount($summary['income_summary']['credit_amount']) !!}</td>
                    <td class="text-end">
                        @if (isset($comparisons['totals']['income']))
                            {{Html::comparisonRate($comparisons['totals']['income'])}}
                        @endif
                    </td>
                    <td class="text-end">{!! Html::amount($summary['income_summary']['income_amount']) !!}</td>
                </tr>
                <tr>
                    <th colspan="3">支出合計</th>
                    <td class="text-end">{!! Html::amount($summary['expense_summary']['cash_amount']) !!}</td>
                    <td class="text-end">{!! Html::amount($summary['expense_summary']['credit_amount']) !!}</td>
                    <td class="text-end">
                        @if (isset($comparisons['totals']['expense']))
                            {{Html::comparisonRate($comparisons['totals']['expense'])}}
                        @endif
                    </td>
                    <td class="text-end">{!! Html::amount($summary['expense_summary']['expense_amount']) !!}</td>
                </tr>
                <tr>
                    <th colspan="5">合計</th>
                    <td class="text-end">
                        @if (isset($comparisons['totals']['total']))
                            {{Html::comparisonRate($comparisons['totals']['total'])}}
                        @endif
                    </td>
                    <td class="text-end">{!! Html::amount($summary['total_amount']) !!}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <p>データがありません。</p>
@endif
