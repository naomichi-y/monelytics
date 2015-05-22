<script>
  $(function() {
    var minHeight = 400;
    var tabHeight = window.innerHeight - minHeight;

    if (tabHeight < minHeight) {
      tabHeight = minHeight;
    }

    // テーブルのヘッダのスクロールを固定
    $('#table-selector').tablefix({
      width: $('#tab-container').width() - 4,
      height: tabHeight,
      fixRows: 1
    });
  });
</script>

@if ($summary['cost_size'][ActivityCategory::COST_TYPE_VARIABLE] || $summary['cost_size'][ActivityCategory::COST_TYPE_CONSTANT])
  <div id="tab-container">
    <table class="table table-hover table-bordered table-highlight" id="table-selector">
      <colgroup span="3" style="width: 10%">
      <colgroup span="5" style="width: 14%">
      <thead>
        <tr>
          <th class="text-center">収支タイプ</th>
          <th class="text-center">科目カテゴリ名</th>
          <th class="text-center">科目</th>
          <th class="text-center">現金収支額</th>
          <th class="text-center">クレジット収支額</th>
          <th class="text-center">特別収支</th>
          <th class="text-center">科目合計<br /><span class="note">(特別収支を含まない)</span></th>
          <th class="text-center">科目合計<br /><span class="note">(特別収支を含む)</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($summary['category_summary'] as $cost_type => $cost_summary)
          <tr>
            <th rowspan="{{$summary['cost_size'][$cost_type]}}">
              @if ($cost_type == ActivityCategory::COST_TYPE_VARIABLE)
                変動収支
              @else
                固定収支
              @endif
            </th>
            {{-- */ $i = 0 /* --}}
            @if (sizeof($cost_summary))
              @foreach ($cost_summary as $activity_category_id => $activity_category_summary)
                @if ($i > 0)
                  <tr>
                @endif
                <th rowspan="{{sizeof($activity_category_summary['data'])}}">{{{$activity_category_summary['category_name']}}}</th>

                {{-- */ $j = 0 /* --}}
                @foreach ($activity_category_summary['data'] as $activity_category_group_id => $activity_category_group_summary)
                  @if ($j > 0)
                    <tr>
                  @endif
                    <th>{{{$activity_category_group_summary['group_name']}}}</th>
                    <td class="text-right">{{HTML::linkWithQueryString($base_link, array('activity_category_group_id[]' => $activity_category_group_id, 'credit_flag' => Activity::CREDIT_FLAG_UNUSE), number_format($activity_category_group_summary['cash_amount']))}}</td>
                    <td class="text-right">{{HTML::linkWithQueryString($base_link, array('activity_category_group_id[]' => $activity_category_group_id, 'credit_flag' => Activity::CREDIT_FLAG_USE), number_format($activity_category_group_summary['credit_amount']))}}</td>
                    <td class="text-right">
                      @if ($cost_type == ActivityCategory::COST_TYPE_VARIABLE)
                       {{HTML::linkWithQueryString($base_link, array('activity_category_group_id[]', $activity_category_group_id, 'special_flag' => Activity::SPECIAL_FLAG_USE), number_format($activity_category_group_summary['special_use_amount']))}}
                      @else
                       N/A
                      @endif
                    </td>
                    <td class="text-right">{{HTML::linkWithQueryString($base_link, array('activity_category_group_id[]' => $activity_category_group_id, 'special_flag' => Activity::SPECIAL_FLAG_UNUSE), number_format($activity_category_group_summary['special_unuse_amount']))}}</td>
                    <td class="text-right">{{HTML::linkWithQueryString($base_link, array('activity_category_group_id[]' => $activity_category_group_id), number_format($activity_category_group_summary['group_amount']))}}</td>
                  </tr>
                  {{-- */ $j++ /* --}}
                @endforeach
                {{-- */ $i++ /* --}}
              @endforeach
            @else
              <th class="text-center">-</th>
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
          <td class="text-right">{{number_format($summary['income_summary']['cash_amount'])}}</td>
          <td class="text-right">{{number_format($summary['income_summary']['credit_amount'])}}</td>
          <td class="text-right">{{number_format($summary['income_summary']['special_use_amount'])}}</td>
          <td class="text-right">{{number_format($summary['income_summary']['special_unuse_amount'])}}</td>
          <td class="text-right">{{number_format($summary['income_summary']['income_amount'])}}</td>
        </tr>
        <tr>
          <th colspan="3">支出合計</th>
          <td class="text-right">{{number_format($summary['expense_summary']['cash_amount'])}}</td>
          <td class="text-right">{{number_format($summary['expense_summary']['credit_amount'])}}</td>
          <td class="text-right">{{number_format($summary['expense_summary']['special_use_amount'])}}</td>
          <td class="text-right">{{number_format($summary['expense_summary']['special_unuse_amount'])}}</td>
          <td class="text-right">{{number_format($summary['expense_summary']['expense_amount'])}}</td>
        </tr>
        <tr>
          <th colspan="7">合計</th>
          <td class="text-right">{{number_format($summary['total_amount'])}}</td>
        </tr>
      </tfoot>
    </table>
  </div>
@else
  <p>データがありません。</p>
@endif
