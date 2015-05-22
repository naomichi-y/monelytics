<div class="row" style="overflow: auto">
  <div class="col-md-6">
    <h2>利用頻度ランキング</h2>
    @if (sizeof($location_rankings))
      <table class="table table-striped table-hover">
        <colgroup>
          <col style="width: 10%">
          <col style="width: 20%">
          <col style="width: 20%">
          <col style="width: 25%">
          <col style="width: 25%">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">ランク</th>
            <th class="text-center">科目名</th>
            <th class="text-center">場所</th>
            <th class="text-center">利用回数</th>
            <th class="text-center">総額</th>
          </tr>
        </thead>
        <tbody>
          {{-- */ $i = 1 /* --}}
          @foreach ($location_rankings as $location_ranking)
            <tr>
              <td class="text-center">{{$i++}}</td>
              <td>{{{$location_ranking->group_name}}}</td>
              <td>{{HTML::linkWithQueryString('/summary/daily', array('begin_date' => $date_range->begin_date, 'end_date' => $date_range->end_date, 'location' => $location_ranking->location), $location_ranking->location)}}</td>
              <td class="text-right">{{number_format($location_ranking->count)}}回</td>
              <td class="text-right">{{number_format($location_ranking->amount)}}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p>データがありません。</p>
    @endif
  </div>

  <div class="col-md-6">
    <h2>支出ランキング <span class="note">(固定支出を除く)</span></h2>
    @if (sizeof($expense_rankings))
      <table class="table table-striped table-hover">
        <colgroup>
          <col style="width: 5%">
          <col style="width: 25%">
          <col style="width: 20%">
          <col style="width: 20%">
          <col style="width: 20%">
          <col style="width: 10%">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">ランク</th>
            <th class="text-center">発生日</th>
            <th class="text-center">科目名</th>
            <th class="text-center">場所</th>
            <th class="text-center">用途</th>
            <th class="text-center">金額</th>
          </tr>
        </thead>
        <tbody>
          {{-- */ $i = 1 /* --}}
          @foreach ($expense_rankings as $expense_ranking)
            <tr>
              <td class="text-center">{{$i++}}</td>
              <td class="text-center">{{HTML::date($expense_ranking->activity_date)}}</td>
              <td>{{{$expense_ranking->activityCategoryGroup->group_name}}}</td>
              <td>{{{$expense_ranking->location}}}</td>
              <td>{{{$expense_ranking->content}}}</td>
              <td class="text-right">{{number_format($expense_ranking->amount)}}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p>データがありません。</p>
    @endif
  </div>
</div>
