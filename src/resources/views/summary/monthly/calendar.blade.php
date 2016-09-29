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
                  <div class="amounts text-right">
                    {!! link_to("summary/daily?begin_date={$params[$i]['date']}&amp;end_date={$params[$i]['date']}&amp;cost_type=1", number_format($params[$i]['variable_amount'])) !!}
                      <br />
                    @if ($params[$i]['constant_amount'])
                      {!! link_to("summary/daily?begin_date={$params[$i]['date']}&amp;end_date={$params[$i]['date']}&amp;cost_type=2", number_format($params[$i]['constant_amount'])) !!}
                    @else
                      &nbsp;
                    @endif
                  </div>
                @endif
              </td>
            @endfor
          </tr>
        @endforeach
      </tbody>
    </table>
    <p class="text-right">※括弧内は固定収支</p>
  </div>
@elseif (is_null($calendar))
  <p>カレンダーを表示するには月指定の検索条件を行って下さい。</p>
@else
  <p>データがありません。</p>
@endif
