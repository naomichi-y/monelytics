<script>
  $(function() {
    var minHeight = 400;
    var tabHeight = window.innerHeight - minHeight;

    if (tabHeight < minHeight) {
      tabHeight = minHeight;
    }

    // テーブルのヘッダのスクロールを固定
    $('#table-selector').tablefix({
      width: $('#tab-container').width(),
      height: tabHeight,
      fixRows: 3,
      fixCols: 1
    });
  });
</script>

@if (sizeof($summary['data']))
  <div id="tab-container">
    <table class="table table-hover table-bordered table-highlight" id="table-selector">
      <colgroup style="width: 10%"></colgroup>
      <colgroup>
        <col span="{{$summary['header_size']['total']}}" style="width: {{floor(66 / $summary['header_size']['total'])}}%" />
      </colgroup>
      <colgroup span="3" style="width: 8%"></colgroup>
      <thead>
        <tr>
          <th class="text-center" rowspan="3">年月</th>
          @foreach ($summary['headers'] as $cost_type => $activity_categories)
            <th colspan="{{$summary['header_size']['cost_type'][$cost_type]}}" class="text-center">
              @if ($cost_type == ActivityCategory::COST_TYPE_VARIABLE)
                変動収支
              @else
                固定収支
              @endif
            </th>
          @endforeach
          <th rowspan="3" class="text-center">総支出</th>
          <th rowspan="3" class="text-center">総収入</th>
          <th rowspan="3" class="text-center">合計</th>
        </tr>
        <tr>
          @foreach ($summary['headers'] as $cost_type => $activity_categories)
            @foreach ($activity_categories as $activity_categories)
              <th colspan="{{sizeof($activity_categories['activity_category_groups'])}}" class="text-center">{{{$activity_categories['activity_category_name']}}}</th>
            @endforeach
          @endforeach
        </tr>
        <tr>
          @foreach ($summary['headers'] as $cost_type => $activity_categories)
            @foreach ($activity_categories as $activity_categories)
              @foreach ($activity_categories['activity_category_groups'] as $activity_category_group_name)
                <th class="text-center">{{{$activity_category_group_name}}}</th>
              @endforeach
            @endforeach
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach ($summary['data'] as $summary_date => $data)
          <tr>
            <th class="text-center">{{$summary_date}}</th>
            @foreach ($summary['headers'] as $cost_type => $activity_categories)
              @foreach ($activity_categories as $activity_categories)
                @foreach ($activity_categories['activity_category_groups'] as $activity_category_group_id => $activity_category_groups)
                  <td class="text-right">
                  @if (isset($data['amount'][$cost_type][$activity_categories['activity_category_id']][$activity_category_group_id]))
                    {{HTML::linkWithQueryString('/dailySummary', array('date_month' => str_replace('/', '-', $summary_date), 'activity_category_group_id[]' => $activity_category_group_id), number_format($data['amount'][$cost_type][$activity_categories['activity_category_id']][$activity_category_group_id]))}}
                  @else
                    0
                  @endif
                  </td>
                @endforeach
              @endforeach
            @endforeach
            <td class="text-right">{{number_format($data['total_expense_amount'])}}</td>
            <td class="text-right">{{number_format($data['total_income_amount'])}}</td>
            <td class="text-right">{{number_format($data['total_amount'])}}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th class="text-center">合計</th>
          @foreach ($summary['headers'] as $cost_type => $activity_categories)
            @foreach ($activity_categories as $activity_categories)
              @foreach ($activity_categories['activity_category_groups'] as $activity_category_group_id => $activity_category_group_name)
                <td class="text-right">
                  @if (isset($summary['footers']['yearly_total_activity_categories'][$activity_category_group_id]))
                    {{number_format($summary['footers']['yearly_total_activity_categories'][$activity_category_group_id])}}
                  @else
                    0
                  @endif
                </td>
              @endforeach
            @endforeach
          @endforeach
          <td class="text-right">{{number_format($summary['footers']['yearly_total_expense_amount'])}}</td>
          <td class="text-right">{{number_format($summary['footers']['yearly_total_income_amount'])}}</td>
          <td class="text-right">{{number_format($summary['footers']['yearly_total_result_amount'])}}</td>
        </tr>
      </tfoot>
    </table>
  </div>
@else
  <p>データがありません。</p>
@endif
